<?php
namespace Custom\Widgets\eventus;

use \RightNow\Connect\v1_2 as RNCPHP;

require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();


class managepaymethods extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'default_ajax_endpoint' => array(
                'method'      => 'handle_default_ajax_endpoint',
                'clickstream' => 'custom_action',
            ),
        ));
        
        $this -> CI -> load -> helper('constants');
    }

    function getData() {

        parent::getData();
        $baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();
        $this -> data['paymentMethodsArr'] = $this -> CI -> model('custom/paymentMethod_model') -> getCurrentPaymentMethodsObjs($this -> CI -> session -> getProfileData('c_id'));
        $this -> data['disabledPayMethods'] = $this -> _disableDeletePayMethods($this -> data['paymentMethodsArr']);

        
        $contactObj = $this -> CI -> model('Contact') -> get() -> result;
        
        
        $postVals = array();
        $postVals["EmailAddress"] = $contactObj -> Emails[0] -> Address;
        $postVals["FirstName"] = $contactObj -> Name -> First;
        $postVals["LastName"] = $contactObj -> Name -> Last;
        //per Ted, generate random number to make it less suspicious to the API
        $postVals["PaymentAmount"] = 1 + (rand(10, 99) / 100);//were just doing a $1 transaction and voiding it to extract the pnref, then voiding
        $postVals["BillingStreetAddress"] = $contactObj -> Address -> Street;
        $postVals["BillingStreetAddress2"] = '';
        $postVals["BillingCity"] = $contactObj -> Address -> City;
        $postVals["BillingStateOrProvince"] = $contactObj -> Address -> StateOrProvince -> LookupName;
        $postVals["BillingPostalCode"] = $contactObj -> Address -> PostalCode;
        $postVals["BillingCountry"] = $contactObj -> Address -> Country -> LookupName;
        $postVals["PaymentButtonText"] = "Add Payment Method";
        $postVals["NotificationFlag"] = "0";
        $postVals["TrackingID"] = "";
        $postVals["StyleSheetURL"] = $baseURL . "/euf/assets/themes/africa/payment2.css";
        $postVals["MerchantToken"] = FS_MERCHANT_TOKEN;
        $postVals["PostbackURL"] = FS_POSTBACK_URL;
        $postVals["PostBackRedirectURL"] = FS_POSTBACK_URL;
        $postVals["PostBackErrorURL"] = FS_POSTBACK_URL;
        $postVals["SetupMode"] = "Direct";
        $postVals["InvoiceNumber"] = "NewPM-".time();
        $postVals["HeaderImageURL"] = FS_HEADER_URL;
        $postVals["DirectUserName"] = FS_USERNAME;
        $postVals["DirectUserToken"] = FS_USERTOKEN;
        $postVals["DirectMerchantKey"] = FS_MERCHANT_KEY;
        $postVals["NotificationType"] = "";
        $this -> data['js']['postToFsVals'] = $postVals;
        $this -> data['js']['postbackUrl'] = FS_POSTBACK_URL;
        $this -> data['js']['consumerEndpoint'] = FS_COMSUMER_ENDPOINT;

    }

    function _disableDeletePayMethods($payMethods){
        $disabledArray = array();
        
        foreach ($payMethods as $pm) {
            //don't disable 1 time pledges.
            $roql = "Select donation.pledge from donation.pledge where donation.pledge.Frequency != 9 AND donation.pledge.paymentMethod2 = ".$pm->ID;
            $results = RNCPHP\ROQL::queryObject($roql) -> next();
            $pledge = $results -> next();
            if($pledge)
                $disabledArray[] = $pm->ID;
           
        }
        return $disabledArray;
    }
    /**
     * Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_default_ajax_endpoint($params) {
        // Perform AJAX-handling here...
        // echo response
    }
}