﻿<?php

//Author: Zach Cordell
//Date: 5/1/15
//Purpose: cron utility will be run every 1 time per day.  Will process pledges that are due to be run today

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

define('ALLOW_POST', false);
define('ALLOW_GET', true);
define('ALLOW_PUT', false);
define('ALLOW_PATCH', false);
define('BUCKET_CHUNK_SIZE', 15);  //How many pledges are put into a single bucket for synchronous processing.
$response = null;

require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';

$returnArray = array();

if (!function_exists("\curl_init"))
    \load_curl();

require_once("/cgi-bin/africanewlife.cfg/scripts/custom/Log/esglogpaycron-v2.0.php");

// $fileLogger = new fileLogger(true, fileLogger::All);  //file logger is a log worker
// $logger = esgLogger::getInstance();
// $logger->registerLogWorker($fileLogger);  //register the log worker with the esgLogger event bus singleton
// $logArray[] = date("Y-m-d H:i:s  ")."Begin Pledge Processing...";

$logArray = array();

// /********CONSTANTS*********/

switch(intval($_GET['pledge'])){

    case intval($_GET['pledge']) < 0:
        returnPledgeIdChunks();
        break;
    default:
        //executeUpdatesForRecords($_GET['start']);
        $pledge = RNCPHP\donation\pledge::fetch(intval($_GET['pledge']));
        $payMeth = ($pledge->paymentMethod2->PaymentMethodType->LookupName == "Credit Card") ? 1 : 2;
        $runResult = Initialize($pledge->ID, $pledge->PledgeAmount, $payMeth, $pledge->Balance, $pledge->firstTimeDonationCredit);
        logToFile($logArray);
        return outputResponse($response, null);
        break;

}


function returnPledgeIdChunks(){
    try {
        $ar = RNCPHP\AnalyticsReport::fetch(100228);
        $arr = $ar->run();

        $bucketCounter = 0;

        for ($ii = $arr->count(); $ii--;) {
            $row = $arr->next();
            $response['chunks'][$bucketCounter]['pledges'][] = $row['Pledge ID'];

            if(count($response['chunks'][$bucketCounter]['pledges']) > BUCKET_CHUNK_SIZE){
                $bucketCounter++;
            }
        }

        $response['getChunks'] = true;

        return outputResponse($response, null);
    } catch (\Exception $ex) {
        return outputResponse(null, $ex->getMessage());
    } catch (RNCPHP\ConnectAPIError $ex) {
        return outputResponse(null, $ex->getMessage());
    }


}

//run analytics report and process payments.


