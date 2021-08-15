<?php

namespace Custom\Widgets\eventus;

use \RightNow\Connect\v1_3 as RNCPHP;

class managepaymethods extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
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
    }

    function getData()
    {
        parent::getData();
        try {
            $contact = $this->CI->model('Contact')->get()->result;
            if (is_null($contact->CustomFields->c->customer_key)) {
                $contact->CustomFields->c->customer_key = $this->createNewCustomer($contact);
                $contact->save();
            }
        } catch (\Exception $e) {
            logMessage($contact);
            logMessage($e->getMessage());
        }
    }

    function createNewCustomer($contact)
    {
        $xml = "";
        try {
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
     * Handles the save_card_payment_method AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_save_card_payment_method($params)
    {
        // Perform AJAX-handling here...
        $xml = "";
        try {
            $data = [
                "Username" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_user_id')->Value,
                "Password" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_pass_id')->Value,
                "TransType" => "ADD",
                "Vendor" => RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_vendor')->Value,
                "CustomerKey" => $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key,
                "CardInfoKey" => "",
                "CcAccountNum" => $params["CardNum"],
                "CcExpDate" => $params["ExpMonth"] . substr($params["ExpYear"], 2, 2),
                "CcNameOnCard" => $params["NameOnCard"],
                "CcStreet" => "",
                "CcZip" => "",
                "ExtData" => ""
            ];

            $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/admin/ws/recurring.asmx/ManageCreditCardInfo';
            $xml = $this->CI->curllibrary->httpPost($url, $data);
            logMessage("Front Stream URL for ManageCreditCardInfo =>" . $url);
            logMessage("Front Stream Manage Credit Card Info =>" . $xml);
            $res = $this->CI->xmltoarray->load($xml);

            if (is_null($res["RecurringResult"]["CcInfoKey"]))
                throw new \Exception("Ensure all fields have correct information.");

            $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . '/ArgoFire/validate.asmx/GetCardType';
            $xml = $this->CI->curllibrary->httpPost($url, ["CardNumber" => $params["CardNum"]]);
            logMessage("Front Stream URL for GetCardType =>". $url);
            logMessage("Front Stream GetCardType =>" . $xml);
            $cardtype = $this->CI->xmltoarray->load($xml);

            if (is_null($cardtype["string"]["cdata"]))
                throw new \Exception("Card Type Lookup Failure. Please Try Again.");

            $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($this->CI->model('Contact')->get()->result->ID, $cardtype["string"]["cdata"], null, 1, $params["ExpMonth"], $params["ExpYear"], substr($params["CardNum"], -4), $res["RecurringResult"]["CcInfoKey"])->ID;
            $this->renderJSON(["code" => "success", "id" => $id]);
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
    {
        // Perform AJAX-handling here...
        $xml = "";
        try {
            $data = [
                "Username" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_user_id")->Value,
                "Password" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_pass_id")->Value,
                "TransType" => "ADD",
                "Vendor" => RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_vendor")->Value,
                "CustomerKey" => $this->CI->model('Contact')->get()->result->CustomFields->c->customer_key,
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

            $url = RNCPHP\Configuration::fetch("CUSTOM_CFG_frontstream_endpoint")->Value . '/admin/ws/recurring.asmx/ManageCheckInfo';
            $xml = $this->CI->curllibrary->httpPost($url, $data);

            $res = $this->CI->xmltoarray->load($xml);
            logMessage("Front Stream Manage Check Info: " . $xml);
            if (is_null($res["RecurringResult"]["CheckInfoKey"]))
                throw new \Exception("Ensure all fields have correct information.");

            $id = $this->CI->model('custom/paymentMethod_model')->createPaymentMethod($this->CI->model('Contact')->get()->result->ID, "Checking", null, 2, null, null, substr($params["AccountNum"], -4), $res["RecurringResult"]["CheckInfoKey"])->ID;
            $this->renderJSON(["code" => "success", "id" => $id]);
        } catch (\Exception $e) {
            $this->renderJSON(["code" => "error", "message" => $e->getMessage()]);
        }
        // echo response
    }














    function _disableDeletePayMethods($payMethods)
    {
        $disabledArray = array();

        foreach ($payMethods as $pm) {
            //don't disable 1 time pledges.
            $roql = "Select donation.pledge from donation.pledge where donation.pledge.Frequency != 9 AND donation.pledge.paymentMethod2 = " . $pm->ID;
            $results = RNCPHP\ROQL::queryObject($roql)->next();
            $pledge = $results->next();
            if ($pledge)
                $disabledArray[] = $pm->ID;
        }
        return $disabledArray;
    }
}
