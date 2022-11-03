<?php

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
require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';


$tstfile = fopen("/tmp/testfile.txt", "a") or die("Unable to open file!");
fwrite($tstfile, "getbadchecks- ".date("Y/m/d h:i:s")." - IP:  $ip  \n");
fclose($tstfile);

load_curl();
require_once("/cgi-bin/africanewlife.cfg/scripts/custom/Log/esglogbadchecks-v2.0.php");

$fileLogger = new fileLogger(true, fileLogger::All);  //file logger is a log worker
$logger = esgLogger::getInstance();
$logger -> registerLogWorker($fileLogger);  //register the log worker with the esgLogger event bus singleton
esgLogger::log("Begin Pledge Processing...", logWorker::Debug);

$returnArray =  array();

$pass = cfg_get(CUSTOM_CFG_FS_PW_CRON);
$user = cfg_get(CUSTOM_CFG_FS_UN_CRON);
$merchKey = cfg_get(CUSTOM_CFG_frontstream_vendor);

 $mytx = array(
         'UserName' => $user,
         'Password' => $pass,
         'MerchantKey' => $merchKey,
         'BeginDt' => date("m/d/Y", strtotime("-1 days")),
         'EndDt' => date("m/d/Y", time()),
        //  'BeginDt' => date("m/d/Y", strtotime("Sept 14, 2022")),
        //  'EndDt' => date("m/d/Y", strtotime("Sept 15, 2022")),
         'ExtData' => "",
         'op' => "admin/ws/trxdetail.asmx/GetReturnedCheckReport"
     );

 $returnValues = runTransaction($mytx);
 //$returnValues is either a list of transactions id's or false
 //_output($returnValues);

 //$returnValues[] = 439900;

 if($returnValues){//should be false if no items received
     foreach($returnValues as $transArray){
         processBadCheck($transArray);
     }
 }else{
    $returnArray[] =  "No Returned Checks";
 }
    
return outputResponse($returnArray, null);


function processBadCheck($transArray){
    
    
    //set the transaction to reversed  id 8
    try{
        //old transactions posted by denari can look like this 394EAE0F6A:394EAE0F6A::765B2BAE30:024568
        //in this case ::fetch will actually return transactin 394.  so check for it and don't run if it does.
        
        if (strpos($transArray['INVOICE_ID'],':') === false){
            esgLogger::log("Fetching ".$transArray['INVOICE_ID'], logWorker::Debug);
            $trans = RNCPHP\financial\transactions::fetch($transArray['INVOICE_ID']);
            if ($trans->ID > 0){
                esgLogger::log( " Date equals =  ". date("Ymd", $trans -> CreatedTime)."  = ". date("Ymd", strtotime($transArray['AUTHDATE'])), logWorker::Debug);
                
                //check to make sure the dates are the same on teh transaction.  Sometimes the test site will use teh 
                //frontstream account and duplicate the trans id's
                //if ( date("Ymd", $trans -> CreatedTime) == date("Ymd", strtotime($transArray['AUTHDATE']))){
                    
                    $trans -> currentStatus = RNCPHP\financial\transaction_status::fetch(8);//reversed.
                    $trans->save();
                    esgLogger::log("Resetting transaction ".$trans->ID." to reversed", logWorker::Debug);
                    _resetPledges($trans->donation->ID);
                    
                // }else{
                //     esgLogger::log("Trans ID's matched but dates did not", logWorker::Debug);
                // }
                
            }else{
                esgLogger::log("Didn't find transaction". $transArray['INVOICE_ID'], logWorker::Debug);
            }
        }else{
            esgLogger::log("Can't process denari transaction ". $transArray['INVOICE_ID'], logWorker::Debug);
        }
 
    }catch (Exception $e){
        esgLogger::log("Error - ".$e->getMessage(), logWorker::Debug);
    }
    
}

function _resetPledges($donationID){
    $roql = "SELECT donation.donationToPledge FROM donation.donationToPledge WHERE donation.donationToPledge.DonationRef = $donationID ";
    
    $res = RNCPHP\ROQL::queryObject( $roql )->next();
    while($d2p = $res->next()){
        
        $pledge = RNCPHP\donation\pledge::fetch($d2p->PledgeRef->ID);
        if($pledge){
            esgLogger::log("Resetting Pledge ".$pledge->ID." to On Hold Non Payment", logWorker::Debug);
            $pledge->PledgeStatus = RNCPHP\donation\PledgeStatus::fetch(2);//on hold non payment.
            $pledge->save();
        }
        
    }
}


/**
*
* 
*
*/
function runTransaction(array $postVals) {
   //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912

   $host = cfg_get(CUSTOM_CFG_frontstream_endpoint);
   $host .= "/" . $postVals['op'];

   $mybuilder = array();
   foreach ($postVals as $key => $value) {
       $mybuilder[] = $key . '=' . $value;
   }
   
   esgLogger::log(print_r($mybuilder, true), logWorker::Debug);

   $result = runCurl($host, $mybuilder);
   esgLogger::log($result, logWorker::Debug);
   $returnArray[] = ($result);
   //print_r($result);
   
   if ($result == false) {
       esgLogger::log("121 - Unable to run transaction", logWorker::Debug);
       return false;
   }

   return parseFrontStreamRespOneTime($result, $transType);


}

function runCurl($host, array $postData) {
   try {
       
       //_output($postData);
       //_output($host);
       // Initialize Curl and send the request

       esgLogger::log(implode("&", $postData), logWorker::Debug);
       esgLogger::log($host, logWorker::Debug);

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $host);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postData));
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       $result = curl_exec($ch);

       if (curl_errno($ch) > 0) {
           esgLogger::log("152 - could not verify min trans request", logWorker::Debug);
           esgLogger::log(curl_error($ch), logWorker::Debug);
           //_output(curl_error($ch));
           curl_close($ch);
           return false;
       } else if (strpos($result, "HTTP Error") !== false) {
           esgLogger::log("157 - runCurl - http error", logWorker::Debug);
           esgLogger::log(curl_error($ch), logWorker::Debug);
           curl_close($ch);
           return false;
       }
   } catch(Exception $e) {
       curl_close($ch);
       esgLogger::log($e -> getMessage(), logWorker::Debug);
       return false;
   }
   curl_close($ch);
   return $result;
}

function parseFrontStreamRespOneTime($result, $transType) {
    //different return val for eft and cc
    
    $xmlparser = xml_parser_create();
    xml_parse_into_struct($xmlparser, $result, $values, $indices);
    xml_parser_free($xmlparser);
    $response = $values[$indices['RESULTCODE'][0]]['value'];
    //_output($response);
    
    $x = 0;
    if ($response == 0){
        //get all the invoice id's from the return
        foreach($values as $value){
            if($value['tag'] == "INVOICE_ID"){
                $transactionids[$x]['INVOICE_ID'] = $value['value']; 
            }
            
            if($value['tag']== "AUTH_DATE"){
                $transactionids[$x]['AUTHDATE'] = $value['value'];
                $x++;
            }
            
            
        }
        esgLogger::log("transaction IDS LIST", logWorker::Debug);
        esgLogger::log(print_r($transactionids, true), logWorker::Debug);
        //_output($transactionids);
        return $transactionids;
    }
    
    
    return false;
    
    //_output($values);
    
}

//forces lazy loading of array or object
function _getValues($parent) {
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

function _output($value){
    _getValues($value);
    echo "<pre>";
    print_r($value);
    echo "</pre><br/>";
}


function log_message($msg){
    //echo $msg."<br/>";
    $log = new RNCPHP\Log\LogMessage();
    $log->message = $msg;
    //$log->save();
}
?>