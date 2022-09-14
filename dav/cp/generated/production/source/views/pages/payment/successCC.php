<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="standard.php" clickstream="payment"/>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <h2>
        Thank you for your donation!
    </h2>
    <p> 
     
    <span>
    <? 
    logMessage('Began logging of payment/successCC');
    logMessage('Transaction ID  = ' . var_export(\RightNow\Utils\Url::getParameter('t_id'), true));
    if(\RightNow\Utils\Url::getParameter('t_id') == 0){
        //display queueing message
        ?>#rn:msg:CUSTOM_MSG_QUEUED_TRANSACTION_CONFIRMATION#<?
    }else{
        logMessage('Payment/successCC ELSE Condition...');
        $CI    		= & get_instance();
        // //Loading helper class
        // $CI->load-> helper('constants'); 
        // logMessage('Payment/successCC Loaded Helper Class...');
        
        // $items = $CI -> session -> getSessionData('items');
        $item_type = $CI->session->getSessionData("item_type");
        logMessage('item_type = ' . var_export($item_type, true));
        $transactionId = \RightNow\Utils\Url::getParameter('t_id');

        $itemsFromCart = $CI->model('custom/items')->getItemsFromCart($CI->session->getSessionData('sessionID'), 'checkout', $transactionId);
        logMessage('SuccessCC Page itemsFromCart = ' . var_export($itemsFromCart, true));
        // //echo "item type = ".$item_type." contstant = ".DONATION_TYPE_PLEDGE. " = ".DONATION_TYPE_GIFT." - ".DONATION_TYPE_SPONSOR;

        switch ($item_type) {
        case DONATION_TYPE_PLEDGE :
            logMessage("Pledge Type Found");
            ?>#rn:msg:CUSTOM_MSG_DONATE_PAGE_CONFIRMATION#<?
            break;
        case DONATION_TYPE_GIFT :
            logMessage("Donation Type Found");
            ?>#rn:msg:CUSTOM_MSG_GIFT_PAGE_CONFIRMATION#<?
            break;
        case DONATION_TYPE_SPONSOR :
            logMessage("Sponsor Type Found");
            logMessage($itemsFromCart);
            if(is_array($itemsFromCart) && $itemsFromCart[0]['isWomensScholarship']):
                ?>#rn:msg:CUSTOM_MSG_WOMAN_PAGE_CONFIRMATION#<?
            else:
                ?>#rn:msg:CUSTOM_MSG_SPONSOR_PAGE_CONFIRMATION#<?
            endif;

            break;      
        default :
            logMessage("No Type Found");
            ?>Thank you for your payment. <?
            break; 
        }
    }
    
          
    $sessionData = array(
        'total' => null,
        'totalRecurring' => null,
        'items' => null,
        'donateValCookieContent' => null,
        'payMethod' => null
    );

    $sessionData = array('transId' => null);
    $CI->session->setSessionData($sessionData);
    $CI->model('custom/items') -> clearItemsFromCart($CI->session->getSessionData('sessionID'), null, $transactionId);
       
    ?>
        
        
          
    </span>
    </p>
    <br/>
    <p>
        #rn:msg:CUSTOM_MSG_SPONSOR_PAGE_EVENT_MSG#
    </p>
</div>