<?php
namespace Custom\Models;

use RightNow\Connect\v1_3 as RNCP;
use RightNow\Utils\Config;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');

initConnectAPI(); 

class items extends  \RightNow\Models\Base {

    function __construct() {
        parent::__construct();
        //This model would be loaded by using $this->load->model('custom/Sample_model');
        $this->CI->load->helper('log');
        $this->CI->load->helper('constants');
    }
    
    public function saveItemsToCart($sessionID, $items){   
        logMessage('saveItemsToCart with $items = ' . var_export($items, true));
        if(!$sessionID){ return "error"; }
        $itemFieldsToSet = array('itemName', 'qty', 'oneTime', 'recurring', 'fund', 'appeal', 'giftId', 'childId', 'type', 'childName', 'childImgURL', 'pledgeId', 'isWomensScholarship' );
        $itemObjs = array();
        foreach($items as $item){
            logMessage('current item = ' . var_export($item, true));
            $cartItem = new RNCP\Shopping\Cart;
            $cartItem->SessionID = $sessionID;
            // $item will be an array when this routine is invoked from shopping cart widget, otherwise it will be an object, so
            // handle accordingly
            if(is_array($item)){
                logMessage('Processing item as array');
                $cartItem->itemName = $item['merch']['title'];
                $cartItem->qty = $item['quantity'];
                // Set oneTime appropriately depending on whether item is gift or donation
                $cartItem->oneTime = isset($item['customData']['amountOneTime']) ? $item['customData']['amountOneTime'] : $item['merch']['price'];
                $cartItem->recurring = $item['customData']['amountMonthly'];
                $cartItem->fund = $item['customData']['donationFundID'];
                $cartItem->appeal = $item['customData']['donationAppealID'];;
                $cartItem->giftId = $item['merch']['id'];
                $cartItem->childId = $item['customData']['childID'];
                //type fund or missionary: both are donations
                $cartItem->type = ($item['customData']['donationType'] == "fund" || 
                                    $item['customData']['donationType'] == "missionary")? DONATION_TYPE_PLEDGE: DONATION_TYPE_GIFT;
                $cartItem->childName = $item['customData']['childName'];
                $cartItem->childImgURL = $item['customData']['childImgURL'];
                $cartItem->isWomensScholarship = ($item['isWomensScholarship']) ? true : false;
            }else{
                logMessage('Processing item as object');
                foreach($itemFieldsToSet as $itemField){
                    if(isset($item->$itemField)){
                        $cartItem->$itemField = $item->$itemField;
                    }
                }
            }
            logMessage("in save items to cart saving cart item");
            $cartItem->save();
            //update it with the cart ID for the ID if we were invoked from shopping cart widget
            if(is_array($item)){
                $item['id'] = $cartItem->ID;
            }
            $itemObjs[] = $item;  
        }
        
        return $itemObjs;
    }

    //remove 1 item from cart
    public function removeItemFromCart($sessionId, $cartId){
        try{
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId' and Shopping.Cart.ID = $cartId";
            $res = RNCP\ROQL::queryObject( $roql)->next();
            while($cartItem = $res->next()) {
                //logMesage("Destroying cart item".$cartItem->ID);
                $cartItem->destroy();
            }
        }catch(Exception $e){
            echo "error - $e </br>";
        }

        return 'success';
    }
    
    public function updateItem($sessionId, $cartId, $qtyDelta = 0, $updatedCustomData = null){
        $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId' and Shopping.Cart.ID = $cartId";
        logMessage($roql);
        $res = RNCP\ROQL::queryObject( $roql)->next();
        while($cartItem = $res->next()) {
            
            if($qtyDelta != 0){
                $cartItem->qty = $cartItem->qty + $qtyDelta;
            }
            
            if($updatedCustomData){
                logMessage("updated custom data");
                logMessage($updatedCustomData);
                foreach($updatedCustomData as $customDataKey => $customDataValue){
                    //if(!array_key_exists($customDataKey, $updatedLineItem['customData'])) throw new \Exception("Invalid custom data key: $customDataKey");
                    logMessage("changing custom data");
                    logMessage("key = $customDataKey value = $customDataValue");
                    if($customDataKey == "amountMonthly"){
                        $cartItem->recurring = $customDataValue;
                    }else if($customDataKey == "amountOneTime"){
                        $cartItem->oneTime = $customDataValue;
                    }
                    
                }
            }
            logMessage("saving cart item");
            $cartItem->save();
        }

        return 'success';
    }
    
