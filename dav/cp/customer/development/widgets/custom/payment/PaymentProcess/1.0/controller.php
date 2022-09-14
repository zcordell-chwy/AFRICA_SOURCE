<?php
namespace Custom\Widgets\payment;
use RightNow\Connect\v1_4 as RNCPHP;

class PaymentProcess extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'default_ajax_endpoint' => array(
                'method'      => 'handle_default_ajax_endpoint',
                'clickstream' => 'custom_action',
            ),
        ));

        $this->CI->load->helper('constants');
        $this->CI->load->library('CurlLibrary');
        $this->CI->load->library('XMLToArray');
    }

    function getData() {

        $this -> CI -> load -> helper('constants');
        
        $contactObj = $this->CI->model('Contact')->get()->result;
        
        $c_id = $this->CI->session->getProfileData('contactID');
        logMessage('contact id = ' . var_export($c_id, true));
        
        $this->CI->session->setSessionData(array("contact_id" => $c_id));

        //Fetching the existing payment methods
        $this->data['paymentMethodsArr'] = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($c_id);
        // logmessage($this->data['paymentMethodsArr']);


        logMessage('Session id = ' . var_export($this->CI->session->getSessionData('sessionID'), true));

        $jsPmArr = array();
        foreach ($this->data['paymentMethodsArr'] as $pm) {
            $jsPmArr[] = array('id' => $pm->ID);
        }

        $this->data['js']['paymentMethods'] = $jsPmArr;

        //Fetch total due amount for the child sponsor.
        $id = \RightNow\Utils\Url::getParameter('id');
        if (!$id) {
            return parent::getData();
        }
        $child = RNCPHP\sponsorship\Child::fetch($id);

        $desc = 'Sponsor ' . $child->ChildRef. ' '. $child->FullName;
        $amt = $child->DisplayedRate;
        $this->CI->session->setSessionData(array("child_desc" => $desc));
        
        $this->data['amount'] = $amt;
        $this->CI->session->setSessionData(array("child_id" => $id));
        $this->CI->session->setSessionData(array("TOTAL" => $amt));
        logMessage('total = ' . var_export($amt, true));

		$transObj = $this->CI->model('custom/transaction_model')->create_transaction($c_id, $amt,$desc,null);
		$transId = $transObj;
		$this->CI->session->setSessionData(array("transId" => $transObj));

        $postVals = array();
        $postVals["EmailAddress"] = $contactObj->Emails[0]->Address;
        $postVals["FirstName"] = $contactObj->Name->First;
        $postVals["LastName"] = $contactObj->Name->Last;
        $postVals["PaymentAmount"] = $amt;
        $postVals["BillingStreetAddress"] = $contactObj->Address->Street;
        $postVals["BillingStreetAddress2"] = '';
        $postVals["BillingCity"] = $contactObj->Address->City;
        $postVals["BillingStateOrProvince"] = $contactObj->Address->StateOrProvince->LookupName;
        $postVals["BillingPostalCode"] = $contactObj->Address->PostalCode;
        $postVals["BillingCountry"] = $contactObj->Address->Country->LookupName;
        $postVals["PaymentButtonText"] = "";
        $postVals["NotificationFlag"] = "0";
        $postVals["TrackingID"] = "";
        $postVals["StyleSheetURL"] = "https://africanewlife.custhelp.com/euf/assets/themes/responsive/payment.css";
        $postVals["MerchantToken"] = FS_MERCHANT_TOKEN;
        $postVals["PostbackURL"] = FS_POSTBACK_URL;
        $postVals["PostBackRedirectURL"] = FS_POSTBACK_URL;
        $postVals["PostBackErrorURL"] = FS_POSTBACK_URL;
        $postVals["SetupMode"] = "Direct";
        $postVals["InvoiceNumber"] = $transId;
        $postVals["HeaderImageURL"] = FS_HEADER_URL;
        $postVals["DirectUserName"] = FS_USERNAME;
        $postVals["DirectUserToken"] = FS_USERTOKEN;
        $postVals["DirectMerchantKey"] = FS_MERCHANT_KEY;
        $postVals["NotificationType"] = "";
        $this->data['js']['postToFsVals'] = $postVals;
        $this->data['js']['postbackUrl'] = FS_POSTBACK_URL;
        $this->data['js']['consumerEndpoint'] = FS_COMSUMER_ENDPOINT;

        parent::getData();
    }


    /**
     * Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_default_ajax_endpoint($params)
    {
        // Perform AJAX-handling here...
        // echo response
        try {

			echo 'default_ajax_endpoint AJAX' ;die;
            $rawFormDataArr = json_decode($params['formData']);
            if (!$rawFormDataArr) {
                header("HTTP/1.1 400 Bad Request");
                // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
                Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
            }


            $cleanFormArray = array();
            foreach ($rawFormDataArr as $rawData) {
                $cleanData = addslashes($rawData->value);
                $cleanIndex = addslashes($rawData->name);
                if (($rawData->name == "paymentMethodId" && $rawData->checked == true) || $rawData->name != "paymentMethodId")
                    $cleanFormArray[$cleanIndex] = $cleanData;
            }

            $sanityCheckMsgs = array();
            $cleanFormArray['paymentMethodId'] = (int)$cleanFormArray['paymentMethodId'];
            if (is_null($cleanFormArray['paymentMethodId']) || !is_int($cleanFormArray['paymentMethodId']) || $cleanFormArray['paymentMethodId'] < 1) {
                $sanityCheckMsgs[] = "Invalid Payment Method";
            }


            $transactionId = $this->CI->session->getSessionData('transId');
			
			logMessage('Transaction Id = ' . var_export($transactionId, true));

            if (is_null($transactionId) || strlen($transactionId) < 1) {
                $sanityCheckMsgs[] = "Invalid Transaction";
            }
            $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($c_id);
            logMessage('Payment Methods = ' . var_export($paymentMethodsArr, true));
            if (count($paymentMethodsArr) < 1) {
                $sanityCheckMsgs[] = "Error Processing Payment, unable to access stored payment";
            }

            $thisPayMethod = null;
            foreach ($paymentMethodsArr as $key => $value) {
                if ($cleanFormArray['paymentMethodId'] == $value->ID) {
                    $thisPayMethod = $value;
                    break;
                }
            }


            if (is_null($thisPayMethod)) {
                $sanityCheckMsgs[] = "Unable to access stored payment method";
            }

            if (is_null($this->CI->session->getSessionData('TOTAL')) ||  !is_numeric(intval($this->CI->session->getSessionData('TOTAL')))) {
                logMessage("**Total = " . $this->CI->session->getSessionData('TOTAL'));
                $sanityCheckMsgs[] = "Invalid Payment Amount";
            }

            // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
            $transItemType = $this->CI->session->getSessionData('item_type');
            // print_r($transItemType);
			logMessage("Trans Item Type =>" . $transItemType);
            // if ($transItemType === DONATION_TYPE_SPONSOR) {
                logMessage('Running sponsorship transaction. Verifying child record lock is still held by logged in user.');

                //$items = $this->session->getSessionData('items');
                // $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
                
                // I think there can only ever be a single child item here but doing a for loop to make this future proof
                // foreach ($items as $item) {
                    ////$this->logging->logVar('Child sponsorship record: ', $item);
                    $status = $this->CI->model('custom/sponsorship_model')->isChildRecordLocked(intval($this->CI->session->getSessionData('child_id')));
                    ////$this->logging->logVar('Is Child Record Locked?: ', $status->isLocked);
                    ////$this->logging->logVar('Lock Owner: ', $status->lastOwner);
                    $loggedInContactID = $this->CI->session->getProfileData('contactID');
                    //$this->logging->logVar('Logged in contact ID: ', $loggedInContactID);
                    // if (!$this->CI->session->getSessionData('child_id') && (!$status->isLocked || $status->lastOwner !== $loggedInContactID)) {
                    //     $sanityCheckMsgs[] = "Lock on child record has expired. Please redo transaction.";
                    // }
                // }
            // }

            if (count($sanityCheckMsgs) > 0) {
                echo $this->createResponseObject("Invalid Input", $sanityCheckMsgs);
                logMessage('sanityCheckMsgs Errors');
                return;
            }

            // $this->_logToFile(181, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");
            // logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");

            $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE);

            $this->_logToFile(185, "Front Stream Response:");
            $this->_logToFile(186, print_r($frontstreamResp, true));
            logMessage("Front Stream Response: " . print_r($frontstreamResp, true));

            $result = array();

            if ($frontstreamResp['isSuccess'] === true) {
                $this->_logToFile(91, "Processing a successful transaction");
                $donationId = $this->afterTransactionDonationCreation($thisPayMethod);

                if ($donationId === false || $donationId < 1) {
                    echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                    return;
                }

                //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $thisPayMethod->ID, $frontstreamResp['pnRef']);
                //$this -> clearCartData();
                $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "------------");
                $this->_logToFile(203, "---------");
                $this->_logToFile(204, "---------");
                echo $this->createResponseObject("Success!", array(), "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/");
                return;
            }

            echo $this->createResponseObject("Error Processing Payment", $this->CI->model('custom/frontstream_model')->getEndUserErrorMsg());
            return;
        } catch (\Exception $e) {
            $this->_logToFile(215, ": " . $e->getMessage());
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            $this->_logToFile(218, $e->getMessage());
        }
    }

    function afterTransactionDonationCreation($paymethod)
    {
        try {
            //we've successfully accomplished a transaction, create the donation object
            $c_id = $this->CI->session->getProfileData('contactID');
            $amt = intval($this->CI->session->getSessionData('TOTAL'));
            //$items = $this -> session -> getSessionData('items');
            $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
            $transactionId = $this->CI->session->getSessionData('transId');
            $this->_logToFile(221, "Creating Donation from paymentmethod: " . $paymethod->ID);
            $this->_logToFile(222, "Amt:" . $amt . " Contact:" . $c_id . " Transaction:" . $transactionId . " Items:");
            $this->_logToFile(223, print_r($items, true));
            $donationId = $this->CI->model('custom/donation_model')->createDonationAfterTransaction($amt, $c_id, $items, $transactionId, $paymethod);
            $this->_logToFile(225, "Created donation $donationId");
        }catch (Exception $e) {
            logMessage($e->getMessage());
            $donationId = -1;
        }
        return $donationId;
    }

}