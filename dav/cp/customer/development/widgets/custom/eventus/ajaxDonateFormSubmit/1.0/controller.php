<?php

namespace Custom\Widgets\eventus;

use RightNow\Connect\v1_3 as RNCPHP;

class ajaxDonateFormSubmit extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->CI->load->helper('log');
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
            'save_card_payment_method' => array(
                'method'      => 'handle_save_card_payment_method',
                'clickstream' => 'save_card_payment_method',
            ),
            'save_check_payment_method' => array(
                'method'      => 'handle_save_check_payment_method',
                'clickstream' => 'save_check_payment_method',
            ),
            'guest_card_payment_method' => array(
                'method'      => 'handle_guest_card_payment_method',
                'clickstream' => 'guest_card_payment_method',
            )
        ));

        $this->CI->load->helper('constants');
        $this->CI->load->library('CurlLibrary');
        $this->CI->load->library('XMLToArray');
    }

    function getData()
    {

        return parent::getData();
    }


    function createNewCustomer($contact)
    {
        $xml = "";
        try {
            logMessage("Guest Contact ID: " . print_r($contact->ID, true));
            logMessage("Guest Contact First : " . print_r($contact->Name->First, true));
            logMessage("Guest Contact Last : " . print_r($contact->Name->Last, true));
            $data = [
                "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_user_id")->Value,
                "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_pass_id")->Value,
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
            logMessage("Front Stream Manage Customer =>" . $xml);
            $res = $this->CI->xmltoarray->load($xml);

            return $res["RecurringResult"]["CustomerKey"];
        } catch (\Exception $e) {
            logMessage($xml);
            logMessage($e->getMessage());
        }
    }

    /**
     * Handles the guest_payment_method AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_guest_card_payment_method($params)
    {


        // Perform AJAX-handling here...
        $xml = "";
        try {

            //$mytx is an array containing all POST data from a form
            $mytx = array();
            $host = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value;
            $mytx['op']         = '/smartpayments/transact.asmx';
            $host .= $mytx['op'];
            $mytx['username']   = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_user_id')->Value;
            $mytx['password']   = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_pass_id')->Value;
            $mytx['amount']     = $this->CI->session->getSessionData('TOTAL');
            $mytx['CardNum']    = $params["CardNum"];
            $mytx['ExpDate']    = $params["ExpMonth"] . substr($params["ExpYear"], 2, 2);
            // $mytx['CVNum']      = $_POST['CVNum'];
            $mytx['NameOnCard'] = $params['NameOnCard'];
            $mytx['TransType']  = 'Sale';
            // $mytx['MagData']    = $_POST['MagData'];
            $mytx['NameOnCard'] = $params['NameOnCard'];
            $mytx['InvNum']     = $this->CI->session->getSessionData('transId');
            $mytx['PNRef']      = '';
            $mytx['Zip']        = $params['Zip'];
            $mytx['Street']     = $params['Street'];
            $mytx['CheckNum']   = '';
            $mytx['TransitNum'] = $params['TransitNum'];
            $mytx['AccountNum'] = $params['AccountNum'];
            // $mytx['MICR']       = $_POST['MICR'];
            $mytx['NameOnCheck']= $params['NameOnCheck'];
            // $mytx['DL']         = $_POST['DL'];
            // $mytx['SS']         = $_POST['SS'];
            // $mytx['DOB']        = $_POST['DOB'];
            $mytx['StateCode']  = '';
            $mytx['CheckType']  = 'Savings';
            $mytx['Pin']        = '';
            $mytx['RegisterNum']    = '';
            $mytx['SureChargeAmt']  = '';
            $mytx['CashBackAmt']    = '';
            $mytx['ExtData']    = '';

            //Generate the string as "key=value" pairs
            $mybuilder = array();
            foreach($mytx as $key=>$value) {
              $mybuilder[] = $key . '='.$value;
            }

            //join the pairs into a single string
            $mystring = implode("&",$mybuilder);
            //print_r($mystringer);
            
            $frontstreamResp = $this->CI->model('custom/frontstream_model')->GuestPaymentProcess($mystring,$host);
            logMessage("Guest Front Stream Response: " . print_r($frontstreamResp, true));
            echo $this->createResponseObject("Success!", array(), "/app/payment/success/t_id/" . $this->CI->session->getSessionData('transId'));
            
            return;

            $this->renderJSON(["code" => "success", "id" => $id]);
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        // echo response
    }

    function createContact($params){
        try{
            $existingContact = $this->CI->model('Contact')->lookupContactByEmail($params["Emails"],$params["CFName"] ? $params["CFName"] : null,$params["CLName"] ? $params["CLName"] : null)->result;
            // logMessage("Guest Contact Email: " . print_r($params["Emails"], true));
            // logMessage("Guest Contact First Name: " . print_r($params["CFName"], true));
            // logMessage("Guest Contact Last Name: " . print_r($params["CLName"], true));
            logMessage("Guest Existing Contact: " . print_r($existingContact, true));
            if($existingContact){
                logMessage("Guest Existing Contact Condition");
                $contact = RNCPHP\Contact::fetch(intval($existingContact));               
                logMessage("Guest Existing Contact Fetch: ". print_r($contact, true));
            }else{
                logMessage("Guest New Contact Condition");
                $contact = new RNCPHP\Contact();

                //add email addresses
                $contact->Emails = new RNCPHP\EmailArray();
                $contact->Emails[0] = new RNCPHP\Email();
                $contact->Emails[0]->AddressType=new RNCPHP\NamedIDOptList();
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
                if(!empty($params["State"])){
                    $contact->Address->StateOrProvince = new RNCPHP\NamedIDLabel();
                    $contact->Address->StateOrProvince->ID = intval($params["State"]);
                }
                if(!empty($params["Country"]))
                 $contact->Address->Country = RNCPHP\Country::fetch(intval($params["Country"]));
                
                 $contact->Address->PostalCode =$params["Zip"]; 

                //Set Login / Pass
                $contact->Login = $params["Emails"];
                $contact->NewPassword = $params["CPass"];
               	$newPassword = $params["CPass"];

                //Hear about us field
                if(strlen($params["AboutUS"]) > 0 )
                $contact->CustomFields->CO->how_did_you_hear =$params["AboutUS"];                 
                
                $contact->save(RNCPHP\RNObject::SuppressAll);
                RNCPHP\ConnectAPI::commit();
                // logMessage("Guest New Contact: " . print_r($contact, true));
            }

        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        return $contact;        
    }

    /**
     * Handles the save_card_payment_method AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_save_card_payment_method($params)
    {
        // Perform AJAX-handling here...
        $xml = "";
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Form Parameters: " . print_r($params, true), "");
        try {            
            $contact = $this->CI->model('Contact')->get()->result;
            logMessage("Guest Contact Object Response: " . print_r($contact, true));
            if($contact)
            $customerKey = $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key;
            // logMessage("Guest Contact Object Response: " . print_r($contact, true));
            if(is_null($contact)){
                logMessage("Guest Contact Checking..!!");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                // logMessage("Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                logMessage("Guest Contact CreateNewCustomer in FrontStream");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;
                $contact->save();
                // logMessage("Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));

            logMessage("**Total = " . $params['Amount']);
            if($params['Amount']){
                $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                $this->CI->session->setSessionData(array("MONTHLY" => $params['monthly']));               
            }

            if(!is_null($contact->CustomFields->c->customer_key)){
                    $data = [
                        "Username" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_user_id')->Value,
                        "Password" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_pass_id')->Value,
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
                    
                    logMessage("Front Stream ManageCredit Info Request =>" . $data);

                    $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/admin/ws/recurring.asmx/ManageCreditCardInfo';
                    $xml = $this->CI->curllibrary->httpPost($url, $data);
                    logMessage("Front Stream Manage Credit Card Info =>" . $xml);
                    $res = $this->CI->xmltoarray->load($xml);

                    if (is_null($res["RecurringResult"]["CcInfoKey"]))
                        throw new \Exception("Ensure all fields have correct information.");

                    $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value .'/ArgoFire/validate.asmx/GetCardType';
                    $xml = $this->CI->curllibrary->httpPost($url, ["CardNumber" => $params["CardNum"]]);
                    logMessage("Front Stream GetCardType =>" . $xml);
                    $cardtype = $this->CI->xmltoarray->load($xml);

                    if (is_null($cardtype["string"]["cdata"]))
                        throw new \Exception("Card Type Lookup Failure. Please Try Again.");

                    $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, $cardtype["string"]["cdata"], null, 1, $params["ExpMonth"], $params["ExpYear"], substr($params["CardNum"], -4), $res["RecurringResult"]["CcInfoKey"])->ID;
                    logMessage("Payment Method ID  =>" . $id);

                    $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                    logMessage("Payment Method Array  =>" . $paymentMethodsArr);

                    if(!is_null($id)){ 
                        $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'),$this->CI->session->getSessionData('DonateDesc'),null);
                        $transactionId = $transObj;
                        $this->CI->session->setSessionData(array("transId" => $transObj));

                        $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                        logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");
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
                            $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "------------");
                            $this->_logToFile(203, "---------");
                            $this->_logToFile(204, "---------");
                            echo $this->createResponseObject("Success!", array(), "/app/payment/donate_success/id/" . $this->CI->session->getSessionData('child_id'));
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
    {// Perform AJAX-handling here...
        $xml = "";
        logMessage("Form Parameters: " . print_r($params, true));
        try {            
            $contact = $this->CI->model('Contact')->get()->result;
            // logMessage("Guest Contact Object Response: " . print_r($contact, true));
            if(is_null($contact)){
                logMessage("Guest Contact Checking..!!");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                // logMessage("Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                logMessage("Guest Contact CreateNewCustomer in FrontStream");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;
                $contact->save();
                // logMessage("Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            logMessage("**Total = " . $params['Amount']);
            if($params['Amount']){
                $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                $this->CI->session->setSessionData(array("MONTHLY" => $params['monthly']));               
            }

            if(!is_null($contact->CustomFields->c->customer_key)){
                $data = [
                    "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_user")->Value,
                    "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_pass")->Value,
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
                    
                    logMessage("Front Stream ManageCredit Info Request =>" . $data);

                    $url = RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_endpoint")->Value . '/admin/ws/recurring.asmx/ManageCheckInfo';
                    $xml = $this->CI->curllibrary->httpPost($url, $data);
                    logMessage("Front Stream Manage Credit Card Info =>" . $xml);
                    $res = $this->CI->xmltoarray->load($xml);

                    if (is_null($res["RecurringResult"]["CcInfoKey"]))
                        throw new \Exception("Ensure all fields have correct information.");


                    $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, "Checking", null, 2, null, null, substr($params["AccountNum"], -4), $res["RecurringResult"]["CheckInfoKey"])->ID;
                    logMessage("Payment Method ID  =>" . $id);

                    $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                    logMessage("Payment Method Array  =>" . $paymentMethodsArr);

                    if(!is_null($id)){ 
                        $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'),$this->CI->session->getSessionData('DonateDesc'),null);
                        $transactionId = $transObj;
                        $this->CI->session->setSessionData(array("transId" => $transObj));

                        $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                        logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");
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
                            echo $this->createResponseObject("Success!", array(), "/app/payment/donate_success/id/" . $this->CI->session->getSessionData('child_id'));
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

            logMessage("In handle_default_ajax_endpoint function...");
            $rawFormDataArr = json_decode($params['formData']);

            if (!$rawFormDataArr) {
                header("HTTP/1.1 400 Bad Request");
                // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
                Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
            }
            logMessage("**Total = " . $params['Amount']);
            if($params['Amount']){
                $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                $this->CI->session->setSessionData(array("MONTHLY" => $params['monthly']));               
            }

            logMessage("Monthly FLAG PARAM >>: " . print_r($params['monthly'], true));

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
            
            logMessage(" Contact ID > " . $this->CI->session->getProfileData('contactID') . " Amount > " . $this->CI->session->getSessionData('TOTAL'));
            
            $transObj = $this->CI->model('custom/transaction_model')->create_transaction($this->CI->session->getProfileData('contactID'), $this->CI->session->getSessionData('TOTAL'),$this->CI->session->getSessionData('DonateDesc'),null);
            
            logMessage("Transaction ID >>" . $transObj);            
            $transactionId = $transObj;
            
            $this->CI->session->setSessionData(array("transId" => $transObj));
            
            $c_id = $this->CI->session->getProfileData('contactID');
            $this->CI->session->setSessionData(array("contact_id" => $c_id));

            // $transactionId = $this->CI->session->getSessionData('transId');
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

            if (is_null($this->CI->session->getSessionData('TOTAL')) ||  !is_numeric($this->CI->session->getSessionData('TOTAL')) ) {
                logMessage("**Total = " . $this->CI->session->getSessionData('TOTAL'));
                $sanityCheckMsgs[] = "Invalid Payment Amount";
            }

            // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
            $transItemType = $this->CI->session->getSessionData('item_type');

            // if ($transItemType === DONATION_TYPE_SPONSOR) {
                logMessage('Running sponsorship transaction. Verifying child record lock is still held by logged in user.');
                //$items = $this->session->getSessionData('items');
                // $items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
                // I think there can only ever be a single child item here but doing a for loop to make this future proof
                // foreach ($items as $item) {
                    ////$this->logging->logVar('Child sponsorship record: ', $item);
                    // $status = $this->CI->model('custom/sponsorship_model')->isChildRecordLocked(intval($this->CI->session->getSessionData('child_id')));
                    ////$this->logging->logVar('Is Child Record Locked?: ', $status->isLocked);
                    ////$this->logging->logVar('Lock Owner: ', $status->lastOwner);
                    $loggedInContactID = $this->CI->session->getProfileData('contactID');
                    //$this->logging->logVar('Logged in contact ID: ', $loggedInContactID);
                    // if (!$item['isWomensScholarship'] && (!$status->isLocked || $status->lastOwner !== $loggedInContactID)) {
                    //     $sanityCheckMsgs[] = "Lock on child record has expired. Please redo transaction.";
                    // }
                // }
            // }

            if (count($sanityCheckMsgs) > 0) {
                echo $this->createResponseObject("Invalid Input", $sanityCheckMsgs);
                return;
            }

            $this->_logToFile(181, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------");
            logMessage("---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------");
           
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
                $this->_logToFile(202, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/id/".$this->CI->session->getSessionData('child_id'). "------------");
                $this->_logToFile(203, "---------");
                $this->_logToFile(204, "---------");
                echo $this->createResponseObject("Success!", array(), "/app/payment/donate_success/id/" . $this->CI->session->getSessionData('child_id'));
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
        logMessage("After Transaction Donation Creation >>: ");
        try {
            //we've successfully accomplished a transaction, create the donation object
            $c_id = $this->CI->session->getSessionData('contact_id');
            $amt = intval($this->CI->session->getSessionData('TOTAL'));

            $lineItemObjs = array();
            $totalOneTime = 0;
            $totalReoccurring = 0;
            $newLineItemObj = new \stdClass;
            $newLineItemObj->itemName = $this->CI->session->getSessionData('DonateDesc');
            $newLineItemObj->childId = !empty($this->CI->session->getSessionData('child_id')) ? $this->CI->session->getSessionData('child_id') : '';
            
            logMessage("Monthly FLAG >>: " . print_r($this->CI->session->getSessionData('MONTHLY'), true));

            if($this->CI->session->getSessionData('MONTHLY') == "0"){            
                $newLineItemObj->oneTime = !empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : '';
                $this->CI->session->setSessionData(array("oneTime_amt" => !empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : ''));
                $this->CI->session->setSessionData(array("monthly_amt" => ""));
                logMessage("OneTime >>: " . print_r(!empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : '', true));
            }
            if($this->CI->session->getSessionData('MONTHLY') == "1"){
                $newLineItemObj->recurring = !empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : '';
                $this->CI->session->setSessionData(array("monthly_amt" => !empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : ''));
                $this->CI->session->setSessionData(array("oneTime_amt" => ""));
                logMessage("Monthly >>: " . print_r(!empty($this->CI->session->getSessionData('TOTAL')) ? $this->CI->session->getSessionData('TOTAL') : '', true));
            }

            logMessage("FUND ID  >>: " . print_r(\RightNow\Utils\Url::getParameter('f_id')));
            $newLineItemObj->fund = \RightNow\Utils\Url::getParameter('f_id');
            $newLineItemObj->appeal = '';
            $newLineItemObj->type = DONATION_TYPE_PLEDGE;

            $lineItemObjs[] = $newLineItemObj;

            // $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            $transactionId = $this->CI->session->getSessionData('transId');
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Creating Donation from paymentmethod: " .  $paymethod->ID, "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Amt:" . $amt . " Contact:" . $c_id . " Transaction:" . $transactionId . " Items:" . $lineItemObjs, "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Donation Fund: " .  $this->CI->session->getSessionData('donation_fund'), "");
            $donationId = $this->CI->model('custom/donation_model')->createDonationAfterTransaction_2($amt, $c_id, $lineItemObjs, $transactionId, $paymethod,$this->CI->session->getSessionData('donation_fund'));
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Create Donation After Transaction Donation ID >>: " . print_r($donationId, true), "");
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