function Initialize($pledgeID, $pledgeAmt, $payMeth, $balance, $firstTimeDonationCredit)
{
    global $logArray;
    /* Removed balance b/c of unallocated solution ZC 10/25
    $logArray[] = date("Y-m-d H:i:s  ")."balance before = ".$balance;
    if ($balance > 0){
    }else{
        $balance = 0;
    }
    $logArray[] = date("Y-m-d H:i:s  ")."Balance = ".$balance;
    */

    //if its the first donation toward a pledge and they have a first time donation credit
    //this happens when a co spon pledge is created and they have a credit for upping their pledge. 
    //this will only happen to quarterly or yearly pledges
    if (getCountPledgeDonations($pledgeID) == 0 && $firstTimeDonationCredit > 0)
        $totalToCharge = $pledgeAmt - $firstTimeDonationCredit;
    else {
        $totalToCharge = $pledgeAmt;
    }

    $logArray[] = date("Y-m-d H:i:s  ")."totalTocharge = " . $totalToCharge . " pledge amount = " . $pledgeAmt;

    if ($totalToCharge > 0) { //we have something to charge
        if ($pledgeAmt > 0  && $payMeth > 0) {
            try {

                $pledge = RNCPHP\donation\pledge::fetch($pledgeID);
                //0-none 1-cc 2-eft
                $logArray[] = date("Y-m-d H:i:s  ")."Processing pledge ID = " . $pledgeID;
                $result = ($pledge->paymentMethod2->PaymentMethodType->ID == "1") ? processCCpayment($pledge, $totalToCharge) : processEFTpayment($pledge, $totalToCharge);

                if ($result > 0) {
                    //reset the pledge to the next transaction date. Reset # of attempts to 0
                    resetNextPayment($pledge, true);
                } else {
                    //reset the pledge set next trans date to tomorrow. Increment # of attempts
                    $logArray[] = date("Y-m-d H:i:s  ")."46 - processCCpayment returned bad result";
                    //_output("46 - processCCpayment returned bad result");
                    resetNextPayment($pledge, false);
                }
            } catch (Exception $e) {
                $logArray[] = date("Y-m-d H:i:s  ")."PHP Error " . $e->getMessage();
            } catch (RNCPHP\ConnectAPIError $e) {
                $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
            }
        } //end if
    } else { //we have a siatuion where they've paid ahead more than a month.
        //create a transaction and comment 
        //creating a $0 donation 
        //createing a $0 transaction
        //createing a donation to pledge record.

        $pledge = RNCPHP\donation\pledge::fetch($pledgeID);

        try {
            $newDonation = new RNCPHP\donation\Donation;
            $newDonation->Contact = $pledge->Contact;
            $newDonation->DonationDate = time();
            $newDonation->Amount = number_format(0, 2, '.', ''); //0.00 for amount
            $newDonation->Type = RNCPHP\donation\Type::fetch(1);
            $newDonation->save(RNCPHP\RNObject::SuppressAll);
        } catch (Exception $e) {
            $logArray[] = date("Y-m-d H:i:s  ").$e->getMessage();
            return 0;
        } catch (RNCPHP\ConnectAPIError $e) {
            $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        }

        $donation2Pledge = new RNCPHP\donation\donationToPledge();
        $donation2Pledge->PledgeRef = $pledge->ID;
        $donation2Pledge->DonationRef = $newDonation->ID;

        try {
            $donation2Pledge->save(RNCPHP\RNObject::SuppressAll);
            //logMessage($donation2Pledge);
        } catch (Exception $e) {
            $logArray[] = date("Y-m-d H:i:s  ").$e->getMessage();
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        }

        try {
            $trans = new RNCPHP\financial\transactions();
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch('Completed');
            $trans->totalCharge = number_format(0, 2, '.', ''); //0.00
            $trans->contact = $pledge->Contact;
            $trans->refCode = "BalCov"; //balance covered it
            $trans->paymentMethod = $pledge->paymentMethod2;

            $obj->Notes[0] = new RNCPHP\Note();
            $obj->Notes[0]->Channel = new RNCPHP\NamedIDLabel();
            $obj->Notes[0]->Channel->LookupName = "Fax";
            $obj->Notes[0]->Text = "Balance covered this transaction: PledgeAmt:" . $pledgeAmt . " Balance:" . $balance;

            if ($newDonation) {
                $trans->donation = $newDonation;
            }

            $trans->save();
        } catch (\Exception $e) {
            $logArray[] = date("Y-m-d H:i:s  ").$e->getMessage();
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
            return false;
        }
    }

    RNCPHP\ConnectAPI::commit();
}

// update payment result, if successful update next payment due date and create transaction.
// if not sucessful, try again tomorrow
//  if not successfult 3 times in a row,  put in 'pledge on hold' status set next payment date to null.

