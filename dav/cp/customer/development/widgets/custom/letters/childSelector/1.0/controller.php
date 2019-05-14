<?php
namespace Custom\Widgets\letters;

class childSelector extends \RightNow\Libraries\Widget\Base {
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
        
        
        $this->data['children'] = $this->CI->model('custom/sponsor_model')->getSponsoredChildren(getUrlParm('c_id'));
        //remove needy child //it'll always be last
        array_pop($this->data['children']);
        logMessage($this->data['children']);
        //child to show
        $pledgeToShowKey = $this->searchForId(getUrlParm('pledge'), $this->data['children']);
        logMessage($pledgeToShowKey);
        $this->data['pledgeToShow'] = (getUrlParm('pledge') > 0) ? $this->data['children'][$pledgeToShowKey] : $this->data['children'][0];
        logMessage($this->data['pledgeToShow']);
        //back arrow value    
        if($pledgeToShowKey > 0){
            $this->data['previousPledge'] = $this->data['children'][$pledgeToShowKey - 1]->PledgeId;
        }
        
        //forward arrow value
        if($pledgeToShowKey < count($this->data['children'])){
            $this->data['nextPledge'] = $this->data['children'][$pledgeToShowKey + 1]->PledgeId;
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
    
    function searchForId($id, $array) {
       $arrCount = 0;
       foreach ($array as $item) {
           if ($item->PledgeId == $id) {
               return $arrCount;
           }
           $arrCount++;
       }
       return null;
    }
}