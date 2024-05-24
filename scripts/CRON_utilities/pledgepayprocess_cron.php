<?php

//Author: Zach Cordell
//Date: 5/1/15
//Purpose: cron utility will be run every 1 time per day.  Will process pledges that are due to be run today

ini_set('display_errors', 'Off');
error_reporting(0);

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$ip_dbreq = true;
require_once('include/init.phph');

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");

use RightNow\Connect\v1_2 as RNCPHP;

initConnectAPI('cron_042022_user', 'x&w4iA712');

load_curl();
require_once("/cgi-bin/africanewlife.cfg/scripts/custom/Log/esglogpaycron-v2.0.php");

$fileLogger = new fileLogger(true, fileLogger::All);  //file logger is a log worker
$logger = esgLogger::getInstance();
$logger->registerLogWorker($fileLogger);  //register the log worker with the esgLogger event bus singleton
esgLogger::log("Begin Pledge Processing...", logWorker::Debug);


//run analytics report and process payments.
$ar = RNCPHP\AnalyticsReport::fetch(100228);
$arr = $ar->run();
for ($ii = $arr->count(); $ii--;) {
    $row = $arr->next();

    $pledgeID = $row['Pledge ID'];
    $pledgeAmt = $row['Pledge Amount'];
    $payMeth = ($row['Payment Method Type'] == "Credit Card") ? 1 : 2;
    $balance = $row['Balance'];
    $firstTimeDonationCredit = $row['firstTimeDonationCredit'];
    esgLogger::log("Memory Usage:" . memory_get_usage() . "  Peak Memory Usage: " . memory_get_peak_usage(), logWorker::Debug);
    Initialize($pledgeID, $pledgeAmt, $payMeth, $balance, $firstTimeDonationCredit);
}

function Initialize($pledgeID, $pledgeAmt, $payMeth, $balance, $firstTimeDonationCredit)
{
    /* Removed balance b/c of unallocated solution ZC 10/25
    esgLogger::log("balance before = ".$balance, logWorker::Debug);
    if ($balance > 0){
    }else{
        $balance = 0;
    }
    esgLogger::log("Balance = ".$balance, logWorker::Debug);
    */

    //if its the first donation toward a pledge and they have a first time donation credit
    //this happens when a co spon pledge is created and they have a credit for upping their pledge. 
    //this will only happen to quarterly or yearly pledges
    if (getCountPledgeDonations($pledgeID) == 0 && $firstTimeDonationCredit > 0)
        $totalToCharge = $pledgeAmt - $firstTimeDonationCredit;
    else {
        $totalToCharge = $pledgeAmt;
    }

    esgLogger::log("totalTocharge = " . $totalToCharge . " pledge amount = " . $pledgeAmt, logWorker::Debug);

    if ($totalToCharge > 0) { //we have something to charge
        if ($pledgeAmt > 0  && $payMeth > 0) {
            try {

                $pledge = RNCPHP\donation\pledge::fetch($pledgeID);
                //0-none 1-cc 2-eft
                esgLogger::log("Processing pledge ID = " . $pledgeID, logWorker::Debug);
                $result = ($pledge->paymentMethod2->PaymentMethodType->ID == "1") ? processCCpayment($pledge, $totalToCharge) : processEFTpayment($pledge, $totalToCharge);

                if ($result > 0) {
                    //reset the pledge to the next transaction date. Reset # of attempts to 0
                    resetNextPayment($pledge, true);
                } else {
                    //reset the pledge set next trans date to tomorrow. Increment # of attempts
                    esgLogger::log("46 - processCCpayment returned bad result", logWorker::Debug);
                    //_output("46 - processCCpayment returned bad result");
                    resetNextPayment($pledge, false);
                }
            } catch (Exception $e) {
                esgLogger::log("PHP Error " . $e->getMessage(), logWorker::Debug, $e->getTrace());
            } catch (RNCPHP\ConnectAPIError $e) {
                esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
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
            esgLogger::log($e->getMessage(), logWorker::Debug);
            return 0;
        } catch (RNCPHP\ConnectAPIError $e) {
            esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
        }

        $donation2Pledge = new RNCPHP\donation\donationToPledge();
        $donation2Pledge->PledgeRef = $pledge->ID;
        $donation2Pledge->DonationRef = $newDonation->ID;

        try {
            $donation2Pledge->save(RNCPHP\RNObject::SuppressAll);
            //logMessage($donation2Pledge);
        } catch (Exception $e) {
            esgLogger::log($e->getMessage(), logWorker::Debug);
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
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
            esgLogger::log($e->getMessage(), logWorker::Debug);
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
            return false;
        }
    }

    RNCPHP\ConnectAPI::commit();
}

//update payment result, if successful update next payment due date and create transaction.
//if not sucessful, try again tomorrow
//  if not successfult 3 times in a row,  put in 'pledge on hold' status set next payment date to null.

function processCCpayment(RNCPHP\donation\pledge $pledge, $totalToCharge)
{
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
        esgLogger::log("87 - frontstream failure ", logWorker::Debug, $returnValues);
        //dont need to create donation or d2p if declined
        createTransaction($pledge->paymentMethod2, null, $totalToCharge, createDonation($pledge, $totalToCharge), "Declined", $pledge->Contact, $notes, $trans);
        $transID = -99; //set to negative so we know to not to reset the next pledge date to tomorrow instead of calculating based on frequency
    } else {
        esgLogger::log("84 - frontstream success ", logWorker::Debug, $returnValues);
        //create donation and associate it to existing pledge
        //then create transaction and associate it to existing donation
        $transID = createTransaction($pledge->paymentMethod2, $returnValues['auth'], $totalToCharge, createDonation($pledge, $totalToCharge), "Completed", $pledge->Contact, $notes, $trans);
    }


    return $transID;
}