function processCCpayment(RNCPHP\donation\pledge $pledge, $totalToCharge)
{
    global $logArray;
    //create transaction and pass in id.  then set all the values before save in the createTransaction
    $trans = new RNCPHP\financial\transactions();
    $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(4); //processing then we'll update
    $trans->save(RNCPHP\RNObject::SuppressAll);


    // if (strlen($pledge->paymentMethod2->PN_Ref) > 1 && strlen($pledge->paymentMethod2->InfoKey) < 1) {
    if (strlen($pledge->paymentMethod2->PN_Ref) > 1) {
        $mytx = array(
            'MagData' => '',
            'PNRef' => $pledge->paymentMethod2->PN_Ref,
            'ExtData' => '<InvNum>' . $trans->ID . '</InvNum>',
            'TransType' => "Sale",
            'CardNum' => "",
            'ExpDate' => "",
            'CVNum' => "",
            'amount' => $totalToCharge,
            'InvNum' => $trans->ID,
            'NameOnCard' => "",
            'Zip' => "",
            'Street' => "",
            'op' => "ArgoFire/transact.asmx/ProcessCreditCard"
        );
        // } else if (strlen($pledge->paymentMethod2->InfoKey) > 1 && strlen($pledge->paymentMethod2->PN_Ref) < 1) {
    } else if (strlen($pledge->paymentMethod2->InfoKey) > 1) {
        $mytx = array(
            'Amount' => $totalToCharge,
            'CcInfoKey' => $pledge->paymentMethod2->InfoKey,
            'op' => "admin/ws/recurring.asmx/ProcessCreditCard",
            'Vendor' => cfg_get(CUSTOM_CFG_frontstream_vendor),
            'ExtData' => '<InvNum>' . $trans->ID . '</InvNum>',
            'InvNum' => $trans->ID
        );
    }

    $returnValues = runTransaction($mytx);

    $notes = print_r($returnValues, true);

    //strlen condition put in to interpret whitespace as a decline
    if (!$returnValues || $returnValues['code'] != 0 || strlen(trim($returnValues['code'])) == 0) {
        $logArray[] = date("Y-m-d H:i:s  ")."87 - frontstream failure ";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($returnValues, true);
        //dont need to create donation or d2p if declined
        createTransaction($pledge->paymentMethod2, null, $totalToCharge, createDonation($pledge, $totalToCharge), "Declined", $pledge->Contact, $notes, $trans);
        $transID = -99; //set to negative so we know to not to reset the next pledge date to tomorrow instead of calculating based on frequency
    } else {
        $logArray[] = date("Y-m-d H:i:s  ")."84 - frontstream success ";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($returnValues, true);
        //create donation and associate it to existing pledge
        //then create transaction and associate it to existing donation
        $transID = createTransaction($pledge->paymentMethod2, $returnValues['auth'], $totalToCharge, createDonation($pledge, $totalToCharge), "Completed", $pledge->Contact, $notes, $trans);
    }


    return $transID;
}

function processEFTpayment(RNCPHP\donation\pledge $pledge, $totalToCharge)
{
    global $logArray;
    //create transaction and pass in id.  then set all the values before save in the createTransaction
    $trans = new RNCPHP\financial\transactions();
    $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(4); //processing then we'll update
    $trans->save(RNCPHP\RNObject::SuppressAll);

    // if (strlen($pledge->paymentMethod2->PN_Ref) > 1 && strlen($pledge->paymentMethod2->InfoKey) < 1) {
    if (strlen($pledge->paymentMethod2->PN_Ref) > 1) {
        $mytx = array(
            'op' => 'ArgoFire/transact.asmx/ProcessCheck',
            'CheckNum' => '',
            'TransType' => "RepeatSale",
            'MICR' => '',
            'DL' => '',
            'SS' => '',
            'DOB' => '',
            'StateCode' => '',
            'CheckType' => '',
            'TransitNum' => '',
            'AccountNum' => '',
            'Amount' => $totalToCharge,
            'NameOnCheck' => '',
            'ExtData' => '<InvNum>' . $trans->ID . '</InvNum><PNRef>' . $pledge->paymentMethod2->PN_Ref . '</PNRef>'
        );
        // } else if (strlen($pledge->paymentMethod2->InfoKey) > 1 && strlen($pledge->paymentMethod2->PN_Ref) < 1) {    
    } else if (strlen($pledge->paymentMethod2->InfoKey) > 1) {
        $mytx = array(
            'Amount' => $totalToCharge,
            'op' => "admin/ws/recurring.asmx/ProcessCheck",
            'Vendor' => cfg_get(CUSTOM_CFG_frontstream_vendor),
            'CheckInfoKey' => $pledge->paymentMethod2->InfoKey,
            'InvNum' => $trans->ID,
            'ExtData' => '<InvNum>' . $trans->ID . '</InvNum>'
        );
    }

    $returnValues = runTransaction($mytx);
    $notes = print_r($returnValues, true);

    if (!$returnValues || $returnValues['code'] != 0  || strlen(trim($returnValues['code'])) == 0) {
        $logArray[] = date("Y-m-d H:i:s  ")."87 - frontstream failure ";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($returnValues, true);
        createTransaction($pledge->paymentMethod2, null, $totalToCharge, createDonation($pledge, $totalToCharge), "Declined", $pledge->Contact, $notes, $trans);
        $transID = -99; //set to negative so we know to not to reset the next pledge date to tomorrow instead of calculating based on frequency
    } else {
        $logArray[] = date("Y-m-d H:i:s  ")."84 - frontstream success ";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($returnValues, true);
        $transID = createTransaction($pledge->paymentMethod2, $returnValues['auth'], $totalToCharge, createDonation($pledge, $totalToCharge), "Completed", $pledge->Contact, $notes, $trans);
    }

    return $transID;
}

