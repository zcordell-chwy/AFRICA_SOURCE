<?php
namespace Custom\Widgets\payment;

use RightNow\Connect\v1_4 as RNCPHP;
use RightNow\Utils\Framework;

class Thankyou extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    
    function getData() {
    	

    	$id = \RightNow\Utils\Url::getParameter('id');
    	$this->data['childID'] = $id;
    	$this->data['js']['childID'] = $id;
    	
    	logMessage('Thank you PAGE Child ID ' . var_export($id, true));
    	//fetch sponsor child
        if($id){
            logMessage('Thank you PAGE Child ID ' . var_export($id, true));
            $child = RNCPHP\sponsorship\Child::fetch(intval($id));
        	$CommunityName = !empty($child->CommunityName->Name) ? $child->CommunityName->Name : '';
    		$this->data['CommunityName'] = $CommunityName;
    		
        	$c_id = $this->CI->session->getSessionData('contact_id');
        	if($c_id){
        		$contact = RNCPHP\Contact::fetch(intval($c_id)); 
        		if($contact->Name){
        			$this->data['firstName'] = $contact->Name->First;
        		}
        	}
            if ($imageLocation = $this->CI->model('custom/sponsorship_model')->getChildImg($child->ChildRef)) {
                $hasImage = true;
            } else {
                $imageLocation = CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
                $hasImage = false;
            }
            $this->data['image'] = $imageLocation;
        }else{
            if($this->CI->session->getSessionData('contact_id')){
                 $contact = RNCPHP\Contact::fetch(intval($this->CI->session->getSessionData('contact_id'))); 
                if($contact->Name){
                    $this->data['firstName'] = $contact->Name->First;
                }
            }
        }  
        parent::getData();
        
    }
}