    //remove all items of a certain type from cart
    public function clearItemsFromCart($sessionId, $donationType = null, $transId = null){
        try{
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId'";
            if($donationType){
                $roql .= " and Shopping.Cart.type = $donationType";
            }
            logMessage($roql);
            $res = RNCP\ROQL::queryObject( $roql)->next();
            
            if ($res->count() < 1 && !empty($transId)) {
                $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.transId = ".intval($transId);
                logMessage($roql);
                $res = RNCP\ROQL::queryObject( $roql)->next();
            }

            while($cartItem = $res->next()) {
                logMessage("Destroying cart item".$cartItem->ID);
                $cartItem->destroy();
            }
        }catch(Exception $e){
            echo "error - $e </br>";
        }catch(RNCP\ConnectAPIError $e) {
            logMessage("RNCPHP Exception: ".$e->getMessage());
        }
        
        return 'success';
    }
    
    /*$format: different data structures for cart and checkout:  'cart' || 'checkout'*/
    public function getItemsFromCart($sessionId, $format, $transId = null){
        
        $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId'";
        logMessage($roql);
        $res = RNCP\ROQL::queryObject( $roql)->next();

        if ($res->count() < 1 && !empty($transId)) {
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.transId = ".intval($transId);
            logMessage($roql);
            $res = RNCP\ROQL::queryObject( $roql)->next();
        }

        try{
            
            $lineItemObjs = array();

            while($cartItem = $res->next()) {
                logMessage("Retreive cart item".$cartItem->ID);
                
                if($format == 'checkout'){
                    $newLineItemObj = array();
                    $newLineItemObj['itemName'] = $cartItem->itemName;
                    $newLineItemObj['qty'] = $cartItem->qty;
                    $newLineItemObj['oneTime'] = $cartItem->oneTime;
                    $newLineItemObj['recurring'] = $cartItem->recurring;
                    $newLineItemObj['fund'] = $cartItem->fund;
                    $newLineItemObj['appeal'] = $cartItem->appeal;
                    $newLineItemObj['giftId'] = $cartItem->giftId;
                    $newLineItemObj['childId'] = $cartItem->childId;
                    $newLineItemObj['type'] = $cartItem->type;
                    $newLineItemObj['childName'] = $cartItem->childName;
                    $newLineItemObj['cartId'] = $cartItem->ID;
                    $newLineItemObj['pledgeId'] = $cartItem->pledgeId;
                    $newLineItemObj['isWomensScholarship'] = $cartItem->isWomensScholarship;
        
                    $lineItemObjs[] = $newLineItemObj;
                }else if($format == "cartIds"){//for getting id's only to check if cart items exist
                    $lineItemObjs[] = $cartItem->ID;    
                }else{
                    $newLineItem = array();
                    $newLineItem['id'] = $cartItem->ID;
                    $newLineItem['merch'] = array();
                    $newLineItem['merch']['id'] = $cartItem->giftId;
                    $newLineItem['merch']['title'] = $cartItem->itemName;
                    // Shopping cart widget expects price to be numeric so convert it
                    $newLineItem['merch']['price'] = floatval($cartItem->oneTime);
                    $newLineItem['quantity'] = $cartItem->qty;
                    $newLineItem['pledgeId'] = $cartItem->pledgeId;
                    $newLineItem['customData'] = array();
                    //gift items
                    if($cartItem->childId)
                        $newLineItem['customData']['childID'] = $cartItem->childId;
                    if($cartItem->childName)
                        $newLineItem['customData']['childName'] = $cartItem->childName;
                    if($cartItem->childImgURL)
                        $newLineItem['customData']['childImgURL'] = $cartItem->childImgURL;
                    //donation items
                    if($cartItem->fund)
                        $newLineItem['customData']['donationFundID'] = $cartItem -> fund ;  
                    if($cartItem->appeal)
                        $newLineItem['customData']['donationAppealID'] = $cartItem -> appeal;
                    if($cartItem->type == DONATION_TYPE_PLEDGE){
                        $newLineItem['customData']['donationType'] = 'fund';
                        // Shopping cart widget expects one time and monthly amounts to be numeric so convert them
                        $newLineItem['customData']['amountOneTime'] = floatval($cartItem->oneTime);
                        $newLineItem['customData']['amountMonthly'] = floatval($cartItem->recurring);
                        // Price should be sum of one time and monthly amounts for donations
                        $newLineItem['merch']['price'] = floatval($cartItem->oneTime) + floatval($cartItem->recurring);
                    }
                    
                    
                     $lineItemObjs[] = $newLineItem;   
                }
                       
                
                
            }
        }catch(Exception $e){
            echo "error - $e </br>";
        }
        
        ////logMesage("get items from cart");
        ////logMesage($lineItemObjs);
        
        return $lineItemObjs;

    }