function resetNextPayment($pledge, $goodTrans)
{
    global $logArray;
    try {
        if ($goodTrans) {
            //good trans we want to set the numProcessingAttempts field to 0 and set teh date.

            //IN THE REPORT THAT DRIVES THESE RESULTS WE FILTER OUT PLEDGES WHERE STOP DATE < TODAY.  
            //Dont worry about the next transaction date being set to a date past the stop date.
            //$pledge->NextTransaction = strtotime($pledge->Frequency->StrToTimeValue);
            $pledge->numProcessingAttempts = 0;
            $pledge->PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(1); //Active

        } else {
            //bad transaction we want to reset the date for tomorrow and increment the numProcessingAttempts field.
            _output("166 before bad pledge transaction date reset");
            $timesAttempted = $pledge->numProcessingAttempts;
            $pledge->numProcessingAttempts = ++$timesAttempted;
            $numAttempts = cfg_get(CUSTOM_CFG_Auto_ReProcess_Frontstream_NumAttempts);

            if (cfg_get(CUSTOM_CFG_Enable_Auto_ReProcess_Frontstream) == 1) {
                if ($timesAttempted >= $numAttempts) {
                    $pledge->PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(2); //On Hold Non payment
                }
            } else {
                $pledge->PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(2); //Cancelled for payment method
            }
        }
        //_output($pledge);
        $pledge->save(RNCPHP\RNObject::SuppressAll);
    } catch (Exception $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."264 - reset date failure " . $e->getMessage();
        _output($e->getMessage());
        _output($pledge);
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        return false;
    }

    return true;
}

function runTransaction(array $postVals)
{
    global $logArray;
    //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912
    $host = cfg_get(CUSTOM_CFG_frontstream_endpoint);
    $pass = cfg_get(CUSTOM_CFG_FS_PW_CRON);    //cfg_get(CUSTOM_CFG_frontstream_pass_id);
    $user = cfg_get(CUSTOM_CFG_FS_UN_CRON);   //cfg_get(CUSTOM_CFG_frontstream_user_id);

    if (!verifyMinTransReqs($postVals, $host, $user, $pass)) {
        //$logArray[] = date("Y-m-d H:i:s  ")."103 - could not verify min trans request";
        return false;
    }

    $host .= "/" . $postVals['op'];

    $mybuilder = array();
    foreach ($postVals as $key => $value) {
        $mybuilder[] = $key . '=' . $value;
    }

    $mybuilder[] = 'username=' . $user;
    $mybuilder[] = 'password=' . $pass;

    $logArray[] = date("Y-m-d H:i:s  ")."my params";
    $logArray[] = date("Y-m-d H:i:s  ").print_r($mybuilder, true);

    $result = runCurl($host, $mybuilder);

    $logArray[] = date("Y-m-d H:i:s  ")."returned result ";
    $logArray[] = date("Y-m-d H:i:s  ").print_r($result, true);

    if ($result == false) {
        //_output("121 - Unable to run transaction");
        $logArray[] = date("Y-m-d H:i:s  ")."121 - Unable to run transaction";
        $logArray[] = date("Y-m-d H:i:s  ").$postVals;
        return false;
    }

    //$logArray[] = date("Y-m-d H:i:s  ")."238 - result after run transaction ", logWorker::Debug, $result);
    if ($postVals['op'] == "ArgoFire/transact.asmx/ProcessCheck") {
        $transType = ($postVals['op'] == "ArgoFire/transact.asmx/ProcessCheck") ? "check" : "credit";
    } else if ($postVals['op'] == "admin/ws/recurring.asmx/ProcessCheck") {
        $transType = ($postVals['op'] == "admin/ws/recurring.asmx/ProcessCheck") ? "check" : "credit";
    } else {
        $transType = ($postVals['op'] == "ArgoFire/transact.asmx/ProcessCheck") ? "check" : "credit";
    }


    return parseFrontStreamRespOneTime($result, $transType);
}
/**
 * Runs a curl POST call
 */