function processEFTpayment(RNCPHP\donation\pledge $pledge, $totalToCharge)
{

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
        esgLogger::log("87 - frontstream failure ", logWorker::Debug, $returnValues);
        createTransaction($pledge->paymentMethod2, null, $totalToCharge, createDonation($pledge, $totalToCharge), "Declined", $pledge->Contact, $notes, $trans);
        $transID = -99; //set to negative so we know to not to reset the next pledge date to tomorrow instead of calculating based on frequency
    } else {
        esgLogger::log("84 - frontstream success ", logWorker::Debug, $returnValues);
        $transID = createTransaction($pledge->paymentMethod2, $returnValues['auth'], $totalToCharge, createDonation($pledge, $totalToCharge), "Completed", $pledge->Contact, $notes, $trans);
    }

    return $transID;
}

function resetNextPayment($pledge, $goodTrans)
{

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
        esgLogger::log("264 - reset date failure " . $e->getMessage(), logWorker::Debug);
        _output($e->getMessage());
        _output($pledge);
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
        return false;
    }

    return true;
}

function runTransaction(array $postVals)
{
    //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912
    $host = cfg_get(CUSTOM_CFG_frontstream_endpoint);
    $pass = cfg_get(CUSTOM_CFG_FS_PW_CRON);    //cfg_get(CUSTOM_CFG_frontstream_pass_id);
    $user = cfg_get(CUSTOM_CFG_FS_UN_CRON);   //cfg_get(CUSTOM_CFG_frontstream_user_id);

    if (!verifyMinTransReqs($postVals, $host, $user, $pass)) {
        //esgLogger::log("103 - could not verify min trans request", logWorker::Debug, $postVals);
        return false;
    }

    $host .= "/" . $postVals['op'];

    $mybuilder = array();
    foreach ($postVals as $key => $value) {
        $mybuilder[] = $key . '=' . $value;
    }

    $mybuilder[] = 'username=' . $user;
    $mybuilder[] = 'password=' . $pass;

    esgLogger::log("my params", logWorker::Debug, $mybuilder);

    $result = runCurl($host, $mybuilder);

    esgLogger::log("returned result ", logWorker::Debug, $result);

    if ($result == false) {
        //_output("121 - Unable to run transaction");
        esgLogger::log("121 - Unable to run transaction", logWorker::Debug, $postVals);
        return false;
    }

    //esgLogger::log("238 - result after run transaction ", logWorker::Debug, $result);
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
            esgLogger::log("152 - could not verify min trans request", logWorker::Debug, curl_error($ch));
            curl_close($ch);
            return false;
        } else if (strpos($result, "HTTP Error") !== false) {
            esgLogger::log("157 - runCurl - http error", logWorker::Debug, curl_error($ch));
            curl_close($ch);
            return false;
        }
    } catch (Exception $e) {
        curl_close($ch);
        esgLogger::log("164 - " . $e->getMessage(), logWorker::Debug);
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
        return false;
    }
    curl_close($ch);

    return $result;
}

