<?php
namespace Custom\Models;

use RightNow\Connect\v1_3 as RNCPHP;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

class mail_model  extends \RightNow\Models\Base {
    function __construct() {
        parent::__construct();
        //This model would be loaded by using $this->load->model('custom/frontstream_model');

    }

    public function sendStatement($contactid)
    {
        
        $errorMsgs = array();
        $contact = RNCPHP\Contact::fetch($contactid);
        if ($contact->ID > 0){
            //send email
            $statementMailSend = -1;
            $giftReceiptMailSend = RNCPHP\Mailing::SendMailingToContact($contact, null, 11, time());
            if ($giftReceiptMailSend != 1) {
                $errorMsgs[] = "Could Not send mailing to contact. General Mailing Error";
                return $errorMsgs;
            } else {
                return true;
            }
            
        }else{
            $errorMsgs[] = "Contact ID is not valid";
            return $errorMsgs;
        }
    	      
    }

    
}