function runCurl($host, array $postData)
{
    global $logArray;
    try {
        // Initialize Curl and send the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            $logArray[] = date("Y-m-d H:i:s  ")."152 - could not verify min trans request";
            $logArray[] = date("Y-m-d H:i:s  ").print_r(curl_error($ch), true);
            curl_close($ch);
            return false;
        } else if (strpos($result, "HTTP Error") !== false) {
            $logArray[] = date("Y-m-d H:i:s  ")."157 - runCurl - http error";
            $logArray[] = date("Y-m-d H:i:s  ").print_r(curl_error($ch), true);
            curl_close($ch);
            return false;
        }
    } catch (Exception $e) {
        curl_close($ch);
        $logArray[] = date("Y-m-d H:i:s  ")."164 - " . $e->getMessage();
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        return false;
    }
    curl_close($ch);

    return $result;
}

function parseFrontStreamRespOneTime($result, $transType)
{
    global $logArray;
    //different return val for eft and cc

    $xmlparser = xml_parser_create();
    xml_parse_into_struct($xmlparser, $result, $values, $indices);
    xml_parser_free($xmlparser);

    $logArray[] = date("Y-m-d H:i:s  ")."283 - parseFrontStreamRespOneTime transtype =" . $transType;

    if ($transType == "credit") {
        //$logArray[] = date("Y-m-d H:i:s  ")."284 - response", logWorker::Debug, $response);
        $response['code'] = $values[$indices['RESULT'][0]]['value'];
        $response['auth'] = $values[$indices['PNREF'][0]]['value'];
        $response['error'] = $values[$indices['RESPMSG'][0]]['value'];
        $response['msg'] = $values[$indices['MESSAGE'][0]]['value'];
        //$logArray[] = date("Y-m-d H:i:s  ")."284 -  values ", logWorker::Debug, $values);
        //_output($response);
    } else if ($transType == "check") {
        //$logArray[] = date("Y-m-d H:i:s  ")."295 - check response", logWorker::Debug, $response);
        $response['code'] = $values[$indices['RESULT'][0]]['value'];
        $response['auth'] = $values[$indices['PNREF'][0]]['value'];
        $response['error'] = $values[$indices['MESSAGE'][0]]['value'];
        //$logArray[] = date("Y-m-d H:i:s  ")."298 -  check values ", logWorker::Debug, $values);
    }

    // $logArray[] = date("Y-m-d H:i:s  ")." values ";
    // $logArray[] = date("Y-m-d H:i:s  ").print_r($values, true);
    $logArray[] = date("Y-m-d H:i:s  ")." response ";
    $logArray[] = date("Y-m-d H:i:s  ").print_r($response, true);

    return $response;
}

function verifyMinTransReqs($postVals, $host, $user, $pass)
{
    global $logArray;
    //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912

    if (is_null($host) || strlen($host) < 1) {
        $logArray[] = date("Y-m-d H:i:s  ")."182 - Invalid host passed to runTransaction";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($postVals, true);
        return false;
    }
    if (is_null($user) || strlen($user) < 1) {
        $logArray[] = date("Y-m-d H:i:s  ")."186 - Invalid user passed to runTransaction";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($postVals, true);
        echo "";
        return false;
    }

    if (is_null($pass) || strlen($pass) < 1) {
        $logArray[] = date("Y-m-d H:i:s  ")."192 - Invalid password passed to runTransaction";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($postVals, true);
        echo "";
        return false;
    }
    if (is_null($postVals) || count($postVals) < 1) {
        $logArray[] = date("Y-m-d H:i:s  ")."197 - Invalid post values passed to runTransaction";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($postVals, true);
        echo "";
        return false;
    }
    if (is_null($postVals['op'])) {
        $logArray[] = date("Y-m-d H:i:s  ")."203 - Invalid operation.";
        $logArray[] = date("Y-m-d H:i:s  ").print_r($postVals, true);
        echo "";
        return false;
    }
    return true;
}

