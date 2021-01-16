<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="responsive.php" login_required="false" clickstream="payment" />
<?

function _logToFile($lineNum, $message){
    // $hundredths = ltrim(microtime(), "0");
    
    // $fp = fopen('/tmp/esgLogPayCron/transactionLogs_'.date("Ymd").'.log', 'a');
    // fwrite($fp,  date('H:i:s.').$hundredths.": successNewPM Controller @ $lineNum : ".$message."\n");
    // fclose($fp);
    
}

//have to use this header re write for frontstream redirect in an iframe to work.
//RNT write the header to Deny all redirects in an iframe for click jack purposes.
//if this does not conform to security standards we will need to investigate a different method.
header('X-Frame-Options: PLACEHOLDER');
$this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 18, "Begin New PM","Transaction"); 

$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();

$messagesArr = array();
$this -> load -> helper('constants');

$this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 18, print_r($_GET, true),"Transaction");

$cleanGetData = array();
foreach ($_GET as $key => $value) {
    $cleanGetData[addslashes($key)] = addslashes($value);
}

$this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 18, print_r($cleanGetData, true),"Transaction");
$newpaymeth = explode("-", $cleanGetData['InvoiceNumber']);

//try to get trans from session
//then try from frontstream return
//if nothing, create a new one.
$transId = $this -> session -> getSessionData('transId');

if (!is_null($transId) && is_numeric($transId) && $transId > 0) { 
}else{
    $transId = $cleanGetData['InvoiceNumber'];
}

//find the contact
$c_id = $this -> session -> getSessionData('theRealContactID');

if(!is_null($c_id) && is_numeric($c_id) && $c_id > 0){
}else{
    if(!is_null($transId) && is_numeric($transId) && $transId > 0){
        $transObj = $this-> CI -> model('custom/transaction_model') -> get_transaction($transId);
        $c_id = $transObj->contact->ID;
    }
    
}
//if all else fails, just use a placeholder contact
if(!is_null($c_id) && is_numeric($c_id) && $c_id > 0){
}else{
    $c_id = 60871;//60871: placeholder contact
}

//if all else fails create a transaction
if (!is_null($transId) && is_numeric($transId) && $transId > 0) {   
}else{
    $transId = $this-> CI -> model('custom/transaction_model') -> create_transaction($c_id, $amt, implode(',', $itemDescs)); 
}

$skipToSuccess = false;
//seems like front stream will post back twice sometimes.  need to check for completed transaction before continuing.
$this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 70, "Need to check status of transaction:".$transId,"Transaction");
if(!empty($transId)){
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 71, "Checking for status completed","Transaction");
    $transObj = $this-> CI -> model('custom/transaction_model') -> get_transaction($transId);
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 73, "Current status:".$transObj->currentStatus->Name." compared to:".TRANSACTION_SALE_SUCCESS_STATUS,"Transaction");
    if ($transObj->currentStatus->Name == TRANSACTION_SALE_SUCCESS_STATUS) {
        //just skip to success.
        // echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/successCC/t_id/' . $transId . '/header/' . $headerRedirect . '"</script>';
        $skipToSuccess = true;
    }
}

