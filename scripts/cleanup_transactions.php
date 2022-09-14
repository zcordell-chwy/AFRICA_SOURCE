<?php

/**
 * 
 * Run this in linux shell
 * for var in {0..66}; do curl https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/cleanup_transactions.php -H 'HTTP_X_CUSTOM_AUTHORIZATION: emNvcmRlbGw6UGFzc3dvcmQx' >> cleanup_transactions.txt; done
 * */

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}
if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

require_once(DOCROOT . '/custom/utilities/credential_auth.php');
require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
require_once('include/init.phph');

use RightNow\Connect\v1_3 as RNCPHP;

load_curl();

try {
    $results = getReportResults(102015, 10);

    //pnref returned on the original charge, invoice/transaction number, amount
    //bixby: 1373971|1375158|1376257
    /*Array
(
    [code] => 0
    [auth] => 78664961
    [error] => Approved
)
|Array
(
    [code] => 0
    [auth] => 78695127
    [error] => Approved
)
|Array
(
    [code] => 0
    [auth] => 78699266
    [error] => Approved
)*/
    //ReversePayment(78695127, 1375158,"39.00");

} catch (Exception $e) {
    print_r($e);
} catch (RNCPHP\ConnectAPIError $err) {
    print_r($err);
}

/**
 * One time script that nulls out Member ID's that are equal to Zero
 * 
 * 
 * return RNCPHP\AnalyticsReportResult  
 */
function getReportResults($reportId = null, $recordLimit = 10) {

    if (!$reportId) {
        echo "failed";
        return;
    }

    $filters = new RNCPHP\AnalyticsReportSearchFilterArray;

    $ar = RNCPHP\AnalyticsReport::fetch($reportId);
    $arFetchResult = $ar->run(0, $filters, $recordLimit);

    $pledgeIdArr = array();
    while ($resultRow = $arFetchResult->next()) {
        //echo $resultRow['description']."\n";
        $transactionsArr = explode("|",$resultRow['description']);
        $transIdArr =  explode("|",$resultRow['TransIds']);
        $totalChargeArr =  explode("|",$resultRow['totalCharge']);
        $pledgeId = $resultRow['PledgeID'];
        $contactId = $resultRow['ContactID'];
        $payMethod = $resultRow['PayMethod'];
        

        for($i=0; $i < count($transactionsArr); $i++){
            //echo $trans."\n";
            $pnRef = trim(str_replace(array("\r", "\n"), '', get_string_between($transactionsArr[$i], "[auth] => ", "[error]")));
            $transId = $transIdArr[$i];

            //echo "82";
            if($i==0){
                $note = "Skipping Trans:$transId** with PnRef:$pnRef** on Pledge:$pledgeId** for Contact:$contactId** Amount: ".$totalChargeArr[$i]." from correction script\n";
                //$trans = setTransToApproved($transId,$note, 'Completed');
            }else{
                $fs_response = ReversePayment($pnRef, $transId, $totalChargeArr[$i], $payMethod);
                //print_r($fs_response);
                echo "\n";
                $note = "Reversing Trans:$transId** with PnRef:$pnRef** on Pledge:$pledgeId** for Contact:$contactId** Amount: ".$totalChargeArr[$i]." from correction script\n";
                $trans = setTransToApproved($transId, $note." \n\n\n ".print_r($fs_response,true), 'Reversed'); 
            }

            echo "$note";
        }
        $pledgeIdArr[] = $pledgeId;
        echo "--------\n\n";
    }

    return $pledgeIdArr;
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function setTransToApproved($transId, $noteContent, $setStatus = null){

    $trans = RNCPHP\financial\transactions::fetch(intval($transId));

    try {
        $f_count = count($trans->Notes);
        if ($f_count == 0) {
            $trans -> Notes = new RNCPHP\NoteArray();
        }
        $trans -> Notes[$f_count] = new RNCPHP\Note();
        $trans -> Notes[$f_count] -> Text = $noteContent;

        if($setStatus)
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch($setStatus);

    } catch (\Exception $e) {
        print_r($e->getMessage());
    } catch (RNCPHP\ConnectAPIError $e) {
        print_r($e->getMessage());
    }

    $trans->save();
}


//this method does not need an associated transaction.
//will be used to reverse small payment done to add a new payment method at /app/paymentmethods
function ReversePayment($pnref,$transId,$amount, $payMethod)
    {
        try {
            if ($pnref) {

                if($payMethod == 'Credit Card'){
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => RNCPHP\Configuration::fetch(CUSTOM_CFG_FS_PW_CRON)->Value,
                        'UserName' => RNCPHP\Configuration::fetch(CUSTOM_CFG_FS_UN_CRON)->Value,
                        'TransType' => 'Return',
                        'PNRef' => $pnref,
                        'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                        'MagData' => '',
                        'ExtData' => '',
                        'CardNum' => '',
                        'ExpDate' => '',
                        'CVNum' => '',
                        'InvNum' => $transId,
                        'NameOnCard' => '',
                        'Zip' => '',
                        'Street' => ''
                    );
                } else{
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => RNCPHP\Configuration::fetch(CUSTOM_CFG_FS_PW_CRON)->Value,
                        'UserName' => RNCPHP\Configuration::fetch(CUSTOM_CFG_FS_UN_CRON)->Value,
                        'TransType' => 'Return',
                        'PNRef' => $pnref,
                        'op' => "ArgoFire/transact.asmx/ProcessCheck",
                        'ExtData' => '<PNRef>' . $pnref . '</PNRef>',
                        'CheckNum' => '',
                        'TransitNum' => '',
                        'AccountNum' => '',
                        'MICR' => '',
                        'NameOnCheck' => '',
                        'DL' => '',
                        'SS' => '',
                        'DOB' => '',
                        'StateCode' => '',
                        'CheckType' => '',
                        'InvNum' => $transId
                    );
                }
            } else {
                $endUserError[] = "No valid PNRef was supplied to reverse the charge.";
                return false;
            }

            //creating a dummy transaction just to get it to pass through runTransaction and parseFrontstreamResp
            //$trans = new RNCPHP\financial\transactions;

            //$returnVal = $this->runTransaction($submissionVals, $trans);

            $host = RNCPHP\Configuration::fetch(CUSTOM_CFG_frontstream_endpoint)->Value;

            $host .= "/" . $submissionVals['op'];

            $mybuilder = array();
            foreach ($submissionVals as $key => $value) {
                $mybuilder[] = $key . '=' . $value;
            }

            //print_r($mybuilder);


            $result = runCurl($host, $mybuilder);

            //print_r($result);

            if ($result == false) {
                $endUserError[] = "Unable to run payment";
                return false;
            }

            $parsedResponse = parseFrontstreamResp($result);

            //print_r($parsedResponse);

            if ($parsedResponse['isSuccess']) {
                return $parsedResponse;
            } else {
                $endUserError[] = "The payment was declined: ";
                $endUserError[] = $parsedResponse['message'] . "  " . $parsedResponse['responseMsg'];
                print_r($parsedResponse);
            }

        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

/**
     * Runs a curl POST call
     */
function runCurl($host, array $postData){
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
        //"Front Stream Curl Response : " . print_r($result, true));
        

        if (curl_errno($ch) > 0) {
            $errorMessage[] = curl_error($ch);
            $endUserError[] = RNCPHP\Configuration::fetch(CUSTOM_CFG_general_cc_error_id)->Value;
            curl_close($ch);
            return false;
        } else if (strpos($result, "HTTP Error") !== false) {
            
            curl_close($ch);
            $endUserError[] = RNCPHP\Configuration::fetch(CUSTOM_CFG_general_cc_error_id)->Value;
            return false;
        }
    } catch (\Exception $e) {
        curl_close($ch);
        $errorMessage[] = $e->getMessage();
        $endUserError[] = RNCPHP\Configuration::fetch(CUSTOM_CFG_general_cc_error_id)->Value;
        return false;
    }
    curl_close($ch);
    return $result;
}