    public function getTotalDueNow($sessionId){
        try{
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId'";
            //logMesage($roql);
            $res = RNCP\ROQL::queryObject( $roql)->next();
            
            $dueNow = 0;
            while($cartItem = $res->next()){
                if(empty($cartItem->qty)) $cartItem->qty = 1;
                if(empty($cartItem->oneTime)) $cartItem->oneTime = '0.00';
                if(empty($cartItem->recurring)) $cartItem->recurring = '0.00';
                $dueNow += $cartItem->qty * $cartItem->oneTime + $cartItem->qty * $cartItem->recurring;
            }
        }catch(Exception $e){
           echo "error - $e </br>";
        }
        
        return $dueNow;   
    }

    public function updateTransOnItems($sessionId, $transId){
        
        // $this->_logToFile(__LINE__, "Session:".$sessionId." Transaction:".$transId);
        helplog(__FILE__, __FUNCTION__.__LINE__,$sessionId." Transaction:".$transId, "");
        if(empty($transId) || empty($sessionId)){
            // $this->_logToFile(__LINE__, "empty");
            return;
        }

        //if there are cart items with the transaction id and an old session, delete them.  
        //this can happen if someone's session refreshed but they already created a cart item with the existing transaction id
        $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.transId = ".intval($transId)." AND Shopping.Cart.SessionID != '$sessionId'";
        $res = RNCP\ROQL::queryObject( $roql)->next();

        while($cartItem = $res->next()) {
            logMessage("Destroying cart item".$cartItem->ID);
            $cartItem->destroy();
        }

        try{
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId'";
            helplog(__FILE__, __FUNCTION__.__LINE__, $roql, "");
            $res = RNCP\ROQL::queryObject( $roql)->next();

            while($cartItem = $res->next()){
                $cartItem->transId = intval($transId);
                helplog(__FILE__, __FUNCTION__.__LINE__,"Cart item ID:".$cartItem->ID." Added Trans:".$transId,"");
                $cartItem->save();
            }
        }catch(Exception $e){
            helplog(__FILE__, __FUNCTION__.__LINE__,"", "Error:".$e->getMessage());
        }catch(RNCP\ConnectAPIError $err) {
            helplog(__FILE__, __FUNCTION__.__LINE__,"", "Error:".$err->getMessage());
        }
        
        return true;   
    }

    public function getTotalReoccurring($sessionId){
        try{
            $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId'";
            //logMesage($roql);
            $res = RNCP\ROQL::queryObject( $roql)->next();
            
            $reoccurring = 0;
            while($cartItem = $res->next()) {
                if(empty($cartItem->qty)) $cartItem->qty = 1;
                if(empty($cartItem->recurring)) $cartItem->recurring = '0.00';
                $reoccurring += $cartItem->qty * $cartItem->recurring;
            }
        }catch(Exception $e){
           echo "error - $e </br>";
        }
        
        return $reoccurring;   
    }

    public function getGiftItems($itemId = null) {
        $items = array();
        $sql = "Select ONLINE.Items from ONLINE.Items Where ONLINE.Items.Gift = 1 ";
        if($itemId){
            $sql .= " AND Online.Items.ID = ".intval($itemId);
        }
        $sql .= " ORDER BY ONLINE.Items.WebDisplayOrder ";


        $resultSet = RNCP\ROQL::queryObject($sql) -> next();

        while ($item = $resultSet -> next()) {

            //$child = $item->ID;
            if ($item -> ID != null) {
                $thischild = new \stdClass();
                $thischild -> ID = $item -> ID;
                $thischild -> Title = $item -> Title;
                $thischild -> Description = $item -> Description;
                $thischild -> Amount = $item -> Amount;
                $thischild -> PhotoURL = $item -> PhotoURL;
                $items[] = $thischild;
            }
        }

        return $items;
    }

    public function getDonationItems() {
        $items = array();
        $sql = "Select ONLINE.Items from ONLINE.Items Where ONLINE.Items.Donation = 1 ORDER BY  ONLINE.Items.WebDisplayOrder";


        $resultSet = RNCP\ROQL::queryObject($sql) -> next();

        while ($item = $resultSet -> next()) {

            //$child = $item->ID;
            if ($item -> ID != null) {
                $thischild = new \stdClass();
                $thischild -> ID = $item -> ID;
                $thischild -> Title = $item -> Title;
                $thischild -> Description = $item -> Description;
                $thischild -> Amount = $item -> Amount;
                $thischild -> PhotoURL = $item -> PhotoURL;
                //Donation Fund
                $thischild -> DonationFund = $item -> DonationFund -> ID;
                //Donation Appeal
                $thischild -> DonationAppeal = $item -> DonationAppeal -> ID;
                $items[] = $thischild;
            }
        }
        return $items;
    }
    
