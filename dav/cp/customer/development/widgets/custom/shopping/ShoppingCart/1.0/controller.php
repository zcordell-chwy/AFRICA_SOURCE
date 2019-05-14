<?php
namespace Custom\Widgets\shopping;

class ShoppingCart extends \RightNow\Libraries\Widget\Base {
    protected static $WIDGET_SCOPE = 'custom/shopping/ShoppingCart';

    function __construct($attrs){
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'getLineItemsAJAX' => array(
                'method'      => 'handle_getLineItemsAJAX',
                'clickstream' => 'custom_action',
            ),
            'initiateCheckoutAJAX' => array(
                'method'      => 'handle_initiateCheckoutAJAX',
                'clickstream' => 'custom_action',
            ),
            'removeLineItemFromShoppingCartSessionDataAJAX' => array(
                'method'      => 'handle_removeLineItemFromShoppingCartSessionDataAJAX',
                'clickstream' => 'custom_action',
            ),
            'addLineItemsToShoppingCartSessionDataAJAX' => array(
                'method'      => 'handle_addLineItemsToShoppingCartSessionDataAJAX',
                'clickstream' => 'custom_action',
            ),
            'updateLineItemQtyInShoppingCartSessionDataAJAX' => array(
                'method'      => 'handle_updateLineItemQtyInShoppingCartSessionDataAJAX',
                'clickstream' => 'custom_action',
            ),
            'updateLineItemCustomDataInShoppingCartSessionDataAJAX' => array(
                'method'      => 'handle_updateLineItemCustomDataInShoppingCartSessionDataAJAX',
                'clickstream' => 'custom_action',
            ),
            'checkHolidayGivingMaxAJAX' => array(
                'method'      => 'checkHolidayGiving',
                'clickstream' => 'custom_action',
            ),
        ));

        $this->CI->load->library('Logging');
    }

    function getData(){
        return parent::getData();

    }

    function checkHolidayGiving(){
        return $this->CI->model('custom/items')->okToAddItems($this -> CI -> session -> getSessionData('sessionID'), json_encode($this->getLineItems()));
    }

    /**
     * Handles the 'getLineItemsAJAX' AJAX request. Returns all line items in the shopping cart.
     * @param array $params Get / Post parameters
     */
    function handle_getLineItemsAJAX($params){
        try{
            logMessage($params);
    
            $response = array(
                'status' => 'success',
                'lineItems' => $this->getLineItems()
            );
            echo json_encode($response);
       }catch(\Exception $e) {
           
           $this->writeToTmp($e->getMessage());
       }catch(RNCPHP\ConnectAPIError $e) {
        
           $this->writeToTmp($e->getMessage());
       }
    }

    function writeToTmp($msg){
        if($msg != ""){
            $file = fopen("/tmp/getLineItemLogging.txt","a");
            fwrite($file,date("Ymd h:i:s  :  ").$msg."\n");
            fclose($file);
        }
        
    }

    /**
     * Handles the 'initiateCheckoutAJAX' AJAX request. Facilitates initiating a checkout of the items in the 
     * shopping cart session data.
     * @param array $params Get / Post parameters
     */
    function handle_initiateCheckoutAJAX($params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_initiateCheckoutAJAX', array('$params' => $params));

        try{
            $redirectURL = $this->initiateCheckout();
            if(empty($redirectURL)) throw new \Exception('Checkout redirect URL is empty');
        }catch(\Exception $e){
            $msg = 'Error encountered while initiating checkout: ' . $e->getMessage();
            return;
        }

        echo json_encode($this->getDefaultSuccessResponse($redirectURL));
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_initiateCheckoutAJAX');
    }

    /**
     * Handles the 'removeLineItemFromShoppingCartSessionDataAJAX' AJAX request. Removes a line item from 
     * the shopping cart session data.
     * @param array $params Get / Post parameters
     */
    function handle_removeLineItemFromShoppingCartSessionDataAJAX($params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_removeLineItemFromShoppingCartSessionDataAJAX', array('$params' => $params));
        
        if(!$this->areParamsValid(array('lineItemID'), $params)) return;   
        $id = $params['lineItemID'];

        //$this->logLineItems();
        $this->removeLineItem($id);

        //////logMesage("Line item with ID = $id removed successfully.");
        //$this->logLineItems();

        echo json_encode($this->getDefaultSuccessResponse());
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_removeLineItemFromShoppingCartSessionDataAJAX');
    }

    /**
     * Handles the 'addLineItemsToShoppingCartSessionDataAJAX' AJAX request. Adds one or more new line items to the shopping
     * cart session data.
     * @param array $params Get / Post parameters
     */
    function handle_addLineItemsToShoppingCartSessionDataAJAX($params){
        
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_addLineItemsToShoppingCartSessionDataAJAX', array('$params' => $params));

        $lineItemObjs = json_decode($params['lineItems']);

        //$this->CI->logging->logVar('lineItems', $lineItems);
        //$lineItemIDSequence = get_instance()->session->getSessionData('lineItemIDSequence');

        // Convert line item objects to associative arrays, since there is an issue with storing objects in session data
        // whereby they will seemingly randomly be converted to associative arrays on subsequence ajax requests.....mysterious
        // I know
        $lineItems = array();
        foreach($lineItemObjs as $lineItemObj){
            $newLineItem = array();
            $newLineItem['id'] = '';
            $newLineItem['merch'] = array();
            $newLineItem['merch']['id'] = $lineItemObj->merch->id;
            $newLineItem['merch']['title'] = $lineItemObj->merch->title;
            $newLineItem['merch']['price'] = $lineItemObj->merch->price;
            $newLineItem['quantity'] = $lineItemObj->quantity;
            $newLineItem['customData'] = array();
            foreach($lineItemObj->customData as $customDataKey => $customDataValue){
                $newLineItem['customData'][$customDataKey] = $customDataValue;
            }

            $dbLineItem = $this->addLineItem($newLineItem);
            //$this->CI->logging->logVar('Line item added', $newLineItem);
            $lineItems[] = $dbLineItem[0];
        }
        //get_instance()->session->setSessionData(array('lineItemIDSequence' => $lineItemIDSequence));

        $this->getLineItems();
        //logMesage('lineItems before return');
        //logMesage($lineItems);
        echo json_encode($this->getDefaultSuccessResponse($lineItems));
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_addLineItemsToShoppingCartSessionDataAJAX');
    }

    /**
     * Handles the 'updateLineItemQtyInShoppingCartSessionDataAJAX' AJAX request. Updates a line item's quantity in
     * the shopping cart session data.
     * @param array $params Get / Post parameters
     */
    function handle_updateLineItemQtyInShoppingCartSessionDataAJAX($params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_updateLineItemQtyInShoppingCartSessionDataAJAX', array('$params' => $params));

        if(!$this->areParamsValid(array('lineItemID', 'qtyDelta'), $params)) return;  
        $id = $params['lineItemID'];
        $qtyDelta = $params['qtyDelta'];

        $this->updateLineItemQty($id, $qtyDelta);

        //////logMesage("Item with ID = $id updated successfully.");
        $this->logLineItems();

        echo json_encode($this->getDefaultSuccessResponse());
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_updateLineItemQtyInShoppingCartSessionDataAJAX');
    }

    /**
     * Handles the 'updateLineItemCustomDataInShoppingCartSessionDataAJAX' AJAX request. Updates a line item's custom data in
     * the shopping cart session data.
     * @param array $params Get / Post parameters
     */
    function handle_updateLineItemCustomDataInShoppingCartSessionDataAJAX($params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'handle_updateLineItemCustomDataInShoppingCartSessionDataAJAX',  array('$params' => $params));

        if(!$this->areParamsValid(array('lineItemID'), $params)) return;
        $id = $params['lineItemID'];
        $updatedCustomData = json_decode($params['updatedCustomData']);

        try{
            $this->updateLineItemCustomData($id, $updatedCustomData);
        }catch(\Exception $e){
            $msg = 'Error encountered while updating custom data of line item: ' . $e->getMessage();
            //////logMesage($msg);
            echo json_encode($this->getDefaultErrorResponse($msg));
            return;
        }

        echo json_encode($this->getDefaultSuccessResponse($updatedCustomData));
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'handle_updateLineItemCustomDataInShoppingCartSessionDataAJAX');
    }

    /**
    * Initiates a checkout of the line items in the shopping cart. To be implemented by inerhiting class that implements a specific
    * shopping cart. Should return the checkout redirect URL. If an error is encountered, exception should be thrown and invoking function will
    * catch and report it.
    */
    protected function initiateCheckout(){
        // To be implemented by inheriting class
        return '';
    }

    /**
    * Helper method to check if paramters to AJAX methods are correct and, if not, return appropriate
    * error response.
    * @param array $paramList the list of parameter names to check in $params
    * @param array $params the list parameter data
    */
    protected function areParamsValid($paramList, &$params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'areParamsValid', array('$paramList' => $paramList, '$params' => $params));

        try{
            $this->validateParams($paramList, $params);
        }catch(\Exception $e){
            $msg = 'Invalid parameters: ' . $e->getMessage();
            //////logMesage($msg);
            echo json_encode($this->getDefaultErrorResponse($msg));
            //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'areParamsValid', false);
            return false;
        }
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'areParamsValid', true);
        return true;
    }

    /**
    * Helper method to validate AJAX method parameter data. Throws exceptions if data does not match what is expected.
    * @param array $paramList the list of parameter names to check in $params
    * @param array $params the list parameter data
    */
    protected function validateParams($paramList, &$params){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'validateParams', array('$paramList' => $paramList, '$params' => $params));

        foreach($paramList as $paramName){
            switch($paramName){
                case 'lineItemID':
                    if(!is_numeric($params['lineItemID']) || intval($params['lineItemID']) != $params['lineItemID']) throw new \Exception("Line item ID must be an interger, '{$params['lineItemID']}' given.");
                    $params['lineItemID'] = intval($params['lineItemID']);
                    $lineItem = $this->getLineItem($params['lineItemID']);
                    if($lineItem === null) throw new \Exception("Line item with ID = {$params['lineItemID']} not found.");
                break;
                case 'merchID':
                    if(!is_numeric($params['merchID']) || intval($params['merchID']) != $params['merchID']) throw new \Exception("Merchandise ID must be an interger, '{$params['merchID']}' given.");
                    $params['merchID'] = intval($params['merchID']);
                break;
                case 'merchPrice':
                    if(!is_numeric($params['merchPrice'])) throw new \Exception("Merchandise price must be numeric, '{$params['merchPrice']}' given.");
                    $params['merchPrice'] = floatval($params['merchPrice']);
                break;
                case 'quantity':
                    if(!is_numeric($params['quantity']) || intval($params['quantity']) != $params['quantity']) throw new \Exception("Quantity must be an interger, '{$params['quantity']}' given.");
                    $params['quantity'] = intval($params['quantity']);
                    if($params['quantity'] === 0) throw new \Exception("Quantity cannot be 0.");
                break;
                case 'qtyDelta':
                    if(!is_numeric($params['qtyDelta']) || intval($params['qtyDelta']) != $params['qtyDelta']) throw new \Exception("qtyDelta must be an interger, '{$params['qtyDelta']}' given.");
                    $params['qtyDelta'] = intval($params['qtyDelta']);
                break;
            }
        }

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'validateParams');
    }

    /**
    * Convenience method for returning a line item from the shopping cart session storage.
    * @param integer $id the ID of the line item to get
    * @param boolean $indexOnly true if you just want the index of the line item, false if you want the actual line item object  
    */
    protected function getLineItem($id, $indexOnly = false){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'getLineItem', array('$id' => $id, '$indexOnly' => $indexOnly));

        $lineItems = $this->getLineItems();
        logMessage($lineItems);
        $lineItemCnt = count($lineItems);
        $lineItem = null;
        $lineItemIndex = null;
        $itemFound = false;

        for($i = 0; $i < $lineItemCnt; $i++){
            //$this->CI->logging->logVar("\$lineItems[$i]", $lineItems[$i]);
            if($lineItems[$i]['id'] === $id){
                $lineItem = $lineItems[$i];
                $lineItemIndex = $i;
                $itemFound = true;
                break;
            }
        }

        if(!$itemFound) throw new \Exception("Line item with ID = $id not found.");

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'getLineItem', $indexOnly ? $lineItemIndex : $lineItem, $indexOnly ? '$lineItemIndex' : '$lineItem');
        return $indexOnly ? $lineItemIndex : $lineItem;
    }

    /**
    * Convenience method for adding a line item.
    * @param object $newLineItem the new line item to add
    */
    protected function addLineItem($newLineItem){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'addLineItem',array('$newLineItem' => $newLineItem));

        //$lineItems = $this->getLineItems();
        //logMesage("in line items before add");
        //logMesage($lineItems);
        $lineItems[] = $newLineItem;
        //logMesage("in line items after add");
        //logMesage($lineItems);
        $dbLineItem = $this->setLineItems($lineItems);
        //logMesage('dbLineItem');
        //logMesage($dbLineItem);
        return $dbLineItem;
        //$this->logLineItems();
        //$this->getLineItems();

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'addLineItem');
    }

    /**
    * Conveneince method for removing a line item from the shopping cart.
    * @param integer $id the ID of the line item to remove
    */
    protected function removeLineItem($id){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'removeLineItem',array('$id' => $id));
        return $this->CI->model('custom/items')->removeItemFromCart($this -> CI -> session -> getSessionData('sessionID'), $id);
        // $lineItems = $this->getLineItems();
        // $lineItemIndex = $this->getLineItem($id, true);
        // array_splice($lineItems, $lineItemIndex, 1);
        // $this->setLineItems($lineItems, "delete");

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'removeLineItem');
    }

    /**
    * Convenience method for updating the quantity of a line item.
    * @param integer $id the ID of the line item to update the quantity of
    * @param integer $qtyDelta the positive/negative change in quantity for the line item
    */
    protected function updateLineItemQty($id, $qtyDelta){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'updateLineItemQty',array('$id' => $id, '$qtyDelta' => $qtyDelta));

        return $this->CI->model('custom/items')->updateItem($this -> CI -> session -> getSessionData('sessionID'), $id, $qtyDelta, null);
        // $lineItems = $this->getLineItems();
        // $updatedLineItem = $this->getLineItem($id);
        // $updatedLineItem['quantity'] = $updatedLineItem['quantity'] + $qtyDelta;
        // $lineItemIndex = $this->getLineItem($id, true);
        // $lineItems[$lineItemIndex] = $updatedLineItem;
        // $this->setLineItems($lineItems);  

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'updateLineItemQty');
    }

    /**
    * Convenience method for updating the custom data of a line item.
    * @param integer $id the ID of the line item to update the custom data of
    * @param object $updatedCustomData the object containing the updated custom data values
    */
    protected function updateLineItemCustomData($id, $updatedCustomData){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'updateLineItemCustomData',array('$id' => $id, '$updatedCustomData' => $updatedCustomData));

        
        
        //$lineItems = $this->getLineItems();
        //$updatedLineItem = $this->getLineItem($id);
        
        return $this->CI->model('custom/items')->updateItem($this -> CI -> session -> getSessionData('sessionID'), $id, null , $updatedCustomData);
        
        // $lineItemIndex = $this->getLineItem($id, true);
        // $lineItems[$lineItemIndex] = $updatedLineItem;
        // $this->setLineItems($lineItems);

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'updateLineItemCustomData');
    }

    /**
    * Convenience method for returning shopping cart line items from session storage.
    */
    protected function getLineItems(){

        //$shoppingCartData = get_instance()->session->getSessionData($this->getShoppingCartSessionKey());
        $shoppingCartData = $this -> CI -> model('custom/items') -> getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'cart');
        //logMesage("getLineItems");
        //logMesage($shoppingCartData);
        
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'getLineItems', $shoppingCartData['lineItems'], 'Line items');
        return $shoppingCartData;
    }

    /**
    * Convenience method for updating shopping cart line items in session storage.
    * @param array $updatedLineItems the array of updated line items
    */
    protected function setLineItems($updatedLineItems){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'setLineItems',array('$updatedLineItems' => $updatedLineItems));

        // $newShoppingCartSessionData = array(
            // $this->getShoppingCartSessionKey() => array('lineItems' => $updatedLineItems)
        // );
        
        //$dBnewShoppingCartSessionData = $this->CI->model('custom/items')->saveItemsToCart($this -> CI -> session -> getSessionData('sessionID'), $updatedLineItems, $total);
        //logMesage("updated line items");
        //logMesage($updatedLineItems);
        
        return $this->CI->model('custom/items')->saveItemsToCart($this -> CI -> session -> getSessionData('sessionID'), $updatedLineItems);
        
        // $newShoppingCartSessionData = array(
            // $this->getShoppingCartSessionKey() => array('lineItems' => $this->CI->model('custom/items')->saveItemsToCart($this -> CI -> session -> getSessionData('sessionID'), $updatedLineItems, $total))
        // );
        //$this->CI->logging->logVar('Updated shopping cart session data', $newShoppingCartSessionData);
        //logMesage("Updated shopping cart session data");
        //logMesage($newShoppingCartSessionData);
        //logMesage("DB Updated shopping cart session data");
        //logMesage($dBnewShoppingCartSessionData);
        //get_instance()->session->setSessionData($newShoppingCartSessionData);

        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'setLineItems');
    }

    /**
    * Convenience method for logging line items in shopping cart.
    */
    protected function logLineItems(){
        //$this->CI->logging->logFunctionCall(self::$WIDGET_SCOPE, 'logLineItems');
        $lineItems = $this->getLineItems();
        //$this->CI->logging->logVar('Line items', $lineItems);
        //$this->CI->logging->logFunctionReturn(self::$WIDGET_SCOPE, 'logLineItems');
    }

    /**
    * Returns the default error response array.
    * @param string $message the error message to include in the error response
    */
    protected function getDefaultErrorResponse($message){
        return array(
            'status' => 'error',
            'msg' => $message
        );
    }

    /**
    * Returns the default success response array.
    * @param any $data the optional response data
    */
    protected function getDefaultSuccessResponse($data=null){
        if(is_null($data)){
            return array(
                'status' => 'success'
            );
        }else{
            return array(
                'status' => 'success',
                'data' => $data
            );
        }
    }

}

