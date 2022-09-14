<?php
namespace Custom\Widgets\shopping;

class ChildGiftShoppingCart extends \Custom\Widgets\shopping\ShoppingCart {
    protected static $WIDGET_SCOPE = 'custom/shopping/ChildGiftShoppingCart';

    function __construct($attrs) {
        parent::__construct($attrs);
        $this->CI->load->helper('constants');
        $this->CI->load->library('Logging');
    }

    function getData() {

        $this->data['cospon'] = $this->data['js']['cospon'] = getUrlParm('cospon');
        $this->CI->model('custom/items')->clearItemsFromCart($this -> CI -> session -> getSessionData('sessionID'), DONATION_TYPE_PLEDGE);
        $this->CI->model('custom/items')->clearItemsFromCart($this -> CI -> session -> getSessionData('sessionID'), DONATION_TYPE_SPONSOR);
        return parent::getData();

    }

    /**
     * Overridable methods from ShoppingCart:
     */
    // function initializeShoppingCart()
    // function handle_initiateCheckoutAJAX($params)
    // function handle_removeLineItemFromShoppingCartSessionDataAJAX($params)
    // function handle_addLineItemToShoppingCartSessionDataAJAX($params)
    // function handle_updateLineItemQtyInShoppingCartSessionDataAJAX($params)
    // protected function initiateCheckout()

    /**
    * Initiates a checkout of the child gift line items in the shopping cart. Maps the line items from
    * session data for this widget into the line item objects recognized by the old payment pages/routines and stores them in 
    * session data under the expected key 'items'. Also calculates the total and totalRecurring and stores
    * them in session data under 'total' and 'totalRecurring' respectively. Lastly, returns the checkout redirect URL.
    */
    
    function handle_initiateCheckoutAJAX($params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_initiateCheckoutAJAX', array('$params' => $params));
        logMessage($params);
        $holidayResult = json_decode($this->checkHolidayGiving());

        if($holidayResult->okToAdd == "false"){
            
            $msg = getMessage(CUSTOM_MSG_HOLIDAY_GIFT_POLICY_MESSAGE_POP)."<br/><br/>Previous Orders and Items in cart total:</br></br>";
            foreach($holidayResult->Child as $child){
                if($child->Over == true){
                    $msg .= $child->Name. ": ".$child->HolidayGiftsTotal." gifts<br/>";
                }
            }
            echo json_encode($this->getDefaultErrorResponse($msg));
            return;
        }
        
        
        logMessage($params);
        try{
            $redirectURL = $this->initiateCheckout();
            if(empty($redirectURL)) throw new \Exception('Checkout redirect URL is empty');
        }catch(\Exception $e){
            $msg = 'Error encountered while initiating checkout: ' . $e->getMessage();
            //////logMesage($msg);
            
            return;
        }

        echo json_encode($this->getDefaultSuccessResponse($redirectURL));
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_initiateCheckoutAJAX');
    }
    
    protected function initiateCheckout(){
        $this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'initiateCheckout');
        
        $this->CI->load->model('custom/items');
        $lineItems = $this->getLineItems();
        

        $lineItemObjs = array();
        $total = 0;

        foreach($lineItems as $lineItem){
            $newLineItemObj = new \stdClass;
            $newLineItemObj->itemName = $lineItem['merch']['title'];
            $newLineItemObj->qty = $lineItem['quantity'];
            // Don't use the price we received from the view as that presents a security vulnerability. Re-lookup the item's
            // price from the database and use that.
            $merchItem = $this->CI->items->getitemdetails($lineItem['merch']['id']);
            $newLineItemObj->oneTime = $merchItem->Amount;
            $newLineItemObj->recurring = 0;
            $newLineItemObj->fund = null;
            $newLineItemObj->appeal = null;
            $newLineItemObj->giftId = $lineItem['merch']['id'];
            $newLineItemObj->childId = $lineItem['customData']['childID'];
            $newLineItemObj->type = DONATION_TYPE_GIFT;
            $newLineItemObj->childName = $lineItem['customData']['childName'];

            $total += $newLineItemObj->oneTime * $newLineItemObj->qty;

            $lineItemObjs[] = $newLineItemObj;
        }

        $this->CI->logging->logVar('$lineItemObjs', $lineItemObjs);
        $this->CI->logging->logVar('$total', $total);

        get_instance()->session->setSessionData(array(
            //'items' => $lineItemObjs,
            'total' => $total,
            'totalRecurring' => 0,
            'item_type' => DONATION_TYPE_GIFT
        ));

        $redirectURL = '/app/payment/checkout';
        $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'initiateCheckout', $redirectURL, 'redirect URL');
        return $redirectURL;
    }
}