    public function getSingleDonationItem($f_id) {
        $items = array();
        $thisfund = new \stdClass();
        $sql = "Select ONLINE.Items from ONLINE.Items where ID = " .$f_id." LIMIT 25";
        //echo $sql;
        //"Select ONLINE.Items from ONLINE.Items Where ONLINE.Items.Donation = 1 ORDER BY  ONLINE.Items.WebDisplayOrder";
        //TODO: just return a single object as there should only be one fund record per fund per ID

      if($f_id > 0)
      {
        
      
        try{ 
        $resultSet = RNCP\ROQL::queryObject($sql) -> next();

            while ($item = $resultSet -> next()) {
    
                //$child = $item->ID;
                if ($item -> ID != null) {
                    $thisfund = new \stdClass();
                    $thisfund -> ID = $item -> ID;
                    $thisfund -> Title = $item -> Title;
                    $thisfund -> Description = $item -> Description;
                    $thisfund -> Amount = $item -> Amount;
                    $thisfund -> PhotoURL = $item -> PhotoURL;
                    //Donation Fund
                    $thisfund -> DonationFund = $item -> DonationFund -> ID;
                    //Donation Appeal //previous var $thischild
                    $thisfund -> DonationAppeal = $item -> DonationAppeal -> ID;
                    $thisfund -> DefaultMonthlyAmount = $item -> defaultMonthlyAmount;
                    $thisfund -> DefaultOneTimeAmount = $item -> defaultOneTimeAmount;
                    $thisfund -> CampaignFrequency = $item -> campaign_frequency;
                                        
                    $items[] = $thisfund;
                }
            }
        }catch(Exception $e){
                echo "Error ".$e->getMessage();
                ////logMesage(getConfig(CUSTOM_CFG_DONATE));  
        //print_f(getConfig(CUSTOM_CFG_DONATE)) ;   
        //print_r(getConfig(CUSTOM_CFG_DONATE)) ;                  
       //header("Location:" . getConfig(CUSTOM_CFG_DONATE));    
      // header("https://africanewlife--tst.custhelp.com/app/donate");   
           }

           
      }
      else{
         header("Location:" . getConfig(CUSTOM_CFG_DONATE));   
      }
      
        
        //echo " param from items model $f_id";
       // echo "<br/>";
        //echo "items from model";
       // print_r($items);
        return $items;
    }

    public function getitemdetails($iid) {
        $items = array();
        $sql = "Select ONLINE.Items from ONLINE.Items Where ONLINE.Items.ID=$iid;";


        $resultSet = RNCP\ROQL::queryObject($sql) -> next();
        $thischild = new \stdClass();
        while ($item = $resultSet -> next()) {

            //$child = $item->ID;
            if ($item -> ID != null) {
                $thischild -> ID = $item -> ID;
                $thischild -> Title = $item -> Title;
                $thischild -> Description = $item -> Description;
                $thischild -> Amount = $item -> Amount;
                $thischild -> PhotoURL = $item -> PhotoURL;
                //Donation Fund
                $thischild -> DonationFund = $item -> DonationFund -> ID;
                //Donation Appeal
                $thischild -> DonationAppeal = $item -> DonationAppeal -> ID;
                //Donation Appeal
                $thischild -> RedText = $item -> CampaignRedText;
            }
        }
        return $thischild;
    }

