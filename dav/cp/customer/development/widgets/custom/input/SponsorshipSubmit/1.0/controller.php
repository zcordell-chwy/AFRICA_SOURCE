<?php

namespace Custom\Widgets\input;

use RightNow\Connect\v1_3 as RNCPHP,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework;

class SponsorshipSubmit extends \RightNow\Widgets\FormSubmit
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'default_ajax_endpoint' => array(
                'method'      => 'handle_default_ajax_endpoint',
                'clickstream' => 'custom_action',
            ),
            'save_card_payment_method' => array(
                'method'      => 'handle_save_card_payment_method',
                'clickstream' => 'save_card_payment_method',
            ),
            'save_check_payment_method' => array(
                'method'      => 'handle_save_check_payment_method',
                'clickstream' => 'save_check_payment_method',
            )
        ));

        $this->CI->load->helper('constants');
        $this->CI->load->library('CurlLibrary');
        $this->CI->load->library('XMLToArray');
        $this->CI->load->helper('log');
    }
    function getData()
    {
        // echo "<pre>";
        // print_r($this->checkIsUserQualified());
        // print_r($_SERVER["REQUEST_URI"]);die;

        /** f_tok is used for ensuring security between data exchanges.
         * Do not remove. 
         * If the contact is logged in, the token may need to be refreshed as often as the profile cookie needs to be refreshed. 
         * Otherwise, the token may need to be refreshed as often as the sessionID needs to be refreshed. */
        if (Framework::isLoggedIn()) {
            $idleLength = $this->CI->session->getProfileCookieLength();
            if ($idleLength === 0)
                $idleLength = PHP_INT_MAX;
        } else {
            $idleLength = $this->CI->session->getSessionIdleLength();
        }

        $this->data['js'] = array(
            'f_tok' => Framework::createTokenWithExpiration(0, $this->data['attrs']['challenge_required']),
            /** warn of form expiration five minutes (in milliseconds) before the token expires or the profile cookie or sessionID needs to be refreshed */
            'formExpiration' => 1000 * (min(60 * \RightNow\Utils\Config::getConfig(SUBMIT_TOKEN_EXP), $idleLength) - 300)
        );
        if ($this->data['attrs']['challenge_required'] && $this->data['attrs']['challenge_location']) {
            $this->data['js']['challengeProvider'] = AbuseDetection::getChallengeProvider();
        }



        $data = parent::getData();
        $this->data['js']['child_id'] = !empty($this->CI->session->getSessionData('child_id')) ? $this->CI->session->getSessionData('child_id') : '';
        $this->data['js']['loggedin'] = $this->CI->session->getProfileData('contactID');
        return $data;
    }

    //New customer creation on FrontStream 
    function createNewCustomer($contact)
    {
        $xml = "";
        try {
            helplog(__FILE__, __FUNCTION__ . __LINE__,  "Guest Contact ID: " . print_r($contact->ID, true) . "Guest Contact First : " . print_r($contact->Name->First, true) . "Guest Contact Last : " . print_r($contact->Name->Last, true) . "Guest Contact Last : " . print_r($contact->Emails[0]->Address, true), "");
            // logMessage("Guest Contact ID: " . print_r($contact->ID, true));
            // logMessage("Guest Contact First : " . print_r($contact->Name->First, true));
            // logMessage("Guest Contact Last : " . print_r($contact->Name->Last, true));
            // logMessage("Guest Contact Last : " . print_r($contact->Emails[0]->Address, true));
            $data = [
                "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_FS_UN_CP")->Value,
                "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_FS_PW_CP")->Value,
                "TransType" => "ADD",
                "Vendor" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_vendor")->Value,
                "CustomerKey" => "",
                "CustomerID" => $contact->ID,
                "CustomerName" => "",
                "FirstName" => !empty($contact->Name) ? $contact->Name->First : '',
                "LastName" => !empty($contact->Name)  ? $contact->Name->Last : '',
                "Title" => "",
                "Department" => "",
                "Street1" => "",
                "Street2" => "",
                "Street3" => "",
                "City" => "",
                "StateID" => "",
                "Province" => "",
                "Zip" => "",
                "CountryID" => "",
                "Email" => $contact->Emails[0]->Address,
                "DayPhone" => "",
                "NightPhone" => "",
                "Fax" => "",
                "Mobile" => "",
                "Status" => "",
                "ExtData" => "",
            ];

            $url = RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_endpoint")->Value . '/admin/ws/recurring.asmx/ManageCustomer';
            $xml = $this->CI->curllibrary->httpPost($url, $data);
            logMessage("Front Stream Manage Customer Response =>" . $xml);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Manage Customer XML Response =>" . $xml, "");
            $res = $this->CI->xmltoarray->load($xml);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Manage Customer Response =>" . $res, "");
            return $res["RecurringResult"]["CustomerKey"];
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Error in Front Stream Manage Customer Response =>" . $xml);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Error in Front Stream Manage Customer Exception =>" . $e->getMessage());
        }
    }

    //Check and Create OSvC Contact 
    function createContact($params)
    {
        try {
            $existingContact = $this->CI->model('Contact')->lookupContactByEmail($params["Emails"], $params["CFName"] ? $params["CFName"] : null, $params["CLName"] ? $params["CLName"] : null)->result;

            logMessage("Guest Existing Contact: " . print_r($existingContact, true));
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Existing Contact: " . print_r($existingContact, true), "");

            if ($existingContact) {
                logMessage("Guest Existing Contact Condition");
                $contact = RNCPHP\Contact::fetch(intval($existingContact));
                if (is_null($contact->Login)) {
                    //Set Login / Pass
                    $contact->Login = $params["Emails"];
                }

                // $contact->CustomFields->c->last_login_ip = $_SERVER['REMOTE_ADDR'];
                // $contact->CustomFields->c->last_login = strtotime("now");
                $contact->save();

                //User Entry Creation
                // $this->createUserLogEntry();
                $this->CI->model('custom/user_tracking')->createUserLogEntry();

                logMessage("Guest Existing Contact Fetch: " . print_r($contact, true));
            } else {
                logMessage("Guest New Contact Condition");
                $contact = new RNCPHP\Contact();

                //add email addresses
                $contact->Emails = new RNCPHP\EmailArray();
                $contact->Emails[0] = new RNCPHP\Email();
                $contact->Emails[0]->AddressType = new RNCPHP\NamedIDOptList();
                $contact->Emails[0]->AddressType->LookupName = "Email - Primary";
                $contact->Emails[0]->Address = $params["Emails"];

                //Set contact with First Name Last Name
                $contact->Name = new RNCPHP\PersonName();
                $contact->Name->First = $params["CFName"];
                $contact->Name->Last = $params["CLName"];

                //Set Address
                $contact->Address = new RNCPHP\Address();
                $contact->Address->Street = $params["Street"];
                $contact->Address->City = $params["City"];
                $contact->Address->StateOrProvince = new RNCPHP\NamedIDLabel();
                $contact->Address->StateOrProvince->ID = intval($params["State"]);
                $contact->Address->Country = RNCPHP\Country::fetch(intval($params["Country"]));
                $contact->Address->PostalCode = $params["Zip"];

                //Set Login / Pass
                $contact->Login = $params["Emails"];
                logMessage("User Login : " . $params["Emails"]);
                if (strlen($params["CPass"]) > 1)
                    $contact->NewPassword = $params["CPass"];

                $newPassword = $params["CPass"];
                logMessage("User Login : " . $newPassword);

                //Hear about us field
                if (strlen($params["AboutUS"]) > 0)
                    $contact->CustomFields->CO->how_did_you_hear = $params["AboutUS"];


                // logMessage("User IP Address : " . $_SERVER['REMOTE_ADDR']);                    
                // logMessage("User Timestamp" . strtotime("now"));                    
                // $contact->CustomFields->c->last_login_ip = $_SERVER['REMOTE_ADDR'];
                // $contact->CustomFields->c->last_login = strtotime("now");

                //$this->createUserLogEntry();

                $this->CI->model('custom/user_tracking')->createUserLogEntry();

                //Save Contact Object & Commit
                $contact->save(RNCPHP\RNObject::SuppressAll);
                RNCPHP\ConnectAPI::commit();
            }
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        return $contact;
    }

    /**
     * Handles the creation of user entry in log table
     * captures the request uri, IP & form submit time stamp
     */
    /*
    function createUserLogEntry(){
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
            // print_r($interval->format('%h'));
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
    }*/


    /**
     * Handles the save_card_payment_method AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_save_card_payment_method($params)
    {
        try {
            $blockedips = explode(",", RNCPHP\Configuration::fetch("CUSTOM_CFG_BLOCKED_IP_ADDRESSES")->Value);

            foreach ($blockedips as $ip) {
                if ($_SERVER['REMOTE_ADDR'] == $ip) {
                    echo $this->createResponseObject("Unqualified", [\RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG)]);
                    return;
                }
            }

            // AbuseDetection::check();
            if (AbuseDetection::check() || AbuseDetection::isAbuse()) {
                Framework::writeContentWithLengthAndExit(json_encode(true), 'application/json');
            }

            //if($this->checkIsUserQualified() == 1){
            if ($this->CI->model('custom/user_tracking')->checkIsUserQualified() == 1) {
                logMessage("user is qualified");
            } else {
                return $this->renderJSON(["errors" => \RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG), "error" => ""]);
            }


            // Perform AJAX-handling here...
            $xml = "";
            logMessage("Form Parameters: " . print_r($params, true));
            $contact = $this->CI->model('Contact')->get()->result;
            logMessage("Guest Contact Object Response: " . print_r($contact, true));
            if ($contact)
                $customerKey = $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key;

            // logMessage("Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            if (is_null($contact)) {
                logMessage("Guest Contact Checking..!!");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                // logMessage("Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                logMessage("Guest Contact CreateNewCustomer in FrontStream");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;
                $contact->CustomFields->c->customer_key = $customerKey;
                //Store the IP and last submit time against contact
                //$contact->CustomFields->c->last_login_ip = $_SERVER['REMOTE_ADDR'];
                //$contact->CustomFields->c->last_login = strtotime("now");

                $contact->save();
                // logMessage("Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            if (!is_null($contact->CustomFields->c->customer_key)) {
                $data = [
                    "Username" => RNCPHP\Configuration::fetch('CUSTOM_CFG_FS_UN_CP')->Value,
                    "Password" => RNCPHP\Configuration::fetch('CUSTOM_CFG_FS_PW_CP')->Value,
                    "TransType" => "ADD",
                    "Vendor" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_vendor')->Value,
                    "CustomerKey" => !empty($customerKey) ? $customerKey : '',
                    "CardInfoKey" => "",
                    "CcAccountNum" => $params["CardNum"],
                    "CcExpDate" => $params["ExpMonth"] . substr($params["ExpYear"], 2, 2),
                    "CcNameOnCard" => $params["NameOnCard"],
                    "CcStreet" => "",
                    "CcZip" => "",
                    "ExtData" => ""
                ];
                //"CustomerKey" => !empty($contact->CustomFields->c->customer_key) ? $contact->CustomFields->c->customer_key : '',
                logMessage("Front Stream ManageCredit Info Request =>" . $data);

                if ($params['Amount']) {
                    $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                }

                $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/admin/ws/recurring.asmx/ManageCreditCardInfo';
                $xml = $this->CI->curllibrary->httpPost($url, $data);
                logMessage("Front Stream Manage Credit Card Info =>" . $xml);
                $res = $this->CI->xmltoarray->load($xml);
                logMessage($res);

                if (is_null($res["RecurringResult"]["CcInfoKey"]))
                    throw new \Exception("Ensure all fields have correct information.");

                $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/ArgoFire/validate.asmx/GetCardType';
                $xml = $this->CI->curllibrary->httpPost($url, ["CardNumber" => $params["CardNum"]]);
                logMessage("Front Stream GetCardType =>" . $xml);
                $cardtype = $this->CI->xmltoarray->load($xml);

                if (is_null($cardtype["string"]["cdata"]))
                    throw new \Exception("Card Type Lookup Failure. Please Try Again.");

                $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, $cardtype["string"]["cdata"], null, 1, $params["ExpMonth"], $params["ExpYear"], substr($params["CardNum"], -4), $res["RecurringResult"]["CcInfoKey"])->ID;
                logMessage("Payment Method ID  =>" . $id);

                $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                logMessage("Payment Method Array  =>" . $paymentMethodsArr);

                if (!is_null($id)) {
                    $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'), $this->CI->session->getSessionData('child_desc'), null, $_SERVER['REMOTE_ADDR']);
                    $transactionId = $transObj;
                    $this->CI->session->setSessionData(array("transId" => $transObj));

                    $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                    $expdate = $params["ExpMonth"] . substr($params["ExpYear"], 2, 2);
                    logMessage("---------Begining Run Transaction $transId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");
                    $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $payMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE, $params["CardNum"], $params["CVNum"], $expdate, true, $_SERVER['REMOTE_ADDR'], false);

                    logMessage("Front Stream Response: " . print_r($frontstreamResp, true));

                    $result = array();

                    if ($frontstreamResp['isSuccess'] === true) {
                        $this->_logToFile(91, "Processing a successful transaction");
                        $donationId = $this->afterTransactionDonationCreation($payMethod);

                        if ($donationId === false || $donationId < 1) {
                            echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                            return;
                        }

                        if ($contact->Login !== null) {
                            $profile = $this->CI->model('Contact')->getProfileSid($contact->Login, $params["CPass"] ?: '', $this->CI->session->getSessionData('sessionID'))->result;
                            logMessage("Guest New Contact Profile: " . print_r($profile, true));
                            if ($profile !== null && !is_string($profile)) {
                                $this->CI->session->createProfileCookie($profile);
                            }
                        }

                        //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                        $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $payMethod->ID, $frontstreamResp['pnRef']);
                        //$this -> clearCartData();
                        $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "------------");
                        $this->_logToFile(203, "---------");
                        $this->_logToFile(204, "---------");
                        echo $this->createResponseObject("Success!", array(), "/app/payment/success/id/" . $this->CI->session->getSessionData('child_id'));
                        return;
                    }
                }
                echo $this->createResponseObject("Error Processing Payment", $this->CI->model('custom/frontstream_model')->getEndUserErrorMsg());
                return;

                // $this->renderJSON(["code" => "success", "id" => $id]);
            }
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        // echo response
    }

    /**
     * Handles the save_check_payment_method AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_save_check_payment_method($params)
    { // Perform AJAX-handling here...
        $xml = "";
        logMessage("Form Parameters: " . print_r($params, true));
        try {

            $blockedips = explode(",", RNCPHP\Configuration::fetch("CUSTOM_CFG_BLOCKED_IP_ADDRESSES")->Value);

            foreach ($blockedips as $ip) {
                if ($_SERVER['REMOTE_ADDR'] == $ip) {
                    echo $this->createResponseObject("Unqualified", [\RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG)]);
                    return;
                }
            }

            //if($this->checkIsUserQualified() == 1){
            if ($this->CI->model('custom/user_tracking')->checkIsUserQualified() == 1) {
                logMessage("user is qualified");
            } else {
                return $this->renderJSON(["errors" => \RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG), "error" => ""]);
            }


            $contact = $this->CI->model('Contact')->get()->result;
            logMessage("Guest Contact Object Response: " . print_r($contact, true));
            $customerKey = $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key;

            //Store the IP and last submit time against contact
            //$contact->CustomFields->c->last_login_ip = $_SERVER['REMOTE_ADDR'];
            //$contact->CustomFields->c->last_login = strtotime("now");
           // $contact->save();

            if (is_null($contact)) {
                logMessage("Guest Contact Checking..!!");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                // logMessage("Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                logMessage("Guest Contact CreateNewCustomer in FrontStream");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;

                //Store the IP and last submit time against contact
                //$contact->CustomFields->c->last_login_ip = $_SERVER['REMOTE_ADDR'];
                //$contact->CustomFields->c->last_login = strtotime("now");

                $contact->save();
                // logMessage("Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            if (!is_null($contact->CustomFields->c->customer_key)) {
                $data = [
                    "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_FS_UN_CP")->Value,
                    "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_FS_PW_CP")->Value,
                    "TransType" => "ADD",
                    "Vendor" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_vendor")->Value,
                    "CustomerKey" => !empty($customerKey) ? $customerKey : '',
                    "CheckInfoKey" => "",
                    "CheckType" => "PERSONAL",
                    "AccountType" => $params["AccountType"],
                    "CheckNum" => "",
                    "MICR" => "",
                    "AccountNum" => $params["AccountNum"],
                    "TransitNum" => $params["TransitNum"],
                    "RawMICR" => "",
                    "SS" => "",
                    "DOB" => "",
                    "BranchCity" => "",
                    "DL" => "",
                    "StateCode" => "",
                    "NameOnCheck" => $params["NameOnCheck"],
                    "Email" => "",
                    "DayPhone" => "",
                    "Street1" => "",
                    "Street2" => "",
                    "Street3" => "",
                    "City" => "",
                    "StateID" => "",
                    "Province" => "",
                    "PostalCode" => "",
                    "CountryID" => "",
                    "ExtData" => ""
                ];

                logMessage("Front Stream ManageCheckInfo Request =>" . $data);

                if ($params['Amount']) {
                    $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                }

                $url = RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_endpoint")->Value . '/admin/ws/recurring.asmx/ManageCheckInfo';
                $xml = $this->CI->curllibrary->httpPost($url, $data);
                logMessage("Front Stream ManageCheckInfo =>" . $xml);
                $res = $this->CI->xmltoarray->load($xml);

                if (is_null($res["RecurringResult"]["CcInfoKey"]))
                    throw new \Exception("Ensure all fields have correct information.");


                $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, "Checking", null, 2, null, null, substr($params["AccountNum"], -4), $res["RecurringResult"]["CheckInfoKey"])->ID;
                logMessage("Payment Method ID  =>" . $id);

                $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                logMessage("Payment Method Array  =>" . $paymentMethodsArr);

                if (!is_null($id)) {
                    $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'), $this->CI->session->getSessionData('child_desc'), null, $_SERVER['REMOTE_ADDR']);
                    $transactionId = $transObj;
                    $this->CI->session->setSessionData(array("transId" => $transObj));

                    $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                    logMessage("---------Begining Run Transaction $transId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");
                    $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $payMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE);

                    logMessage("Front Stream Response: " . print_r($frontstreamResp, true));

                    $result = array();

                    if ($frontstreamResp['isSuccess'] === true) {
                        $this->_logToFile(91, "Processing a successful transaction");
                        $donationId = $this->afterTransactionDonationCreation($payMethod);

                        if ($donationId === false || $donationId < 1) {
                            echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                            return;
                        }

                        if ($contact->Login !== null) {
                            $profile = $this->CI->model('Contact')->getProfileSid($contact->Login, $params["CPass"] ?: '', $this->CI->session->getSessionData('sessionID'))->result;
                            logMessage("Guest New Contact Profile: " . print_r($profile, true));
                            if ($profile !== null && !is_string($profile)) {
                                $this->CI->session->createProfileCookie($profile);
                            }
                        }

                        //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                        $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $payMethod->ID, $frontstreamResp['pnRef']);
                        //$this -> clearCartData();
                        $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "--------Child ID > " . $this->CI->session->getSessionData('child_id'));
                        $this->_logToFile(203, "---------");
                        $this->_logToFile(204, "---------");
                        echo $this->createResponseObject("Success!", array(), "/app/payment/success/id/" . $this->CI->session->getSessionData('child_id'));
                        return;
                    }
                }
                echo $this->createResponseObject("Error Processing Payment", $this->CI->model('custom/frontstream_model')->getEndUserErrorMsg());
                return;

                // $this->renderJSON(["code" => "success", "id" => $id]);
            }
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        // echo response
    }


    /**
     * Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_default_ajax_endpoint($params)
    {
        try {

            $blockedips = explode(",", RNCPHP\Configuration::fetch("CUSTOM_CFG_BLOCKED_IP_ADDRESSES")->Value);

            foreach ($blockedips as $ip) {
                if ($_SERVER['REMOTE_ADDR'] == $ip) {
                    echo $this->createResponseObject("Unqualified", [\RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG)]);
                    return;
                }
            }

            $rawFormDataArr = json_decode($params['formData']);
            //$this->CI->model('custom/user_tracking')->checkIsUserQualified();
            //if($this->checkIsUserQualified() == 1){
            if ($this->CI->model('custom/user_tracking')->checkIsUserQualified() == 1) {
                logMessage("user is qualified");
            } else {
                return $this->renderJSON(["errors" => \RightNow\Utils\Config::getMessage(ERROR_PAGE_PLEASE_S_TRY_MSG), "error" => ""]);
            }
            if (!$rawFormDataArr) {
                header("HTTP/1.1 400 Bad Request");
                // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
                Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
            }
            logMessage("**Total = " . $params['amt']);
            if ($params['amt']) {
                $this->CI->session->setSessionData(array("TOTAL" => $params['amt']));
            }

            $cleanFormArray = array();
            foreach ($rawFormDataArr as $rawData) {
                $cleanData = addslashes($rawData->value);
                $cleanIndex = addslashes($rawData->name);
                if (($rawData->name == "paymentMethodId" && $rawData->checked == true) || $rawData->name != "paymentMethodId")
                    $cleanFormArray[$cleanIndex] = $cleanData;
                //cvv
                if ($rawData->name == "cvnumber2")
                    $cleanFormArray[$cleanIndex] = $cleanData;
            }

            $sanityCheckMsgs = array();

            $cleanFormArray['paymentMethodId'] = (int)$cleanFormArray['paymentMethodId'];
            if (is_null($cleanFormArray['paymentMethodId']) || !is_int($cleanFormArray['paymentMethodId']) || $cleanFormArray['paymentMethodId'] < 1) {
                $sanityCheckMsgs[] = "Invalid Payment Method";
            }

            $c_id = $this->CI->session->getProfileData('contactID');
            $this->CI->session->setSessionData(array("contact_id" => $c_id));

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

            if (is_null($this->CI->session->getSessionData('TOTAL')) ||  !is_numeric($this->CI->session->getSessionData('TOTAL'))) {
                logMessage("**Total = " . $this->CI->session->getSessionData('TOTAL'));
                $sanityCheckMsgs[] = "Invalid Payment Amount";
            }

            // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
            $transItemType = $this->CI->session->getSessionData('item_type');

            // $this->createUserLogEntry();
            $this->CI->model('custom/user_tracking')->createUserLogEntry();



            logMessage('Running sponsorship transaction. Verifying child record lock is still held by logged in user.');
            $status = $this->CI->model('custom/sponsorship_model')->isChildRecordLocked(intval($this->CI->session->getSessionData('child_id')));
            $loggedInContactID = $this->CI->session->getProfileData('contactID');

            if (count($sanityCheckMsgs) > 0) {
                echo $this->createResponseObject("Invalid Input", $sanityCheckMsgs);
                return;
            }

            $this->_logToFile(181, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");
            logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");

            //ProcessPayment($transactionId, RNCPHP\financial\paymentMethod $paymentMethod, $amount = "0", $transType = "",$ccard="", $cvv="",$expdate="", $isguest=false,$savedcard=false)
            // $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE); old before cvv
            $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE, '', $cleanFormArray['cvnumber2'], '', false, $_SERVER['REMOTE_ADDR'], true);

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
                $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/id/" . $this->CI->session->getSessionData('child_id') . "------------");
                $this->_logToFile(203, "---------");
                $this->_logToFile(204, "---------");
                echo $this->createResponseObject("Success!", array(), "/app/payment/success/id/" . $this->CI->session->getSessionData('child_id'));
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
            $c_id = $this->CI->session->getSessionData('contact_id');
            $amt = intval($this->CI->session->getSessionData('TOTAL'));
            //$items = $this -> session -> getSessionData('items');
            // $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
            $items = array();
            $items['recurring'] = !empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : '';
            $items['oneTime'] = "";
            $items['fund'] = "";
            $items['appeal'] = "515";
            $items['childId'] = !empty($this->CI->session->getSessionData('child_id')) ? $this->CI->session->getSessionData('child_id') : '';
            $items['pledgeId'] = "";
            $items['type'] = DONATION_TYPE_SPONSOR;
            $items['isWomensScholarship'] = "0";

            $transactionId = $this->CI->session->getSessionData('transId');
            $this->_logToFile(221, "Creating Donation from paymentmethod: " . $paymethod->ID);
            $this->_logToFile(222, "Amt:" . $amt . " Contact:" . $c_id . " Transaction:" . $transactionId . " Items:");
            $this->_logToFile(223, print_r($items, true));
            $donationId = $this->CI->model('custom/donation_model')->createDonationAfterTransaction_1($amt, $c_id, $items, $transactionId, $paymethod);
            $this->_logToFile(225, "Created donation $donationId");
            logMessage("Create Donation After Transaction Donation ID >>: " . print_r($donationId, true));
        } catch (Exception $e) {
            logMessage($e->getMessage());
            $donationId = -1;
        }
        return $donationId;
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
        return json_encode((object)$result);
    }

    function _logToFile($lineNum, $message)
    {
        $hundredths = ltrim(microtime(), "0");

        $fp = fopen('/tmp/pledgeLogs_' . date("Ymd") . '.log', 'a');
        fwrite($fp,  date('H:i:s.') . $hundredths . ": ajaxCustomFormSubmit Controller @ $lineNum : " . $message . "\n");
        fclose($fp);
    }
}
