<?php

namespace Custom\Widgets\eventus;

use RightNow\Connect\v1_3 as RNCPHP,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Framework;

class ajaxCustomFormSubmit extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'default_ajax_endpoint' => array(
                'method'      => 'handle_default_ajax_endpoint',
                'clickstream' => 'custom_action',
            ),
            'deletepaymethod_ajax_endpoint' => array(
                'method'      => 'handle_deletePaymethod',
                'clickstream' => 'custom_action',
            ),
            'changepaymethod_ajax_endpoint' => array(
                'method'      => 'handle_changePaymethod',
                'clickstream' => 'custom_action',
            ),
        ));
    }

    function getData()
    {

        return parent::getData();
    }

    function handle_deletePaymethod($params)
    {
        $rawFormDataArr = json_decode($params['formData']);
        logMessage("delete pay method");
        logMessage($rawFormDataArr);

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
        logMessage($cleanFormArray);

        $success = false;

        if ($cleanFormArray['payMethodId']) {
            $payMethodObj = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['payMethodId']);
            $success = $this->CI->model('custom/paymentMethod_model')->deletePaymentMethod($cleanFormArray['payMethodId']);
        }

        echo $this->createResponseObject($success, array(), "/app/account/transactions/c_id/" . $this->CI->session->getProfileData('contactID') . "/action/deleteConfirm/", array('confirmMessage' => getMessage(CUSTOM_MSG_DELETE_PAYMETHOD)));
    }

    function handle_changePaymethod($params)
    {
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
        logMessage($cleanFormArray);

        $success = false;

        if ($cleanFormArray['pledgeId'] > 0 && $cleanFormArray['payMethodId']) {
            $payMethodObj = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['payMethodId']);
            $success = $this->CI->model('custom/donation_model')->savePayMethodToPledge($cleanFormArray['pledgeId'], $payMethodObj);
            logMessage("success = " . $success);
        }

        echo $this->createResponseObject($success, array(), "/app/account/pledges/c_id/" . $this->CI->session->getProfileData('contactID') . "/action/updateConfirm/p_id/" . $cleanFormArray['pledgeId'] . "/", null);
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

            $blockedips = explode(",", RNCPHP\Configuration::fetch("CUSTOM_CFG_BLOCKED_IP_ADDRESSES")->Value);
            logMessage($_SERVER['HTTP_REFERER']);

            foreach ($blockedips as $ip) {
                if($_SERVER['REMOTE_ADDR'] == $ip)  {
                    echo $this->createResponseObject("Unqualified", [\RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG)]);
                    return;
                }
            }

            // if($params["Street"] != null && $params["City"] != null && $params["CLName"] != null && $params["CFName"] != null && $params["Emails"] != null && $params["Zip"] != null){} else    {
            //     echo $this->createResponseObject("Invalid Fields", null);
            //     return;
            // }
            
            $rawFormDataArr = json_decode($params['formData']);

            //if($this->checkIsUserQualified() == 1){
            if( $this->CI->model('custom/user_tracking')->checkIsUserQualified()==1){
                // continue;
            }else{
                echo $this->createResponseObject(RNCPHP\MessageBase::fetch(1000063)->Value, [\RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG)]);
                return;
            }

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
                 //cvv
                    if ($rawData->name == "cvnumber2" )
                       $cleanFormArray[$cleanIndex] = $cleanData;
            
            }

            $sanityCheckMsgs = array();
            $cleanFormArray['paymentMethodId'] = (int)$cleanFormArray['paymentMethodId'];
            if (is_null($cleanFormArray['paymentMethodId']) || !is_int($cleanFormArray['paymentMethodId']) || $cleanFormArray['paymentMethodId'] < 1) {
                $sanityCheckMsgs[] = "Invalid Payment Method";
            }


            $transactionId = $this->CI->session->getSessionData('transId');
            if (is_null($transactionId) || strlen($transactionId) < 1) {
                $sanityCheckMsgs[] = "Invalid Transaction";
            }
            $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($c_id);
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

            if (is_null($this->CI->session->getSessionData('total')) ||  !is_numeric($this->CI->session->getSessionData('total')) || $this->CI->session->getSessionData('total') != $cleanFormArray['PaymentAmount']) {
                logMessage("**Total = " . $this->CI->session->getSessionData('total'));
                $sanityCheckMsgs[] = "Invalid Payment Amount";
            }

            //Creating the User entries in user tracking table
           // $this->createUserLogEntry();
           $this->CI->model('custom/user_tracking')->createUserLogEntry();

            // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
            $transItemType = $this->CI->session->getSessionData('item_type');

            if ($transItemType === DONATION_TYPE_SPONSOR) {
                logMessage('Running sponsorship transaction. Verifying child record lock is still held by logged in user.');
                //$items = $this->session->getSessionData('items');
                $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
                // I think there can only ever be a single child item here but doing a for loop to make this future proof
                foreach ($items as $item) {
                    ////$this->logging->logVar('Child sponsorship record: ', $item);
                    $status = $this->CI->model('custom/sponsorship_model')->isChildRecordLocked(intval($item['childId']));
                    ////$this->logging->logVar('Is Child Record Locked?: ', $status->isLocked);
                    ////$this->logging->logVar('Lock Owner: ', $status->lastOwner);
                    $loggedInContactID = $this->CI->session->getProfileData('contactID');
                    //$this->logging->logVar('Logged in contact ID: ', $loggedInContactID);
                    if (!$item['isWomensScholarship'] && (!$status->isLocked || $status->lastOwner !== $loggedInContactID)) {
                        $sanityCheckMsgs[] = "Lock on child record has expired. Please redo transaction.";
                    }
                }
            }

            if (count($sanityCheckMsgs) > 0) {
                echo $this->createResponseObject("Invalid Input", $sanityCheckMsgs);
                return;
            }

            $this->_logToFile(181, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");
            logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");

            //ProcessPayment($transactionId, RNCPHP\financial\paymentMethod $paymentMethod, $amount = "0", $transType = "",$ccard="", $cvv="",$expdate="", $isguest=false,$savedcard=false)
           // $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->session->getSessionData('total')), FS_SALE_TYPE); old before cvv
           
            $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))), FS_SALE_TYPE,'',$cleanFormArray['cvnumber2'],'',false,$_SERVER['REMOTE_ADDR'],true);

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
                $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/successCC/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "------------");
                $this->_logToFile(203, "---------");
                $this->_logToFile(204, "---------");
                echo $this->createResponseObject("Success!", array(), "/app/payment/successCC/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/");
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
            $amt = intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID')));
            //$items = $this -> session -> getSessionData('items');
            $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
            $transactionId = $this->CI->session->getSessionData('transId');
            $this->_logToFile(221, "Creating Donation from paymentmethod: " . $paymethod->ID);
            $this->_logToFile(222, "Amt:" . $amt . " Contact:" . $c_id . " Transaction:" . $transactionId . " Items:");
            $this->_logToFile(223, print_r($items, true));
            $donationId = $this->CI->model('custom/donation_model')->createDonationAfterTransaction($amt, $c_id, $items, $transactionId, $paymethod);
            $this->_logToFile(225, "Created donation $donationId");
        } catch (Exception $e) {
            logMessage($e->getMessage());
            $donationId = -1;
        }
        return $donationId;
    }

    function clearSessionData()
    {
        $sessionData = array(
            'total' => null,
            'totalRecurring' => null,
            'items' => null,
            'donateValCookieContent' => null,
            'payMethod' => null
        );

        $sessionData = array('transId' => null);
        $this->CI->session->setSessionData($sessionData);

        $this->CI->model('custom/items')->clearItemsFromCart($this->CI->session->getSessionData('sessionID'));
    }

    function clearCartData()
    {
        logMessage("Clearing cart data");

        $sessionData = array(
            'total' => null,
            'totalRecurring' => null,
            'items' => null,
            'donateValCookieContent' => null,

        );
        $this->CI->session->setSessionData($sessionData);

        $this->CI->model('custom/items')->clearItemsFromCart($this->CI->session->getSessionData('sessionID'));
    }

    private function createResponseObject($message, array $errors, $redirectLocation = null, $includeObject = null)
    {
        $result = array();

        if (count($errors) > 0) {
            $result['errors'] = $errors;
        } else if (!is_null($redirectLocation) && strlen($redirectLocation) > 0) {
            $result['result']['redirectOverride'] = $redirectLocation;
        } else if (is_null($message)) {
            $result['errors'] = array(getConfig(CUSTOM_CFG_general_cc_error_id));
        }

        $result['message'] = $message;
        if (!is_null($includeObject)) {
            $result['data'] = (object)$includeObject;
        } else {
            $result['data'] = (object) array();
        }
        $result['newFormToken'] = Framework::createTokenWithExpiration(0);
        return json_encode((object)$result);
    }

    /**
     * Handles the creation of user entry in log table
     * captures the request uri, IP & form submit time stamp
     */
   /* function createUserLogEntry(){
        logMessage("User IP Address : " . $_SERVER['REMOTE_ADDR']);                    
        logMessage("User Request URL : " . $_SERVER['REQUEST_URI']);       
        
        helplog(__FILE__, __FUNCTION__ . __LINE__, "User IP & Request URI / URL Details : " . $_SERVER['REMOTE_ADDR'] . " & " . $_SERVER['REQUEST_URI'] , "");        

        $userLogEntry = new RNCPHP\Log\user_tracking();
        $userLogEntry->user_ip = $_SERVER['REMOTE_ADDR'];
        $userLogEntry->request_url = $_SERVER['REQUEST_URI'];
        $userLogEntry->form_submit_time = strtotime("now");
	
	$c_id = $this->CI->session->getSessionData('contact_id');
	if($c_id!=null){
        $userLogEntry->contact_id=intval($c_id);
        }

        //Save Object & Commit
        $userLogEntry->save(RNCPHP\RNObject::SuppressAll);
        RNCPHP\ConnectAPI::commit();
    }*/

    /**
     * Query number of submits per IP address with in an hour
     * returns true or false
     */
  /*  function checkIsUserQualified(){
        try {
        $count = 0;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $userTracking = RNCPHP\Log\user_tracking::find("user_ip='$ip_address'");
        $origin = date_create(gmdate("Y-m-d\TH:i:s\Z", strtotime("now")));
        foreach ($userTracking as $ut) {
            $target = date_create(gmdate("Y-m-d\TH:i:s\Z", $ut->form_submit_time));
            $interval = date_diff($origin, $target);
            logMessage($interval);
            if(intval($interval->h) < 1 && intval($interval->d) < 1)
                $count++;        
        }
        if($count > RNCPHP\Configuration::fetch('CUSTOM_CFG_CP_MAX_TRANSACTION_PER_HOUR')->Value){
            return false;
        }else{
            return true;
        }
        } catch (\Exception $e) {
            $this->_logToFile(186, print_r($e->getMessage()));
        }
    }

*/


    function _logToFile($lineNum, $message)
    {
        $hundredths = ltrim(microtime(), "0");

        $fp = fopen('/tmp/pledgeLogs_' . date("Ymd") . '.log', 'a');
        fwrite($fp,  date('H:i:s.') . $hundredths . ": ajaxCustomFormSubmit Controller @ $lineNum : " . $message . "\n");
        fclose($fp);
    }



}
