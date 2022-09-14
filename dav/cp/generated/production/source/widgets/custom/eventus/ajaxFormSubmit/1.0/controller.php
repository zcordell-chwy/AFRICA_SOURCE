<?php

namespace Custom\Widgets\eventus;

use RightNow\Connect\v1_3 as RNCPHP;

class ajaxFormSubmit extends \RightNow\Libraries\Widget\Base
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
        $this->data['js']['child_id'] = !empty($this->CI->session->getSessionData('child_id')) ? $this->CI->session->getSessionData('child_id') : '';
        return parent::getData();
    }


    function createNewCustomer($contact)
    {
        $xml = "";
        try {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact ID: " . print_r($contact->ID, true), "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact First : " . print_r($contact->Name->First, true), "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Last : " . print_r($contact->Name->Last, true), "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Last : " . print_r($contact->Emails[0]->Address, true), "");
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
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Manage Customer Response =>" . print_r($xml, true), "");
            $res = $this->CI->xmltoarray->load($xml);

            return $res["RecurringResult"]["CustomerKey"];
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($xml, true), "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
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
            $mytx['NameOnCheck'] = $params['NameOnCheck'];
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
            foreach ($mytx as $key => $value) {
                $mybuilder[] = $key . '=' . $value;
            }

            //join the pairs into a single string
            $mystring = implode("&", $mybuilder);
            //print_r($mystringer);

            $frontstreamResp = $this->CI->model('custom/frontstream_model')->GuestPaymentProcess($mystring, $host);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Front Stream Response: " . print_r($frontstreamResp, true), "");
            echo $this->createResponseObject("Success!", array(), "/app/payment/success/t_id/" . $this->CI->session->getSessionData('transId'));

            return;

            $this->renderJSON(["code" => "success", "id" => $id]);
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        // echo response
    }

    function createContact($params)
    {
        try {
            $existingContact = $this->CI->model('Contact')->lookupContactByEmail($params["Emails"], $params["CFName"] ? $params["CFName"] : null, $params["CLName"] ? $params["CLName"] : null)->result;
            //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest Contact Email: " . print_r($params["Emails"], true));
            //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest Contact First Name: " . print_r($params["CFName"], true));
            //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest Contact Last Name: " . print_r($params["CLName"], true));
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Existing Contact: " . print_r($existingContact, true), "");
            if ($existingContact) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Existing Contact Condition", "");
                $contact = RNCPHP\Contact::fetch(intval($existingContact));
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Existing Contact Fetch: " . print_r($contact, true), "");
            } else {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest New Contact Condition", "");
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
                if (strlen($params["CPass"]) > 1)
                    $contact->NewPassword = $params["CPass"];

                $newPassword = $params["CPass"];

                //Hear about us field
                if (strlen($params["AboutUS"]) > 0)
                    $contact->CustomFields->CO->how_did_you_hear = $params["AboutUS"];

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
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Object Response: " . print_r($contact, true), "");
            if ($contact)
                $customerKey = $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key;
            //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            if (is_null($contact)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Checking..!!", "");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact CreateNewCustomer in FrontStream", "");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;
                $contact->save();
                //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            if (!is_null($contact->CustomFields->c->customer_key)) {
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
                //"CustomerKey" => !empty($contact->CustomFields->c->customer_key) ? $contact->CustomFields->c->customer_key : '',
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream ManageCredit Info Request =>" . print_r($data, true), "");

                if ($params['Amount']) {
                    $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                }

                $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/admin/ws/recurring.asmx/ManageCreditCardInfo';
                $xml = $this->CI->curllibrary->httpPost($url, $data);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Manage Credit Card Info =>" . print_r($xml, true), "");
                $res = $this->CI->xmltoarray->load($xml);
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($res, true), "");

                if (is_null($res["RecurringResult"]["CcInfoKey"]))
                    throw new \Exception("Ensure all fields have correct information.");

                $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/ArgoFire/validate.asmx/GetCardType';
                $xml = $this->CI->curllibrary->httpPost($url, ["CardNumber" => $params["CardNum"]]);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream GetCardType =>" . print_r($xml, true), "");
                $cardtype = $this->CI->xmltoarray->load($xml);

                if (is_null($cardtype["string"]["cdata"]))
                    throw new \Exception("Card Type Lookup Failure. Please Try Again.");

                $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, $cardtype["string"]["cdata"], null, 1, $params["ExpMonth"], $params["ExpYear"], substr($params["CardNum"], -4), $res["RecurringResult"]["CcInfoKey"])->ID;
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Payment Method ID  =>" . $id, "");

                $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Payment Method Array  =>" . print_r($paymentMethodsArr), "");

                if (!is_null($id)) {
                    $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'), $this->CI->session->getSessionData('child_desc'), null);
                    $transactionId = $transObj;
                    $this->CI->session->setSessionData(array("transId" => $transObj));

                    $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                    helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Begining Run Transaction $transId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------", "");
                    $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $payMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE);

                    helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Response: " . print_r($frontstreamResp, true), "");

                    $result = array();

                    if ($frontstreamResp['isSuccess'] === true) {
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "Processing a successful transaction", "");
                        $donationId = $this->afterTransactionDonationCreation($payMethod);

                        if ($donationId === false || $donationId < 1) {
                            echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                            return;
                        }

                        if ($contact->Login !== null) {
                            $profile = $this->CI->model('Contact')->getProfileSid($contact->Login, $params["CPass"] ?: '', $this->CI->session->getSessionData('sessionID'))->result;
                            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest New Contact Profile: " . print_r($profile, true), "");
                            if ($profile !== null && !is_string($profile)) {
                                $this->CI->session->createProfileCookie($profile);
                            }
                        }

                        //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                        $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $payMethod->ID, $frontstreamResp['pnRef']);
                        //$this -> clearCartData();
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "------------", "");
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
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
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Form Parameters: " . print_r($params, true), "");
        try {
            $contact = $this->CI->model('Contact')->get()->result;
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Object Response: " . print_r($contact, true), "");
            $customerKey = $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key;
            if (is_null($contact)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact Checking..!!", "");
                $contact = $this->createContact($params);
                $customerKey = $contact->CustomFields->c->customer_key;
                //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest New Contact Customer Key: " . print_r($contact->CustomFields->c->customer_key, true));
            }
            if (is_null($contact->CustomFields->c->customer_key)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest Contact CreateNewCustomer in FrontStream", "");
                $customerKey = $this->createNewCustomer($contact);
                $contact->CustomFields->c->customer_key = $customerKey;
                $contact->save();
                //  helplog(__FILE__,__FUNCTION__.__LINE__,"Guest Contact Object: " . print_r($contact, true));
            }
            $this->CI->session->setSessionData(array("contact_id" => $contact->ID));
            if (!is_null($contact->CustomFields->c->customer_key)) {
                $data = [
                    "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_user_id")->Value,
                    "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_pass_id")->Value,
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

                helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream ManageCredit Info Request =>" . print_r($data, true), "");

                if ($params['Amount']) {
                    $this->CI->session->setSessionData(array("TOTAL" => $params['Amount']));
                }

                $url = RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_endpoint")->Value . '/admin/ws/recurring.asmx/ManageCheckInfo';
                $xml = $this->CI->curllibrary->httpPost($url, $data);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Manage Credit Card Info =>" . print_r($xml, true), "");
                $res = $this->CI->xmltoarray->load($xml);

                if (is_null($res["RecurringResult"]["CcInfoKey"]))
                    throw new \Exception("Ensure all fields have correct information.");

                // $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value .'/ArgoFire/validate.asmx/GetCardType';
                // $xml = $this->CI->curllibrary->httpPost($url, ["CardNumber" => $params["CardNum"]]);
                //  helplog(__FILE__,__FUNCTION__.__LINE__,"Front Stream GetCardType =>" . $xml);
                // $cardtype = $this->CI->xmltoarray->load($xml);

                // if (is_null($cardtype["string"]["cdata"]))
                //     throw new \Exception("Card Type Lookup Failure. Please Try Again.");

                $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($contact->ID, "Checking", null, 2, null, null, substr($params["AccountNum"], -4), $res["RecurringResult"]["CheckInfoKey"])->ID;
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Payment Method ID  =>" . $id, "");

                $paymentMethodsArr = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($contact->ID);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Payment Method Array  =>" . print_r($paymentMethodsArr, true), "");

                if (!is_null($id)) {
                    $transObj = $this->CI->model('custom/transaction_model')->create_transaction($contact->ID, $this->CI->session->getSessionData('TOTAL'), $this->CI->session->getSessionData('child_desc'), null);
                    $transactionId = $transObj;
                    $this->CI->session->setSessionData(array("transId" => $transObj));

                    $payMethod = RNCPHP\financial\paymentMethod::fetch(intval($id));
                    helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Begining Run Transaction $transId with Paymethod " . $payMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------", "");
                    $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $payMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE);

                    helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Response: " . print_r($frontstreamResp, true), "");

                    $result = array();

                    if ($frontstreamResp['isSuccess'] === true) {
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "Processing a successful transaction", "");
                        $donationId = $this->afterTransactionDonationCreation($payMethod);

                        if ($donationId === false || $donationId < 1) {
                            echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                            return;
                        }

                        if ($contact->Login !== null) {
                            $profile = $this->CI->model('Contact')->getProfileSid($contact->Login, $params["CPass"] ?: '', $this->CI->session->getSessionData('sessionID'))->result;
                            helplog(__FILE__, __FUNCTION__ . __LINE__, "Guest New Contact Profile: " . print_r($profile, true), "");
                            if ($profile !== null && !is_string($profile)) {
                                $this->CI->session->createProfileCookie($profile);
                            }
                        }

                        //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                        $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $payMethod->ID, $frontstreamResp['pnRef']);
                        //$this -> clearCartData();
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/" . "--------Child ID > " . $this->CI->session->getSessionData('child_id'), "");
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
                        helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
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

    function handle_deletePaymethod($params)
    {
        $rawFormDataArr = json_decode($params['formData']);
        helplog(__FILE__, __FUNCTION__ . __LINE__, "delete pay method", "");
        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($rawFormDataArr, true), "");

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
        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($cleanFormArray, true), "");

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
        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($cleanFormArray, true), "");

        $success = false;

        if ($cleanFormArray['pledgeId'] > 0 && $cleanFormArray['payMethodId']) {
            $payMethodObj = RNCPHP\financial\paymentMethod::fetch($cleanFormArray['payMethodId']);
            $success = $this->CI->model('custom/donation_model')->savePayMethodToPledge($cleanFormArray['pledgeId'], $payMethodObj);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "success = " . $success, "");
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


            $rawFormDataArr = json_decode($params['formData']);

            if (!$rawFormDataArr) {
                header("HTTP/1.1 400 Bad Request");
                // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
                Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
            }
            helplog(__FILE__, __FUNCTION__ . __LINE__, "**Total = " . $params['amt'], "");
            if ($params['amt']) {
                $this->CI->session->setSessionData(array("TOTAL" => $params['amt']));
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
                helplog(__FILE__, __FUNCTION__ . __LINE__, "**Total = " . $this->CI->session->getSessionData('TOTAL'), "");
                $sanityCheckMsgs[] = "Invalid Payment Amount";
            }

            // If this is a sponsorship pledge, verify that the child being sponsored is still locked by the user executing the transaction.
            $transItemType = $this->CI->session->getSessionData('item_type');

            // if ($transItemType === DONATION_TYPE_SPONSOR) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, 'Running sponsorship transaction. Verifying child record lock is still held by logged in user.', "");
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
            // if (!$item['isWomensScholarship'] && (!$status->isLocked || $status->lastOwner !== $loggedInContactID)) {
            //     $sanityCheckMsgs[] = "Lock on child record has expired. Please redo transaction.";
            // }
            // }
            // }

            if (count($sanityCheckMsgs) > 0) {
                echo $this->createResponseObject("Invalid Input", $sanityCheckMsgs);
                return;
            }

            helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'))) . "------------", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Begining Run Transaction $transactionId with Paymethod " . $thisPayMethod->ID . " for " . intval($this->CI->session->getSessionData('TOTAL')) . "------------", "");

            $frontstreamResp = $this->CI->model('custom/frontstream_model')->ProcessPayment($transactionId, $thisPayMethod, intval($this->CI->session->getSessionData('TOTAL')), FS_SALE_TYPE);

            helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Response:", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($frontstreamResp, true), "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Response: " . print_r($frontstreamResp, true), "");

            $result = array();

            if ($frontstreamResp['isSuccess'] === true) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Processing a successful transaction", "");
                $donationId = $this->afterTransactionDonationCreation($thisPayMethod);

                if ($donationId === false || $donationId < 1) {
                    echo $this->createResponseObject("The payment processed correctly, but your donation may not have been properly credited.  Please contact donor services", $sanityCheckMsgs);
                    return;
                }

                //need to update status to complete only after donation is associated.  otherwise CPM will not pick up the donation.
                $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $thisPayMethod->ID, $frontstreamResp['pnRef']);
                //$this -> clearCartData();
                helplog(__FILE__, __FUNCTION__ . __LINE__, "---------Ending Run Transaction $transactionId Redirecting to " . "/app/payment/success/t_id/" . $transactionId . "/authCode/" . $this->CI->model('custom/frontstream_model')->authCode . "/id/" . $this->CI->session->getSessionData('child_id') . "------------", "");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "---------", "");
                echo $this->createResponseObject("Success!", array(), "/app/payment/success/id/" . $this->CI->session->getSessionData('child_id'));
                return;
            }

            echo $this->createResponseObject("Error Processing Payment", $this->CI->model('custom/frontstream_model')->getEndUserErrorMsg());
            return;
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
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
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Creating Donation from paymentmethod: " . $paymethod->ID, "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Amt:" . $amt . " Contact:" . $c_id . " Transaction:" . $transactionId . " Items:", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($items, true), "");
            $donationId = $this->CI->model('custom/donation_model')->createDonationAfterTransaction_1($amt, $c_id, $items, $transactionId, $paymethod);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Created donation $donationId", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Create Donation After Transaction Donation ID >>: " . print_r($donationId, true), "");
        } catch (Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
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
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Clearing cart data", "");

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

    // function _logToFile($lineNum, $message)
    // {
    //     $hundredths = ltrim(microtime(), "0");

    //     $fp = fopen('/tmp/pledgeLogs_' . date("Ymd") . '.log', 'a');
    //     fwrite($fp,  date('H:i:s.') . $hundredths . ": ajaxCustomFormSubmit Controller @ $lineNum : " . $message . "\n");
    //     fclose($fp);
    // }
}
