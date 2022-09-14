<?php
namespace Custom\Widgets\sponsorship;
use RightNow\Connect\v1_4 as RNCPHP;

class SponsorshipPayment extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        
        //Load Library & Helper classes
        $this->CI->load->helper('constants');
        $this->CI->load->library('CurlLibrary');
        $this->CI->load->library('XMLToArray');
    }
    function getData() {
        
        parent::getData();

        $this -> CI -> load -> helper('constants');

    	// Build labels
    	$this->data['js']['labels'] = array(
    		'payNow' => 'pay now',
    		'back' => 'back',
    		'continue' => 'continue',
    	);

        $this->validateTransaction();

        $c_id = $this->CI->session->getProfileData('contactID');
        logMessage('contact id = ' . var_export($c_id, true));

        ///Fetching the existing payment methods
        $existingPaymentMethods = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs();

        logMessage('Session id = ' . var_export($this->CI->session->getSessionData('sessionID'), true));

        //Fetch total due amount for the child sponsor.
        $id = \RightNow\Utils\Url::getParameter('id');
        if (!$id) {
            return parent::getData();
        }
        $child = RNCPHP\sponsorship\Child::fetch($id);
        // $amt = $child->Rate;
        $amt = $child->DisplayedRate;
        $this->data['amount'] = $amt;
        logMessage('total = ' . var_export($amt, true));

        logMessage('Began logging of payment/checkout');
    
    }

    /** validateTransaction function check for the transaction associated should be in pending and been created in the last 24 hours.
     * If not create another one and set the old one to 'expired' */
    function validateTransaction(){

        logMessage('Beginning validate transaction');
        $transId = $this->CI->session->getSessionData('transId');

        //if we don't have a transaction yet, one will be created in the view
        if(empty($transId)){
            return;
        }

        try{
            $transObj = RNCPHP\financial\transactions::fetch(intval($transId));

            logMessage("Last Updated:".date('Y-m-m H:i:s', $transObj->UpdatedTime)."  Status:".$transObj->currentStatus->LookupName);

            if($transObj->UpdatedTime < strtotime('-24 hours') || $transObj->currentStatus ->LookupName != DEFAULT_TRANSACTION_STATUS){
                
                logMessage("Ressetting Transaction:".$transId);

                $sessionData = array('transId' => null);
                $this -> CI -> session -> setSessionData($sessionData);

                //if its pending expire it and make a note.  otherwise just make a note.
                $f_count = count($transObj -> Notes);
                logMessage("Notes count:".$f_count);
                $transObj -> Notes[$f_count] = new RNCPHP\Note();
                $transObj -> Notes[$f_count] -> Text = "Transaction is too old to use or not in the correct Status.  \nLast Updated:".date('Y-m-m H:i:s', $transObj->UpdatedTime)."\nStatus:".$transObj->currentStatus->LookupName;

                logMessage("Testing:".$transObj->currentStatus ->LookupName."=".DEFAULT_TRANSACTION_STATUS);
                if($transObj->currentStatus ->LookupName == DEFAULT_TRANSACTION_STATUS){
                    logMessage("Setting status to expired");
                    $transObj -> currentStatus = RNCPHP\financial\transaction_status::fetch(EXPIRED_TRANSACTION_STATUS);
                }

                $transObj->save();
            }           
        }catch(\Exception $e){
            logMessage($e->getMessage());
            return;
        }
        return;
    }

}