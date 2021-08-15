<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="responsive.php" login_required="false" clickstream="payment" />
<?

$CI    		= & get_instance();
$this->load->helper('log');

function _logToFile($lineNum, $message){
    $hundredths = ltrim(microtime(), "0");
    
    $fp = fopen('/tmp/esgLogPayCron/transactionLogs_'.date("Ymd").'.log', 'a');
    fwrite($fp,  date('H:i:s.').$hundredths.": successNewPM Controller @ $lineNum : ".$message."\n");
    fclose($fp);
    
}

//have to use this header re write for frontstream redirect in an iframe to work.
//RNT write the header to Deny all redirects in an iframe for click jack purposes.
//if this does not conform to security standards we will need to investigate a different method.
header('X-Frame-Options: PLACEHOLDER');
// _logToFile(7, "Begin New PM");
helplog(__FILE__, __FUNCTION__.__LINE__,"Begin New PM", "");

$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();

$messagesArr = array();
$this -> load -> helper('constants');

// _logToFile(24, print_r($_GET, true));
helplog(__FILE__, __FUNCTION__.__LINE__,print_r($_GET, true), "");

$cleanGetData = array();
foreach ($_GET as $key => $value) {
    $cleanGetData[addslashes($key)] = addslashes($value);
}

// _logToFile(24, print_r($cleanGetData, true));
helplog(__FILE__, __FUNCTION__.__LINE__,print_r($cleanGetData, true), "");

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
// _logToFile(__LINE__, "Need to check status of transaction:".$transId);
helplog(__FILE__, __FUNCTION__.__LINE__,"Need to check status of transaction:".$transId, "");
if(!empty($transId)){
    // _logToFile(__LINE__, "Checking for status completed");
    helplog(__FILE__, __FUNCTION__.__LINE__,"Checking for status completed", "");
    $transObj = $this-> CI -> model('custom/transaction_model') -> get_transaction($transId);
    // _logToFile(__LINE__, "Current status:".$transObj->currentStatus->Name." compared to:".TRANSACTION_SALE_SUCCESS_STATUS);
    helplog(__FILE__, __FUNCTION__.__LINE__,"Current status:".$transObj->currentStatus->Name." compared to:".TRANSACTION_SALE_SUCCESS_STATUS, "");
    if ($transObj->currentStatus->Name == TRANSACTION_SALE_SUCCESS_STATUS) {
        //just skip to success.
        // _logToFile(76,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/$transId/header/$headerRedirect");
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

    // _logToFile(61, "Transaction:$transId Session:$sessionID Contact:$c_id");
    helplog(__FILE__, __FUNCTION__.__LINE__,"Transaction:$transId Session:$sessionID Contact:$c_id", "");
    //this condition is if a customer only enters their card as a payment method on /app/paymentmethods
    if ($cleanGetData['StatusCode'] == 0 && $newpaymeth[0] == "NewPM") {
    // _logToFile(65, "");
        if ($cleanGetData['CardType'] != "Checking") {
            $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
            $transType = "card";
        } else {
            $newPayment = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
            $transType = "check";
        }
    // _logToFile(73, "");
        if ($newPayment -> ID < 1) {
            $messagesArr[] = "There was a problem saving your payment information.";
            $status = TRANSACTION_SALE_ERROR_STATUS;
        } else {
            $success = $this -> model('custom/frontstream_model') -> ReversePayment($cleanGetData['PNRef'], $transType);
            if ($success) {
                $headerRedirect = "success_paymethod";
            }

        }
    // _logToFile(84, "");

    } else if (!is_null($transId) && is_numeric($transId) && $transId > 0) {

    // _logToFile(88, "");
        //CREDIT CARD RESPONSE
        if ($cleanGetData['CardType'] != "Checking") {
    // _logToFile(91, "");
            if (isset($cleanGetData['StatusCode']) && $cleanGetData['StatusCode'] == 0) {
    // _logToFile(93, "");
                $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Frontstream raw return data: \n\n" . print_r($cleanGetData, true));
    // _logToFile(95, "");
                //create new payment method
                $newPaymentObj -> ID = -1;
                $status = TRANSACTION_SALE_SUCCESS_STATUS;
                $newPaymentObj = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "Credit Card", $cleanGetData['AccountExpMonth'], $cleanGetData['AccountExpYear'], $cleanGetData['AccountLastFour']);
    // _logToFile(100, "");
                if ($newPaymentObj -> ID < 1) {
    // _logToFile(102, "");
                    $messagesArr[] = "There was a problem saving your payment information for future use.";
                    $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Failed to create payment method.");
                    $status = TRANSACTION_SALE_ERROR_STATUS;
                } else {
    // _logToFile(107, "");
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
    // _logToFile(112, print_r($items, true));
    helplog(__FILE__, __FUNCTION__.__LINE__,print_r($items, true), "");
                $donationId = $this -> model('custom/donation_model') -> createDonationAfterTransaction($cleanGetData['TransactionAmount'], $c_id, $items, $transId, $newPaymentObj);
    // _logToFile(114, "DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef']);
    helplog(__FILE__, __FUNCTION__.__LINE__,"DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef'], "");
                //need to update this here in order to get CPM to fire after donation is added
                $this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, $donationId, $status, $newPaymentObj -> ID, $cleanGetData['PNRef']);
            } catch(\Exception $e) {
    // _logToFile(118, print_r($e->getMessage(), true));
    helplog(__FILE__, __FUNCTION__.__LINE__,"", print_r($e->getMessage(), true));
                print("\n<br>" . $e -> getMessage());
                $donationId = -1;
            }

    // _logToFile(131, "");
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
    // _logToFile(151, "");
            //ACH RESPONSE
            if (isset($cleanGetData['StatusCode']) && $cleanGetData['StatusCode'] == 0) {
    // _logToFile(154, "");
                $this -> model('custom/transaction_model') -> addNoteToTrans($transId, "Frontstream raw return data: \n\n" . print_r($cleanGetData, true));
                //create new payment method
                $newPaymentObj -> ID = -1;
                $status = TRANSACTION_SALE_SUCCESS_STATUS;
                $newPaymentObj = $this -> model('custom/paymentMethod_model') -> createPaymentMethod($c_id, $cleanGetData['CardType'], $cleanGetData['PNRef'], "EFT", null, null, $cleanGetData['AccountLastFour']);
    // _logToFile(160, "New Payment ID:".$newPaymentObj -> ID);
    helplog(__FILE__, __FUNCTION__.__LINE__,"New Payment ID:".$newPaymentObj -> ID,"");
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
    // _logToFile(191, print_r($items, true));
    helplog(__FILE__, __FUNCTION__.__LINE__,print_r($items, true),"");
                $donationId = $this -> model('custom/donation_model') -> createDonationAfterTransaction($cleanGetData['TransactionAmount'], $c_id, $items, $transId, $newPaymentObj);
    // _logToFile(193, "DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef']);
    helplog(__FILE__, __FUNCTION__.__LINE__,"DonationId:$donationId ContactId:$c_id TransactionAmount:".$cleanGetData['TransactionAmount']." Status:$status NewPayId:".$newPaymentObj -> ID." PnRef:".$cleanGetData['PNRef'],"");
                $this -> model('custom/transaction_model') -> update_transaction($transId, $c_id, $cleanGetData['TransactionAmount'], null, $donationId, $status, $newPaymentObj -> ID, $cleanGetData['PNRef']);
            } catch(\Exception $e) {
    // _logToFile(196, $e->getMessage());
    helplog(__FILE__, __FUNCTION__.__LINE__,"",$e->getMessage());
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
    // _logToFile(194,"");
            $this -> session -> setSessionData($sessionData);
            $sessionData = array('transId' => null);
            $this -> session -> setSessionData($sessionData);
    // _logToFile(198,"");

            /*********************************************************************************************/
            if ($donationId < 1) {
    // _logToFile(202,"");
                $messagesArr[] = "There was a problem recording your donation.  Please contact donor services for assistance.";
            }
        }

    } else {
        // _logToFile(208,"");
        $messagesArr[] = "There was a problem recording your donation.  Please contact donor services and reference authorization code " . $cleanGetData['AuthCode'] . ' and reference number ' . $cleanGetData['PNRef'] . " regarding this error.";
    }

    // _logToFile(222,print_r($messagesArr, true));
    helplog(__FILE__, __FUNCTION__.__LINE__,print_r($messagesArr, true),"");
    if (count($messagesArr) > 0) {
        if (!is_null($transId)) {
            print('<div>Thank you, your transaction ID is: ' . $transId . '. Please retain this transaction number for future reference.</div>');
        }
        print('<div>There may have been a problem with your transaction.  Please contact donor services.</div>');
        print('<div>The following may be useful to track down any issues: </div><ul><li>' . implode("</li><li>", $messagesArr) . '</li></ul>');
    } else {
        if ($headerRedirect) {
            // _logToFile(231,"Redirecting Base URL:$baseURL /app/payment/  $headerRedirect ");
            helplog(__FILE__, __FUNCTION__.__LINE__,"Redirecting Base URL:$baseURL/app/payment/  $headerRedirect ","");
            echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/' . $headerRedirect . '"</script>';
        } else {
            // _logToFile(234,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/ $transId /header/$headerRedirect");
            helplog(__FILE__, __FUNCTION__.__LINE__,"Redirecting Base URL:$baseURL/app/payment/successCC/t_id/ $transId /header/$headerRedirect","");
            echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/successCC/t_id/' . $transId . '/header/' . $headerRedirect . '"</script>';
        }
    }
}else{//skipToSuccess != true
    // _logToFile(262,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/ $transId /header/$headerRedirect");
    helplog(__FILE__, __FUNCTION__.__LINE__,"Redirecting Base URL:$baseURL /app/payment/successCC/t_id/ $transId /header/$headerRedirect","");
    echo '<script type="text/javascript">window.top.location.href="' . $baseURL . '/app/payment/successCC/t_id/' . $transId . '/header/' . $headerRedirect . '"</script>';
}


// _logToFile(239,"");

print('<div>Thank you, your transaction ID is: ' . $transId . '. Please retain this transaction number for future reference.</div>');
// _logToFile(240,"");
?>