function createTransaction(RNCPHP\financial\paymentMethod $payMeth, $refnum, $totalcharge, $donation, $status, RNCPHP\Contact $contact, $notes, $trans)
{

    global $logArray;

    try {

        $trans->currentStatus = RNCPHP\financial\transaction_status::fetch($status);
        $trans->totalCharge = number_format($totalcharge, 2, '.', '');
        $trans->contact = $contact;
        $trans->refCode = $refnum;
        $trans->paymentMethod = $payMeth;

        if ($notes) {
            $trans->description = $notes;
        } else {
            $trans->description = "no note attached";
        }
        if ($donation) {
            $trans->donation = $donation;
        }

        $trans->save();

        return $trans->ID;
    } catch (\Exception $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."229 - Create Transaction Failed.". $e->getMessage();
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        return false;
    }
}

function createDonation($pledge, $totalToCharge)
{
    global $logArray;
    //create donation and donation2pledge

    try {
        $newDonation = new RNCPHP\donation\Donation;
        $newDonation->Contact = $pledge->Contact;
        $newDonation->DonationDate = time();
        $newDonation->Amount = number_format($totalToCharge, 2, '.', '');
        $newDonation->Type = RNCPHP\donation\Type::fetch(1);
        $newDonation->save(RNCPHP\RNObject::SuppressAll);
    } catch (Exception $e) {
        //logMessage($e -> getMessage());
        return 0;
    }

    $donation2Pledge = new RNCPHP\donation\donationToPledge();
    $donation2Pledge->PledgeRef = $pledge->ID;
    $donation2Pledge->DonationRef = $newDonation->ID;

    try {
        $donation2Pledge->save(RNCPHP\RNObject::SuppressAll);
        //logMessage($donation2Pledge);

    } catch (Exception $e) {
        log_message($e->getMessage());
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        $logArray[] = date("Y-m-d H:i:s  ")."API Error " . $e->getMessage();
        return false;
    }

    if ($newDonation->ID > 0)
        return $newDonation->ID;
    else
        return null;
}


function getCountPledgeDonations($pledgeId)
{
    global $logArray;
    try {
        $roql = sprintf(" SELECT donation.donationToPledge FROM donation.donationToPledge where donation.donationToPledge.PledgeRef.ID = %s", $pledgeId);
        $pages = RNCPHP\ROQL::queryObject($roql)->next();
        return ($pages->count());
    } catch (Exception $e) {
        return false;
    }
}
//forces lazy loading of array or object
function _getValues($parent)
{
    global $logArray;
    try {
        // $parent is a non-associative (numerically-indexed) array
        if (is_array($parent)) {

            foreach ($parent as $val) {
                _getValues($val);
            }
        }

        // $parent is an associative array or an object
        elseif (is_object($parent)) {

            while (list($key, $val) = each($parent)) {

                $tmp = $parent->$key;

                if ((is_object($parent->$key)) || (is_array($parent->$key))) {
                    _getValues($parent->$key);
                }
            }
        }
    } catch (exception $err) {
        // error but continue
    }
}


function _output($value)
{
    log_message($value);
    // _getValues($value);
    // echo "<pre>";
    // print_r($value);
    // echo "</pre><br/>";
}


function log_message($msg)
{
    //echo $msg."<br/>";
    $log = new RNCPHP\Log\LogMessage();
    $log->message = $msg;
    //$log->save();
}

function logToFile($logMessage){
    global $logArray;
    $logFile = fopen("/tmp/esgLogPayCron/".date("Y_m_d").".log", "a");
    fwrite($logFile, "\n\n");
    fwrite($logFile, "_______________");
    fwrite($logFile, print_r($logMessage, true));
    fwrite($logFile, "\n\n");
    fclose($logFile);
}