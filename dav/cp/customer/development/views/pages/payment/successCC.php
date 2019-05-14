<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="responsive.php" clickstream="payment"/>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <h2>
        Thank you for your donation!
    </h2>
    <p> 
     
    <span>
    <? 
    logMessage('Began logging of payment/successCC');
    $this -> CI -> load -> helper('constants'); 

    //$items = $this -> CI -> session -> getSessionData('items');
    $item_type = $this -> CI -> session -> getSessionData('item_type');
    logMessage('item_type = ' . var_export($item_type, true));

    //echo "item type = ".$item_type." contstant = ".DONATION_TYPE_PLEDGE. " = ".DONATION_TYPE_GIFT." - ".DONATION_TYPE_SPONSOR;

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
                ?>#rn:msg:CUSTOM_MSG_SPONSOR_PAGE_CONFIRMATION#<?
                break;      
    		default :
                logMessage("No Type Found");
                ?>Thank you for your payment. <?
                break; 
           }
          
    $sessionData = array(
        'total' => null,
        'totalRecurring' => null,
        'items' => null,
        'donateValCookieContent' => null,
        'payMethod' => null
    );

    $sessionData = array('transId' => null);
    $this -> CI -> session -> setSessionData($sessionData);
    $this -> CI -> model('custom/items') -> clearItemsFromCart($this -> CI->session->getSessionData('sessionID'));
       
    ?>
        
        
          
    </span>
    </p>
    <br/>
    <p>
        #rn:msg:CUSTOM_MSG_SPONSOR_PAGE_EVENT_MSG#
    </p>
</div>