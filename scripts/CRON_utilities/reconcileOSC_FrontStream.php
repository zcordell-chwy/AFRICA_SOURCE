<?php

//Author: Zach Cordell
//Date: 04/25/16



ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$ip_dbreq = true;
require_once('include/init.phph');

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');

load_curl();

if($_GET['NumDaysAgo'] > 0){
    define('FROM_DATE', strtotime("-".$_GET['NumDaysAgo']." day"));
    define('TO_DATE', strtotime("-".$_GET['NumDaysAgo']." day"));
}else{
    define('FROM_DATE', strtotime("midnight -2 days"));
    define('TO_DATE', strtotime("midnight -1 day"));
}

define('PASS', cfg_get(CUSTOM_CFG_frontstream_pass));
define('USER', cfg_get(CUSTOM_CFG_frontstream_user));
define('MERCH_KEY', cfg_get(CUSTOM_CFG_merchant_key));


$mytx = array(
    'UserName' => USER,
    'Password' => PASS,
    'RPNum' => MERCH_KEY,
    'BeginDt' => date('Y-m-d\T08:00:00', FROM_DATE),
    'EndDt' => date('Y-m-d\T08:00:00', TO_DATE),
    'ExtData' => "",
    'PNRef' => "",
    'PaymentType' => "",
    'ExcludePaymentType' => "",
    'TransType' => "",
    'ExcludeTransType' => "",
    'ApprovalCode' => "",
    'Result' => "",
    'ExcludeResult' => "",
    'NameOnCard' => "",
    'CardNum' => "",
    'CardType' => "",
    'ExcludeCardType' => "",
    'User' => "",
    'InvoiceId' => "",
    'SettleFlag' => "",
    'SettleMsg' => "",
    'SettleDt' => "",
    'TransformType' => "",
    'Xsl' => "",
    'ColDelim' => "",
    'RowDelim' => "",
    'IncludeHeader' => "",
    'ExcludeVoid' => 'FALSE',
    'op' => "admin/ws/trxdetail.asmx/GetCardTrx"
);

$mytx2 = array(
    'Amount' => '',
    'Password' => PASS,
    'UserName' => USER,
    'RPNum' => MERCH_KEY,
    'TransType' => '',
    'op' => "admin/ws/trxdetail.asmx/GetCheckTrx",
    'BeginDt' => date('Y-m-d\T08:00:00', FROM_DATE),
    'EndDt' => date('Y-m-d\T08:00:00', TO_DATE),
    'CheckNum' => '',
    'TransitNum' => '',
    'AcctNum' => '',
    'NameOnCheck' => '',
    'MICR' => '',
    'DL' => '',
    'SS' => '',
    'DOB' => '',
    'StateCode' => '',
    'CheckType' => '',
    'ExtData' => '',
    'PNRef' => '',
    'PaymentType' => '',
    'ExcludePaymentType' => '',
    'ExcludeTransType' => '',
    'ExcludeVoid' => 'FALSE',
    'ApprovalCode' => '',
    'Result' => '',
    'ExcludeResult' => '',
    'RouteNum' => '',
    'User' => '',
    'InvoiceId' => '',
    'SettleFlag' => '',
    'SettleMsg' => '',
    'SettleDt' => '',
    'TransformType' => '',
    'Xsl' => '',
    'ColDelim' => '',
    'RowDelim' => '',
    'IncludeHeader' => ''


);

//_output($mytx);
//_output($mytx2);


$FSCardArray =  runTransaction($mytx);
$FSCheckArray = runTransaction($mytx2);
$OCSTransArray = getTransArray();


//_output($FSCardArray);
//_output($FSCheckArray);
//_output($OCSTransArray);

//checking for same status, same amount, existence, all NewPM transactions in FS have a corresponding reversal.

_checkNewPMTransactions($FSCardArray['RICHDBDS'], $FSCheckArray['RICHDBDS']);
_checkTransactionStatus($OCSTransArray, $FSCardArray['RICHDBDS'], $FSCheckArray['RICHDBDS']);

