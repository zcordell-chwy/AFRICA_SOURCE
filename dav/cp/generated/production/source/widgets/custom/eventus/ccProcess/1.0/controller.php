<?php

namespace Custom\Widgets\eventus;

use \RightNow\Connect\v1_3 as RNCPHP;

class ccProcess extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->CI->load->helper('constants');
    }

    function getData()
    {
        ////logMessage("Starting ccProcess getData");
        $transId = $this->CI->session->getSessionData('transId');
        ////logMessage("Transaction id: " . $transId);
        if ($transId === false) return;

        $this->CI->load->model('custom/transaction_model');
        $trans = $this->CI->transaction_model->get_transaction($transId);
        if ($trans instanceof RNCPHP\financial\transactions) {
            ////logMessage("Transaction Sent");
            $this->data['trans'] = $trans;
        } else {
            ////logMessage("transaction not returned from model");
        }
        //logMessage("profile data:");
        //logMessage($this -> CI -> session -> getProfile());

        $contactObj = $this->CI->model('Contact')->get()->result;
        //logMessage("contactObject: ");
        //logMessage($contactObj);

        $this->data['paymentMethodsArr'] = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs($this->CI->session->getProfileData('c_id'));
        logmessage($this->data['paymentMethodsArr']);

        $jsPmArr = array();
        foreach ($this->data['paymentMethodsArr'] as $pm) {
            $jsPmArr[] = array('id' => $pm->ID);
        }
        $this->data['js']['paymentMethods'] = $jsPmArr;

        $postVals = array();
        $postVals["EmailAddress"] = $contactObj->Emails[0]->Address;
        $postVals["FirstName"] = $contactObj->Name->First;
        $postVals["LastName"] = $contactObj->Name->Last;
        $postVals["PaymentAmount"] = $this->CI->session->getSessionData('total');
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
        //logMessage($postVals);

    }
}
