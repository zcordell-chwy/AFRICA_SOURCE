<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="basic.php" login_required="false" clickstream="payment" />
<?
$CI    		= & get_instance();
$this->load->helper('log');

function _logToFile($lineNum, $message){
    $hundredths = ltrim(microtime(), "0");
    
    $fp = fopen('/tmp/esgLogPayCron/refundNewPay_'.date("Ymd").'.log', 'a');
    fwrite($fp,  date('H:i:s.').$hundredths.": success_paymethod Controller @ $lineNum : ".$message."\n");
    fclose($fp);
    
}

// _logToFile(14, "*********Begin Refund PayMethod***********");
helplog(__FILE__, __FUNCTION__.__LINE__,"*********Begin Refund PayMethod***********", "");
//have to use this header re write for frontstream redirect in an iframe to work.
//RNT write the header to Deny all redirects in an iframe for click jack purposes.
//if this does not conform to security standards we will need to investigate a different method.
header('X-Frame-Options: PLACEHOLDER');

$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();

$messagesArr = array();
$this -> load -> helper('constants');

$cleanGetData = array();
foreach ($_GET as $key => $value) {
    $cleanGetData[addslashes($key)] = addslashes($value);
}

$dataString = print_r($cleanGetData, true);
if(is_string($dataString))
    // _logToFile(36, $dataString);
    helplog(__FILE__, __FUNCTION__.__LINE__,$dataString, "");

$newpaymeth = explode("-", $cleanGetData['InvoiceNumber']);
// _logToFile(41, print_r($newpaymeth, true));
helplog(__FILE__, __FUNCTION__.__LINE__,print_r($newpaymeth, true), "");

$c_id = $this -> session -> getSessionData('theRealContactID');
if(empty($c_id)){
    $c_id = $newpaymeth[2];
}

// _logToFile(29, "Contact:$c_id");
helplog(__FILE__, __FUNCTION__.__LINE__,"Contact:$c_id", "");

//this condition is if a customer only enters their card as a payment method on /app/paymentmethods
if ($cleanGetData['StatusCode'] == 0 && $newpaymeth[0] == "NewPM") {

    // _logToFile(47, "Creating New Pay method");
    helplog(__FILE__, __FUNCTION__.__LINE__,"Creating New Pay method", "");
    if ($cleanGetData['CardType'] != "Checking") {
        $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod(intval($c_id), $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
        $transType = "card";
    } else {
        $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod(intval($c_id), $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
        $transType = "check";
    }

    // _logToFile(47, "Post Create New Pay method ID:".$newPayment -> ID);
    helplog(__FILE__, __FUNCTION__.__LINE__,"Post Create New Pay method ID:".$newPayment -> ID, "");
    if ($newPayment -> ID < 1) {
        $messagesArr[] = "There was a problem saving your payment information.";
        $status = TRANSACTION_SALE_ERROR_STATUS;
    } else {
        // _logToFile(62, "Reversing Pay Method PNRef:".$cleanGetData['PNRef']." TransType:".$transType);
        helplog(__FILE__, __FUNCTION__.__LINE__,"Reversing Pay Method PNRef:".$cleanGetData['PNRef']." TransType:".$transType, "");
        $success = $this -> model('custom/frontstream_model') -> ReversePayment($cleanGetData['PNRef'], $transType);
        if ($success) {
            $headerRedirect = (getUrlParm('p_id') > 0) ? "/app/account/pledges/c_id/$c_id/action/updateConfirm/p_id/".getUrlParm('p_id') : "/app/account/transactions/c_id/".$c_id."/action/updateConfirm/";
        }

    }
    
    // _logToFile(47, "Post Reverse Pay method:".$newPayment -> ID);
    helplog(__FILE__, __FUNCTION__.__LINE__,"Post Reverse Pay method:".$newPayment -> ID, "");
    if(getUrlParm('p_id') > 0){ //if we are updating the paymethod on a pledge, assign that pledge
        $success = $this -> model('custom/donation_model') -> savePayMethodToPledge(getUrlParm('p_id'), $newPayment );
    }

}
?>

<div class="responseMessage">
    <br />Your New Payment Method has been saved. If you are updating your pledge it has been associated to your pledge. <br /></br>You will be redirected to your pledge list now. If this doesn't happen, please refresh your page.
</div>
<?
    if ($headerRedirect) {
        echo '<script type="text/javascript">window.top.location.href="' . $baseURL . $headerRedirect . '"</script>';
    }
?>