function _checkNewPMTransactions($FSCardArray, $FSCheckArray){



    foreach($FSCardArray as $FSTransaction){

        if (strpos($FSTransaction['INVOICE_ID'], "NewPM-") === 0){

            $key = searcharray($FSTransaction['TRX_HD_KEY'], 'ORIG_TRX_HD_KEY', $FSCardArray);

            if (!$key  && $FSTransaction['RESULT_TXT_VC'] != 'DECLINED'){
                _writeError("Possible Credit Card failure to refund a new payment method - Check Frontstream ", $FSTransaction['INVOICE_ID'], $FSTransaction['DATE_DT']);
            }

        }

    }

    foreach($FSCheckArray as $FSTransaction){


        if (strpos($FSTransaction['INVOICE_ID'], "NewPM-") === 0){

            $key = searcharray($FSTransaction['TRX_HD_KEY'], 'ORIG_TRX_HD_KEY', $FSCheckArray);

            //if there is no CheckArray entry, if the entry was one for a returned check, or, if the entry is somethign other than a void, write the error.
            if (!$key  && $FSTransaction['RESULT_TXT_VC'] != 'DECLINED' && $FSCheckArray[$key]['TRANS_TYPE_ID'] != "Void"){
                _writeError("Possible EFT failure to refund a new payment method - Check Frontstream ", $FSTransaction['INVOICE_ID'], $FSTransaction['DATE_DT']);
            }

        }

    }

    return true;
}

