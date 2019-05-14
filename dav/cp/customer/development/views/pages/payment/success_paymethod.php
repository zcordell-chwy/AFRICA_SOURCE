<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="basic.php" login_required="false" clickstream="payment"/>
<?
//have to use this header re write for frontstream redirect in an iframe to work.
//RNT write the header to Deny all redirects in an iframe for click jack purposes.
//if this does not conform to security standards we will need to investigate a different method.
header('X-Frame-Options: PLACEHOLDER');

$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();

$messagesArr = array();
$this -> load -> helper('constants');

$transId = $this -> session -> getSessionData('transId');
$c_id = $this -> session -> getSessionData('theRealContactID');
$therealcontactID = $this -> session -> getSessionData('theRealContactID');

$cleanGetData = array();
foreach ($_GET as $key => $value) {
    $cleanGetData[addslashes($key)] = addslashes($value);
}


$newpaymeth = explode("-", $cleanGetData['InvoiceNumber']);

// echo "Contact = ".$c_id."<br/>";
// echo "pledge = ".getUrlParm('p_id')."<br/>";
// print_r($cleanGetData);

//this condition is if a customer only enters their card as a payment method on /app/paymentmethods
if ($cleanGetData['StatusCode'] == 0 && $newpaymeth[0] == "NewPM") {

    if ($cleanGetData['CardType'] != "Checking") {
        $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
        $transType = "card";
    } else {
        $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
        $transType = "check";
    }
    if ($newPayment -> ID < 1) {
        $messagesArr[] = "There was a problem saving your payment information.";
        $status = TRANSACTION_SALE_ERROR_STATUS;
    } else {
        $success = $this -> model('custom/frontstream_model') -> ReversePayment($cleanGetData['PNRef'], $transType);
        if ($success) {
            $headerRedirect = (getUrlParm('p_id') > 0) ? "/app/account/pledges/c_id/$c_id/action/updateConfirm/p_id/".getUrlParm('p_id') : "/app/account/transactions/c_id/".$c_id."/action/updateConfirm/";
        }

    }
    
    if(getUrlParm('p_id') > 0){ //if we are updating the paymethod on a pledge, assign that pledge
        $success = $this -> model('custom/donation_model') -> savePayMethodToPledge(getUrlParm('p_id'), $newPayment );
    }

}
?>

<div class="responseMessage">
   <br/>Your New Payment Method has been saved.  If you are updating your pledge it has been associated to your pledge.  <br/></br>You will be redirected to your pledge list now.  If this doesn't happen, please refresh your page.
</div>
<?
    if ($headerRedirect) {
        echo '<script type="text/javascript">window.top.location.href="' . $baseURL . $headerRedirect . '"</script>';
    }
?>
    




