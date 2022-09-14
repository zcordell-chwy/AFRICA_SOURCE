<?php
namespace Custom\Widgets\shopping;

class DonationShoppingCart extends \Custom\Widgets\shopping\ShoppingCart {
    protected static $WIDGET_SCOPE = 'custom/shopping/DonationShoppingCart';

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->CI->load->helper('constants');
        $this->CI->load->library('Logging');
    }

    function getData() {
        $this->CI->model('custom/items')->clearItemsFromCart($this -> CI -> session -> getSessionData('sessionID'), DONATION_TYPE_GIFT);
        $this->CI->model('custom/items')->clearItemsFromCart($this -> CI -> session -> getSessionData('sessionID'), DONATION_TYPE_SPONSOR);
        return parent::getData();

    }

    /**
    * Initiates a checkout of the donation line items in the shopping cart. Maps the line items from
    * session data for this widget into the line item objects recognized by the old payment pages/routines and stores them in 
    * session data under the expected key 'items'. Also calculates the total and totalRecurring and stores
    * them in session data under 'total' and 'totalRecurring' respectively. Lastly, returns the checkout redirect URL.
    */
    protected function initiateCheckout(){
        $this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'initiateCheckout');
        
        $this->CI->load->model('custom/items');
        $lineItems = $this->getLineItems();

        $lineItemObjs = array();
        $totalOneTime = 0;
        $totalReoccurring = 0;

        foreach($lineItems as $lineItem){
            $newLineItemObj = new \stdClass;
            $newLineItemObj->itemName = $lineItem['merch']['title'];
            $newLineItemObj->oneTime = $lineItem['customData']['amountOneTime'];
            $newLineItemObj->recurring = $lineItem['customData']['amountMonthly'];
            $newLineItemObj->fund = $lineItem['customData']['donationFundID'];
            $newLineItemObj->appeal = $lineItem['customData']['donationAppealID'];;
            $newLineItemObj->type = DONATION_TYPE_PLEDGE;

            $totalOneTime += $newLineItemObj->oneTime;
            $totalReoccurring += $newLineItemObj->recurring;

            $lineItemObjs[] = $newLineItemObj;
        }

        $this->CI->logging->logVar('$lineItemObjs', $lineItemObjs);
        $this->CI->logging->logVar('$totalOneTime', $totalOneTime);
        $this->CI->logging->logVar('$totalReoccurring', $totalReoccurring);

        get_instance()->session->setSessionData(array(
            'items' => $lineItemObjs,
            'total' => $totalOneTime + $totalReoccurring,
            'totalRecurring' => $totalReoccurring,
            'item_type' => DONATION_TYPE_PLEDGE
        ));
        
        logMessage("session items");
        logMessage($this -> CI -> session -> getSessionData('items'));
        logMessage(get_instance()->session->getSessionData('items'));

        $redirectURL = '/app/payment/checkout';
        $this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'initiateCheckout', $redirectURL, 'redirect URL');
        return $redirectURL;
    }
}