function _checkTransactionStatus($OCSTransArray, $FSCardArray, $FSCheckArray){

    //_output($FSCardArray);
    $count = 0;
    foreach($FSCardArray as &$FSTransaction){

        if (strpos(trim($FSTransaction['INVOICE_ID']), "NewPM-") === FALSE && $FSTransaction['INVOICE_ID']){


            $key = searcharray($FSTransaction['INVOICE_ID'], 'ID', $OCSTransArray);


//if Result_CH = 0

            if($key !== null ){


                //_output($OCSTransArray);
                 if($FSTransaction['INVOICE_ID'] == 937752){
                    echo " Our test: <br/>";
                    echo trim($FSTransaction['TRANS_TYPE_ID'])."--".trim($FSTransaction['RESULT_CH'])."--". $OCSTransArray[$key]["LookupName"]."<br/>";
                    if(trim($FSTransaction['TRANS_TYPE_ID']) == "Reversal" && trim($FSTransaction['RESULT_CH']) == 0 && $OCSTransArray[$key]["LookupName"] == "Reversed"){
                        echo "We passed! <br/>";
                    }
                 }
                 //completed
                 if(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && trim($FSTransaction['RESULT_CH']) == 0 && $OCSTransArray[$key]["LookupName"] == "Completed"){
                     //echo "completed trans match<br/>"; //SUCCESS
                 }elseif((trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || trim($FSTransaction['RESULT_CH']) != 0) && $OCSTransArray[$key]["LookupName"] == "Completed"){
                   _writeError("Could not match Completed OSC status with Frontstream ID  ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && trim($FSTransaction['RESULT_CH']) != 0 && $OCSTransArray[$key]["LookupName"] == "Declined"){
                   //SUCCESS
                 }elseif((trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || trim($FSTransaction['RESULT_CH']) == 0) && $OCSTransArray[$key]["LookupName"] == "Declined"){
                   _writeError("Could not match Declined OSC status with Frontstream ID  ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Reversal" && trim($FSTransaction['RESULT_CH']) == 0 && $OCSTransArray[$key]["LookupName"] == "Reversed"){
                    $refundedkey = searcharray($FSTransaction['ORIG_TRX_HD_KEY'], 'TRX_HD_KEY', $FSCardArray);
                    if($refundedkey >= 0){
                        echo "unsetting fscardarray ".$refundedkey."<br/>";
                        unset($FSCardArray[$refundedkey]);
                    }else{
                        _writeError("Found Reversal Transaction ".$FSTransaction['INVOICE_ID']." but no corresponding transaction in FrontStream", $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                    }
                    //echo "reversed trans match<br/>";
                 //refunded
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Credit" && trim($FSTransaction['RESULT_CH']) == 0 && $OCSTransArray[$key]["LookupName"] == "Refunded"){
                 //refunded in Oracle but the original sale transaction comes up in teh card array
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && trim($FSTransaction['RESULT_CH']) == 0 && $OCSTransArray[$key]["LookupName"] == "Refunded"){
                 //we'll just ignore it.  we'll verify when the refunded transaction comes up
                 //pending agent or web because of decline
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && trim($FSTransaction['RESULT_CH']) != 0 && ( $OCSTransArray[$key]["LookupName"] == "Pending - Agent Initiated" || $OCSTransArray[$key]["LookupName"] == "Pending - Web Initiated") ){
                    //echo "refunded trans match<br/>";
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || trim($FSTransaction['RESULT_CH']) != 0 && ( $OCSTransArray[$key]["LookupName"] == "Pending - Agent Initiated" || $OCSTransArray[$key]["LookupName"] == "Pending - Web Initiated") ){
                     _writeError("Could not match Pending OSC status with Frontstream ID ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }else{
                     _writeError("Could not match status for ".$OCSTransArray[$key]['LookupName'] ."".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }

            }else{

                _writeError("Transaction Missing from OCS ".$FSTransaction['INVOICE_ID']."", $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
            }



        }

    }

    //Check
    foreach($FSCheckArray as &$FSTransaction){

        if (strpos(trim($FSTransaction['INVOICE_ID']), "NewPM-") === FALSE && $FSTransaction['INVOICE_ID']){


            $key = searcharray($FSTransaction['INVOICE_ID'], 'ID', $OCSTransArray);
            //echo "key = ".$key."<br/>";

            if($key !== null ){

                 if(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && (strpos($FSTransaction['RESULT_TXT_VC'], "APPROVED:") !== false) && $OCSTransArray[$key]["LookupName"] == "Completed"){
                     //echo "completed trans match<br/>"; //SUCCESS
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || (strpos($FSTransaction['RESULT_TXT_VC'], "APPROVED:") !== false)  && $OCSTransArray[$key]["LookupName"] == "Completed"){
                   _writeError("Could not match Completed OSC status with Frontstream Invoice ID  ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && $FSTransaction['RESULT_TXT_VC'] == "DECLINED" && $OCSTransArray[$key]["LookupName"] == "Declined"){
                   //SUCCESS
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || $FSTransaction['RESULT_TXT_VC'] != "DECLINED" && $OCSTransArray[$key]["LookupName"] == "Declined"){
                   _writeError("Could not match Declined OSC status with Frontstream Invoice ID ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Reversal" && $FSTransaction['RESULT_TXT_VC'] == "APPROVED" && $OCSTransArray[$key]["LookupName"] == "Reversed"){
                    $refundedkey = searcharray($FSTransaction['ORIG_TRX_HD_KEY'], 'TRX_HD_KEY', $FSCardArray);
                    if($refundedkey >= 0){
                        //echo "unsetting fscardarray ".$refundedkey."<br/>";
                        unset($FSCardArray[$refundedkey]);
                    }else{
                        _writeError("Found Reversal Transaction ".$FSTransaction['INVOICE_ID']." but no corresponding transaction in FrontStream", $FSTransaction['TRX_HD_KEY'],  date('m/d/Y H:m',$FSTransaction['DATE_DT']));
                    }
                    //echo "reversed trans match<br/>";
                 //refunded
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Credit" && $FSTransaction['RESULT_TXT_VC'] == "APPROVED" && $OCSTransArray[$key]["LookupName"] == "Refunded"){
                 //refunded in Oracle but the original sale transaction comes up in teh card array
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && $FSTransaction['RESULT_TXT_VC'] == "APPROVED" && $OCSTransArray[$key]["LookupName"] == "Refunded"){
                 //we'll just ignore it.  we'll verify when the refunded transaction comes up
                 //pending agent or web because of decline
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) == "Sale" && $FSTransaction['RESULT_TXT_VC'] == "DECLINED" && ( $OCSTransArray[$key]["LookupName"] == "Pending - Agent Initiated" || $OCSTransArray[$key]["LookupName"] == "Pending - Web Initiated") ){
                    //echo "refunded trans match<br/>";
                 }elseif(trim($FSTransaction['TRANS_TYPE_ID']) != "Sale" || $FSTransaction['RESULT_TXT_VC'] != "DECLINED" && ( $OCSTransArray[$key]["LookupName"] == "Pending - Agent Initiated" || $OCSTransArray[$key]["LookupName"] == "Pending - Web Initiated") ){
                     _writeError("Could not match Pending OSC status with Frontstream Invoice ID ".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }else{
                     _writeError("Could not match status for ".$OCSTransArray[$key]['LookupName'] ."".$FSTransaction['INVOICE_ID'], $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
                 }

            }else{

                _writeError("Transaction Missing from OCS ".$FSTransaction['INVOICE_ID']."", $FSTransaction['TRX_HD_KEY'], $FSTransaction['DATE_DT']);
            }


        
        }

    }

}

function _writeError($message, $FSInvoiceNum = "None", $transTime = "None"){
    $Notification = new RNCPHP\helpers\FS_Reconcile_Notif;
    $Notification->Message = $message;
    $Notification->FS_InvoiceNum = $FSInvoiceNum;
    $Notification->FS_Transaction_Time = (string)$transTime;
    
    //_output($Notification);
    $Notification->save();
    return true;
}


function searcharray($value, $key, $array) {
   foreach ($array as $k => $val) {
       if ($val[$key] == $value) {
           return $k;
       }
   }
   return null;
}

function getTransArray(){

    try{
        
        $roql = "select t.ID, t.currentStatus.LookupName, t.totalCharge, t.CreatedTime from financial.transactions t where t.CreatedTime >= '".date('Y-m-d 08:00:00', strtotime('midnight', FROM_DATE))."' AND t.CreatedTime < '".date('Y-m-d 08:00:00',strtotime("midnight", TO_DATE))."'";
        $res = RNCPHP\ROQL::query( $roql )->next();
    
        //echo $roql;
        if(count($res) > 0){
    
            $transArray = array();
    
            while($trans = $res->next()){
                $transArray[] = $trans;
            }

            return $transArray;
        }

        return null;
    }catch(Exception $e){
        print_r($e->getMessage());
    }catch(RNCPHP\ConnectAPIError $err) {
        print_r($err->getMessage());
    }
    

}




/**
*
* Curl Stuff
*/


function runTransaction(array $postVals) {

   $host = cfg_get(CUSTOM_CFG_frontstream_endpoint);
   $host .= "/" . $postVals['op'];

   $mybuilder = array();
   foreach ($postVals as $key => $value) {
       $mybuilder[] = $key . '=' . $value;
   }

   $result = runCurl($host, $mybuilder);

   if ($result == false) {
       
       return false;
   }

   return parseFrontStreamRespOneTime($result, $transType);


}

function runCurl($host, array $postData) {
   try {

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $host);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postData));
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       $result = curl_exec($ch);

       if (curl_errno($ch) > 0) {
       
           _output(curl_error($ch));
           curl_close($ch);
           return false;
       } else if (strpos($result, "HTTP Error") !== false) {
           
           _output(curl_error($ch));
           curl_close($ch);
           return false;
       }
   } catch(Exception $e) {
       curl_close($ch);
       _output(curl_error($ch));
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


    $response = $values[0]['value'];

    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $response, $vals, $index);
    xml_parser_free($parser);


    $transactions = array();
    $i = 0;
    foreach ($vals as $val){
        // _output($val);

        if($val['level'] == 2 && $val['type'] == 'close'){
                //$transactions["RICHDBDS"]["TRXDETAILCARD".$i] = array();
                //echo "val level = 2 val tag = ".$val['tag']. " val value = ".$val['value']."<br/>";
                $i++;
        }else if($val['level'] == 3){
                //echo "val level = 3 val tag = ".$val['tag']. " val value = ".$val['value']."<br/>";
                $level3tag = $val['tag'];
                $transactions["RICHDBDS"]["TRXDETAILCARD".$i][$level3tag] = $val['value'];
        }


//
    }

    //_output($transactions);
    return $transactions;

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
