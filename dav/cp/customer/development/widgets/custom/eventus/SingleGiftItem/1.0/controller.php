<?php
namespace Custom\Widgets\eventus;
class SingleGiftItem extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);        
        
        $this->setAjaxHandlers(array(            
                'default_ajax_endpoint' => array(
                'method'      => 'handle_default_ajax_endpoint',
                'clickstream' => 'custom_action',
            ),
        ));
    }
    function getData() {

        $profile = $this->CI->session->getProfile();
        $this->data['attrs']['isLoggedIn'] =  ($profile->c_id->value > 0) ? true : false;

        $item_id = getUrlParm('Item');

        if(!empty($item_id)){
            $this->CI->load->model('custom/items');
            $this->data['gifts'] = $this->data['js']['gifts'] = $this->CI->items->getGiftItems($item_id, true);
    
            $this->CI->load->model('custom/sponsor_model');
            //$children = $this->CI->sponsor_model->getSponsoredChildren($profile->c_id->value);
            $children = $this->CI->sponsor_model->getSponsoredChildren(27951);
            //27951
            $this->data['eligibleChildren'] =  $this->data['js']['eligibleChildren']= $this->getChildrenEligibleForGift($children, $this->data['gifts'][0]);

            $this->data['redirectOnLogin'] = "SingleGift/Item/".$item_id;
            $this->data['redirectOnLogin'] .= (getUrlParm('cospon')) ? "/cospon/".getUrlParm('cospon') : '';

            logMessage($this->data['eligibleChildren']);

            $this->data['cospon'] = $this->data['js']['cospon'] = getUrlParm('cospon');
        }
        
        
        return parent::getData();
    }    
    
    /**
     * Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_default_ajax_endpoint($params) {
        // Perform AJAX-handling here...
        // echo response
    }

    /**
    * Searches an array of children and returns the children eligible to receive a particular gift.
    * 
    * @param array $children The array of children to search
    * @param object $gift The gift in question
    */
    private function getChildrenEligibleForGift($children, $gift){
        $eligibleChildren = array();

        foreach($children as $child){
            if(!empty($child->ExcludedItems) && in_array($gift->ID, $child->ExcludedItems)) continue;
            $eligibleChildren[] = array(
                'id' => $child->ID,
                'name' => $child->ID == 8793 ? $child->GivenName : $child->FullName,
                'imgURL' => $child->imageLocation
            );
        }

        return $eligibleChildren;
    }

   
}