if($skipToSuccess != true){


    //session id will get lost from time to time as well.
    if(is_null($this->CI->session->getSessionData('sessionID'))){
        $sessionID = $_GET['sid']; 
    }else{
        $sessionID = $this->CI->session->getSessionData('sessionID');
    }

    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, $c_id, 0, 92, "Transaction:$transId Session:$sessionID Contact:$c_id","Transaction");
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
                $headerRedirect = "success_paymethod";
            }

        }

    } else if (!is_null($transId) && is_numeric($transId) && $transId > 0) {

        //CREDIT CARD RESPONSE
        if ($cleanGetData['CardType'] != "Checking") {
            if (isset($cleanGetData['StatusCode']) && $cleanGetData['StatusCode'] == 0) {
                $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Frontstream raw return data: \n\n" . print_r($cleanGetData, true));
                //create new payment method
                $newPaymentObj -> ID = -1;
                $status = TRANSACTION_SALE_SUCCESS_STATUS;
                $newPaymentObj = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
                if ($newPaymentObj -> ID < 1) {
                    $messagesArr[] = "There was a problem saving your payment information for future use.";
                    $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Failed to create payment method.");
                    $status = TRANSACTION_SALE_ERROR_STATUS;
                } else {
                    //can't do this here.  we need the donation on the trans before we update it to complete.
                    //$this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, null, $status, $newPaymentObj->ID);
                }
            }

            /**********************Function that can't be called from anywhere apparently*****************/

            try {
                //we've successfully accomplished a transaction, create the donation object
                $amt = $this -> session -> getSessionData('total');
                //$items = $this -> session -> getSessionData('items');
                $items = $this -> CI -> model('custom/items') -> getItemsFromCart($sessionID, 'checkout', $transId);
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 140, print_r($items, true),"Transaction");
                $donationId = $this -> model('custom/donation_model') -> createDonationAfterTransaction($cleanGetData['TransactionAmount'], $c_id, $items, $transId, $newPaymentObj);
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 142, "DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef'],"Transaction");
                //need to update this here in order to get CPM to fire after donation is added
                $this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, $donationId, $status, $newPaymentObj -> ID, $cleanGetData['PNRef']);
            } catch(\Exception $e) {
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 146,print_r($e->getMessage(), true),"Transaction");
                print("\n<br>" . $e -> getMessage());
                $donationId = -1;
            }

            $sessionData = array( 
                'total' => null,
                'totalRecurring' => null,
                'items' => null,
                'donateValCookieContent' => null,
                'payMethod' => null
            );

            $this -> session -> setSessionData($sessionData);
            $sessionData = array('transId' => null);
            $this -> session -> setSessionData($sessionData);


            /*********************************************************************************************/

            if ($donationId < 1) {
                $messagesArr[] = "There was a problem recording your donation.  Please contact donor services for assistance.";
            }
        } else {
            //ACH RESPONSE
            if (isset($cleanGetData['StatusCode']) && $cleanGetData['StatusCode'] == 0) {
                $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Frontstream raw return data: \n\n" . print_r($cleanGetData, true));
                //create new payment method
                $newPaymentObj -> ID = -1;
                $status = TRANSACTION_SALE_SUCCESS_STATUS;
                $newPaymentObj = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
                $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 177,"New Payment ID:".$newPaymentObj -> ID,"Transaction");
                if ($newPaymentObj -> ID < 1) {
                    $messagesArr[] = "There was a problem saving your payment information for future use.";
                    $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Failed to create payment method.");
                    $status = TRANSACTION_SALE_ERROR_STATUS;
                } else {
                    //can't do this here.  we need the donation on the trans before we update it to complete.
                    //$this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, null, $status, $newPaymentObj->ID);
                }
            }
            /**********************Function that can't be called from anywhere apparently*****************/

            try {
                //we've successfully accomplished a transaction, create the donation object
                $amt = $this -> session -> getSessionData('total');
                //$items = $this -> session -> getSessionData('items');
                $items = $this -> CI -> model('custom/items') -> getItemsFromCart($sessionID, 'checkout', $transId);
                $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 177,print_r($items, true),"Transaction");
                $donationId = $this -> model('custom/donation_model') -> createDonationAfterTransaction($cleanGetData['TransactionAmount'], $c_id, $items, $transId, $newPaymentObj);
                $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 197,"DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef'],"Transaction");
                $this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, $donationId, $status, $newPaymentObj -> ID, $cleanGetData['PNRef']);
            } catch(\Exception $e) {
                $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 177,$e->getMessage(),"Transaction");
                print("\n<br>" . $e -> getMessage());
                $donationId = -1;
            }


            $sessionData = array(
                'total' => null,
                'totalRecurring' => null,
                'items' => null,
                'donateValCookieContent' => null,
                'payMethod' => null
            );
            $this -> session -> setSessionData($sessionData);
            $sessionData = array('transId' => null);
            $this -> session -> setSessionData($sessionData);

            /*********************************************************************************************/
            if ($donationId < 1) {
                $messagesArr[] = "There was a problem recording your donation.  Please contact donor services for assistance.";
            }
        }

    } else {
        $messagesArr[] = "There was a problem recording your donation.  Please contact donor services and reference authorization code " . $cleanGetData['AuthCode'] . ' and reference number ' . $cleanGetData['PNRef'] . " regarding this error.";
    }

    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 226,print_r($messagesArr, true),"Transaction");
    if (count($messagesArr) > 0) {
        if (!is_null($transId)) {
            print('<div>Thank you, your transaction ID is: ' . $transId . '. Please retain this transaction number for future reference.</div>');
        }
        print('<div>There may have been a problem with your transaction.  Please contact donor services.</div>');
        print('<div>The following may be useful to track down any issues: </div><ul><li>' . implode("</li><li>", $messagesArr) . '</li></ul>');
    } else {
        if ($headerRedirect) {
            $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 236,"Redirecting Base URL:$baseURL /app/payment/  $headerRedirect ","Transaction");
            echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/' . $headerRedirect . '"</script>';
        } else {
            $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 238,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/ $transId /header/$headerRedirect","Transaction");
            echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/successCC/t_id/' . $transId . '/header/' . $headerRedirect . '"</script>';
        }
    }
}else{//skipToSuccess != true
    $this->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, 243,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/ $transId /header/$headerRedirect","Transaction");
    echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/successCC/t_id/' . $transId . '/header/' . $headerRedirect . '"</script>';
}

print('<div>Thank you, your transaction ID is: ' . $transId . '. Please retain this transaction number for future reference.</div>');
?>