    /**
     * Req:5 Gift limit per child during holiday season eventusg.teamwork.com/#tasks/10206460
     * Dev: Z Cordell
     * Date: Sept 2017
     * Take line items for each child and add them to the existing gifts.  If that total is more than cfg CUSTOM_CFG_HOLIDAY_GIFT_LIMIT, return failure notice and notify customer
     */
    public function okToAddItems($sessionId, $lineItems){
        
         if( date("U",strtotime(getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_BEGIN))) > time() || date("U",strtotime(getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_END))) < time() ){
            
            return;
        }

        $anyChildInNeedID = '8793';
        $holidayItemsPerChild = new \stdClass;
        $holidayItemsPerChild->okToAdd = 'true';
        $holidayItemsPerChild->Child = array();
        
        $holidayStartDate = getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_BEGIN) ;
        $holidayEndDate = getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_END) ;
        $holidayMaxGifts = getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT) ;
        
        $lineItemsObj = json_decode($lineItems);
        logMessage($lineItemsObj);
        
        if($this -> CI -> session -> getProfileData('contactID') < 1) return;
        
        //get the number of gifts for each child from previous orders
        //CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_BEGIN  CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_END
        $childIds = array();
        
        foreach($lineItemsObj as $Item){
            
            if($Item->customData->childID == $anyChildInNeedID){
                continue;
            }
            $numberOfItems = 0;
            $existingCartItems = 0;
            $cartItemsAttemptingToBeAdded = 0;
            $completedDonationItems = 0;
            $newChild = null;
            
            $existingCartItems = $this->_getExistingCartItems($sessionId, $Item->customData->childID);
            
            $completedDonationItems = $this->_getItemsFromCompletedDonations($Item->customData->childID, $this -> CI -> session -> getProfileData('contactID'), $holidayStartDate, $holidayEndDate);
            $total = $existingCartItems + $completedDonationItems;
            //if over CUSTOM_CFG_HOLIDAY_GIFT_LIMIT return false
            //logMessage("child ".$Item->customData->childID." has ".$total." gifts ____________________");
            
            $newChild = new \stdClass;
            $newChild->ID = $Item->customData->childID;
            $newChild->Name = $Item->customData->childName;
            $newChild->HolidayGiftsTotal = $total;

            if($total > $holidayMaxGifts){
                $holidayItemsPerChild->okToAdd = 'false';
                $newChild->Over = 'true';
            }

            if(!in_array($newChild->ID , $childIds, true)){
                $holidayItemsPerChild->Child[] = $newChild;
            }

            $childIds[] = $newChild->ID;
           
        }
        logMessage($holidayItemsPerChild);
        
        return json_encode($holidayItemsPerChild);
     }

    public function _getExistingCartItems($sessionId, $childId){
        $total = 0;
        $roql = "Select Shopping.Cart from Shopping.Cart where Shopping.Cart.SessionID = '$sessionId' and Shopping.Cart.childId = $childId";
        
        ////logMesage($roql);
        $res = RNCP\ROQL::queryObject( $roql)->next();
        while($cartItem = $res->next()) {
            $total += $cartItem->qty;
        }
        
        logMessage("Existing Cart Items = ".$total);
        return $total;
    }
    
    
    
    public function _getItemsFromCompletedDonations($childId, $contact, $holidayStartDate, $holidayEndDate){
        $total = 0;
                  
        $child_filter= new RNCP\AnalyticsReportSearchFilter;
        $child_filter->Name = 'Child';
        $child_filter->Values = array( $childId );
            
        $contact_filter= new RNCP\AnalyticsReportSearchFilter;
        $contact_filter->Name = 'Contact';
        $contact_filter->Values = array( $contact );
        
        $greaterThan_filter=new RNCP\AnalyticsReportSearchFilter;
        $greaterThan_filter->Name = 'HolidaySeasonBegin';
        $greaterThan_filter->Values = array($holidayStartDate);
        //$greaterThan_filter->Operator = 5; //greater than
        
        $lessThan_filter=new RNCP\AnalyticsReportSearchFilter;
        $lessThan_filter->Name = 'HolidaySeasonEnd';
        $lessThan_filter->Values = array($holidayEndDate);
        //$lessThan_filter->Operator = 3; // less than
             
        $filters = new RNCP\AnalyticsReportSearchFilterArray;
        $filters[] = $child_filter;
        $filters[] = $contact_filter;
        $filters[] = $greaterThan_filter;
        $filters[] = $lessThan_filter;
        
        logMessage($filters);
        
        $ar= RNCP\AnalyticsReport::fetch( 101115 );
        $arr= $ar->run( 0, $filters );
        $nrows= $arr->count();
        if ( $nrows) {
             $row = $arr->next();
             for ( $ii = 0; $ii++ < $nrows; $row = $arr->next() ) {
                   //logMessage($row);
                   $total += $row['Quantity'];
             }
        }   
        logMessage("items from previous orders = ".$total);
        return $total;
    }

    // public function helplog($lineNum, $message){
    //    helplog(__FILE__, __FUNCTION__.__LINE__,$lineNum, $message, "");
        // $hundredths = ltrim(microtime(), "0");
        
        // $fp = fopen('/tmp/esgLogPayCron/checkoutLogs_'.date("Ymd").'.log', 'a');
        // fwrite($fp,  date('H:i:s.').": Items Model @ $lineNum : ".$message."\n");
        // fclose($fp);
        
    
    
}