function parseFrontStreamRespOneTime($result, $transType)
{
    //different return val for eft and cc

    $xmlparser = xml_parser_create();
    xml_parse_into_struct($xmlparser, $result, $values, $indices);
    xml_parser_free($xmlparser);

    esgLogger::log("283 - parseFrontStreamRespOneTime transtype =" . $transType, logWorker::Debug);

    if ($transType == "credit") {
        //esgLogger::log("284 - response", logWorker::Debug, $response);
        $response['code'] = $values[$indices['RESULT'][0]]['value'];
        $response['auth'] = $values[$indices['PNREF'][0]]['value'];
        $response['error'] = $values[$indices['RESPMSG'][0]]['value'];
        $response['msg'] = $values[$indices['MESSAGE'][0]]['value'];
        //esgLogger::log("284 -  values ", logWorker::Debug, $values);
        //_output($response);
    } else if ($transType == "check") {
        //esgLogger::log("295 - check response", logWorker::Debug, $response);
        $response['code'] = $values[$indices['RESULT'][0]]['value'];
        $response['auth'] = $values[$indices['PNREF'][0]]['value'];
        $response['error'] = $values[$indices['MESSAGE'][0]]['value'];
        //esgLogger::log("298 -  check values ", logWorker::Debug, $values);
    }

    esgLogger::log(" values ", logWorker::Debug, $values);
    esgLogger::log(" response ", logWorker::Debug, $response);

    return $response;
}

function verifyMinTransReqs($postVals, $host, $user, $pass)
{
    //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912

    if (is_null($host) || strlen($host) < 1) {
        esgLogger::log("182 - Invalid host passed to runTransaction", logWorker::Debug, $postVals);
        return false;
    }
    if (is_null($user) || strlen($user) < 1) {
        esgLogger::log("186 - Invalid user passed to runTransaction", logWorker::Debug, $postVals);
        echo "";
        return false;
    }

    if (is_null($pass) || strlen($pass) < 1) {
        esgLogger::log("192 - Invalid password passed to runTransaction", logWorker::Debug, $postVals);
        echo "";
        return false;
    }
    if (is_null($postVals) || count($postVals) < 1) {
        esgLogger::log("197 - Invalid post values passed to runTransaction", logWorker::Debug, $postVals);
        echo "";
        return false;
    }
    if (is_null($postVals['op'])) {
        esgLogger::log("203 - Invalid operation.", logWorker::Debug, $postVals);
        echo "";
        return false;
    }
    return true;
}

function createTransaction(RNCPHP\financial\paymentMethod $payMeth, $refnum, $totalcharge, $donation, $status, RNCPHP\Contact $contact, $notes, $trans)
{



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
        esgLogger::log("229 - Create Transaction Failed.", logWorker::Debug, $e->getMessage());
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
        return false;
    }
}

function createDonation($pledge, $totalToCharge)
{

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
        logMessage($e->getMessage());
        return false;
    } catch (RNCPHP\ConnectAPIError $e) {
        esgLogger::log("API Error " . $e->getMessage(), logWorker::Debug);
        return false;
    }

    if ($newDonation->ID > 0)
        return $newDonation->ID;
    else
        return null;
}


function getCountPledgeDonations($pledgeId)
{

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
    _getValues($value);
    echo "<pre>";
    print_r($value);
    echo "</pre><br/>";
}


function log_message($msg)
{
    //echo $msg."<br/>";
    $log = new RNCPHP\Log\LogMessage();
    $log->message = $msg;
    //$log->save();
}