/*
<?xml version="1.0" encoding="utf-8"?>
<Response xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns="http://TPISoft.com/SmartPayments/">
	<Result>0</Result>
	<Message>Pending</Message>
	<PNRef>79582822</PNRef>
	<ExtData>&lt;BatchNumber&gt;919&lt;/BatchNumber&gt;</ExtData>
</Response>
*/

function parseFrontstreamResp($result){
        $xmlparser = xml_parser_create();
        xml_parse_into_struct($xmlparser, $result, $values, $indices);
        xml_parser_free($xmlparser);

        $frontStreamResponse = array();
        $frontStreamResponse['resultCode'] = $values[$indices['RESULT'][0]]['value'];
        $frontStreamResponse['responseMsg'] = $values[$indices['RESPMSG'][0]]['value'];
        $frontStreamResponse['message'] = $values[$indices['MESSAGE'][0]]['value'];
        $frontStreamResponse['pnRef'] = $values[$indices['PNREF'][0]]['value'];
        $frontStreamResponse['code'] = $values[$indices['CODE'][0]]['value'];
        $frontStreamResponse['error'] = $values[$indices['ERROR'][0]]['value'];

        if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['error'] == "APPROVED") { //cc - with ccinfokey
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['message'] == "Pending") { // Added this check for by passing the Check transactions with Pending status
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['responseMsg'] == "Approved") { //exception with pnref
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0) { //assuming all is well here
            $frontStreamResponse['isSuccess'] = true;
        } else {
            $frontStreamResponse['isSuccess'] = false;
        }

        $frontStreamResponse['rawXml'] = $result;
        $frontStreamResponse['parsedXml'] = $values;

        return $frontStreamResponse;
    }
