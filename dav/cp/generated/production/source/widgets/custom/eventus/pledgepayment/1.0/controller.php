<?php
namespace Custom\Widgets\eventus;
use \RightNow\Connect\v1_2 as RNCPHP;

class pledgepayment extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        
        $this->setAjaxHandlers(array(
            'setsessionforpledge' => array(
                'method'      => 'setSession',
                'clickstream' => 'custom_action',
            )
        ));

    }

    function getData() {
        parent::getData();
    }
    
    function setSession($args){
        
        logMessage(__FUNCTION__." Line ".__LINE__);
        logMessage($args);
        
        try{
            
        
            $errorMsgs = array();
            
            //clear session data before we start creating the pledge item
            $this->clearSessionData();
            
            $pledge = RNCPHP\donation\pledge::fetch($args['pledgeId']);
            
            if($pledge){
    
                $items = array();
                
                /*$items[] = array (
                    'itemName' => 'Online Payment toward '.$pledge->Descr,
                    'oneTime' => intval($args['pledgeAmount']),
                    'recurring' => 0,
                    'fund' => $pledge->Fund->ID,
                    'appeal' => $pledge->Appeals->ID,
                    'type' => 1,
                    'pledgeId' => $pledge->ID,
                    );*/
                // Pledge item needs to be an obect instead of an associative array for the saveItemsToCart in models/custom/items
                // to function properly. Temporary hack to get things working with storing cart in DB in the short term. Eventual 
                // cart logic overhaul on the way.
                $pledgeItem = new \stdClass();
                $pledgeItem->itemName = 'Online Payment toward '.$pledge->Descr;
                $pledgeItem->oneTime = intval($args['pledgeAmount']);
                $pledgeItem->recurring = 0;
                $pledgeItem->fund = $pledge->Fund->ID;
                $pledgeItem->appeal = $pledge->Appeals->ID;
                $pledgeItem->type = 1;
                $pledgeItem->pledgeId = $pledge->ID;
                $pledgeItem->qty = 1;

                $items[] = $pledgeItem;

                $sessionData = array(
                    'total' => intval($args['pledgeAmount']),
                    'totalRecurring' => null,
                    'items' => $items,
                    'donateValCookieContent' => null,
                    'payMethod' => null,
                    'item_type' => 1,
                );
                
                $this -> CI -> session -> setSessionData($sessionData);

                // Temporary hack for getting single pledge pay items to be stored in the DB.
                // They will also still be getting stored in the session for the time being. We shouldn't have to worry about overrunning 
                // session though since pledge pay items are limited to 1 item per transaction.
                $this->CI->model('custom/items')->saveItemsToCart($this -> CI -> session -> getSessionData('sessionID'), $items);
            }else{
                $errorMsgs[] = "Could not find pledge";
                echo  $this -> createResponseObject("Could not find pledge", $errorMsgs);
            }
        
        }catch(exception $e){
            $errorMsgs[] = $e->getMessage();
            echo  $this -> createResponseObject($e->getMessage(), $errorMsgs);
        }catch(RNCPHP\ConnectAPIError $e) {
            $errorMsgs[] = $e->getMessage();
            echo  $this -> createResponseObject($e->getMessage(), $errorMsgs);   
        }
        
        echo $this -> createResponseObject("Success", $errorMsgs, "/app/payment/checkout", $items);
    }
    
    function clearSessionData() {
        
        $sessionData = array(
            'total' => null,
            'totalRecurring' => null,
            'items' => null,
            'donateValCookieContent' => null,
            'payMethod' => null
        );

        $this -> CI -> session -> setSessionData($sessionData);
        $sessionData = array('transId' => null);
        $this -> CI -> session -> setSessionData($sessionData);

        $this -> CI -> model('custom/items') -> clearItemsFromCart($this->CI->session->getSessionData('sessionID'));
    }
    
    private function createResponseObject($message, array $errors, $redirectLocation = null, $includeObject = null) {
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

 
}