<?php
namespace Custom\Widgets\sponsorship;

class UnsponsoredChildGallery extends \RightNow\Libraries\Widget\Base {
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
        parent::getData();

        switch($this->data['attrs']['data_source']){
            case 'woman':
                $this->loadWomanData();
                break;
            case 'child':
            default:
                $this->loadChildData();
                break;

        }
        

    }

    function loadWomanData(){
        // Gather URL params
        $program = $this->getURLParam('program', true);
        $selectedWomanID = $this->getURLParam('woman', true);
        $oneTimeVal = $this->getURLParam('onetime', true);
        $recurringVal = $this->getURLParam('recurring', true);
        $page = $this->getURLParam('page', true, 1);
        $event = $this->data['js']['eventId'] = getUrlParm('event');
        logMessage('$event @ line 26 in UnsponsoredChildGallery::getData = ' . var_export($event,true));



        // Get unsponsored children data
        $recordsPerPage = $this->data['attrs']['rows'] * $this->data['attrs']['columns'];
        
        //kept this named unsponsoredChildren in order to not drastically change the view
        $this->data['unsponsoredChildren'] = $this->CI->model('custom/woman_model')->getUnsponsoredWomen( $page, $recordsPerPage, $event, $program);
        
        // Set javascript data
        $this->data['js']['currentPage'] = $this->data['unsponsoredChildren']['metadata']['page'];
        if(!is_null($selectedWomanID)) $this->data['js']['selectedWomanID'] = $selectedWomanID;
        if(!is_null($oneTimeVal)) $this->data['js']['oneTimeVal'] = $oneTimeVal;
        if(!is_null($recurringVal)) $this->data['js']['recurringVal'] = $recurringVal;
    }

    function loadChildData(){
        // Gather URL params
        $gender = $this->getURLParam('gender', true);
        $age = $this->getURLParam('age', true);
        $community = $this->getURLParam('community', true);
        $priority = $this->getURLParam('priority', true);
        $monthofbirth = $this->getURLParam('monthofbirth', true);
        $yearofbirth = $this->getURLParam('yearofbirth', true);
        $selectedChildID = $this->getURLParam('child', true);
        $page = $this->getURLParam('page', true, 1);
        $event = $this->data['js']['eventId'] = getUrlParm('event');
        logMessage('$event @ line 26 in UnsponsoredChildGallery::getData = ' . var_export($event,true));



        // Get unsponsored children data
        $childrenPerPage = $this->data['attrs']['rows'] * $this->data['attrs']['columns'];
        
        $this->data['unsponsoredChildren'] = $this->CI->model('custom/sponsorship_model')->getUnsponsoredChildren( $gender, $age, $community, $page, $childrenPerPage, $event, $priority, $monthofbirth, $yearofbirth);
        if($this -> data['attrs']['advocacy_page'] && $event > 0){
            logMessage('$this->data[\'unsponsoredChildren\'][\'metadata\'] = ' . var_export($this->data['unsponsoredChildren']['metadata'],true));
            $eventObj = $this->CI->model('custom/event_model')->getEvent($event);
            if(!is_null($eventObj) && $eventObj->AllowAnonymousAdvocacy){
                // Don't keep track of advocacies if 'AllowAnonymousAdvocacy' is flagged on the event. We're simply going to 
                // show the childSponsor page link when viewing each child, and allow anonymous advocacy of that link until that 
                // child is sponsored on a first-come, first-serve basis.
                $this->data['js']['childSponsorURL'] = \RightNow\Utils\Url::getShortEufBaseUrl() . '/app/childSponsor/ChildID/';
                $this->data['js']['allowAnonymousAdvocacy'] = true;
                $this->data['js']['advocacies'] = array();
            }else{
                logMessage("Getting advocates");
                $this->data['js']['advocacies'] = $this->CI->model('custom/sponsorship_model')->getAdvocacies($event);
            }
        }
        
        if($this -> data['attrs']['advocacy_page'] && $this->getURLParam('success', true) > 0){
            $this->data['js']['confirmationMessage'] = $this->CI->model('custom/sponsorship_model')->getAdvocateConfirmation($this->getURLParam('success', true)); 
        }

        //to show/hide disclaimer for events.
        if($event > 0){
            $disclaimerEventObj = ($eventObj instanceof RNCPHP\sponsorship\Event) ?  $eventObj : $this->CI->model('custom/event_model')->getEvent($event);
            $this->data['js']['showDisclaimer'] = $disclaimerEventObj->showDisclaimer;
        }

        // Get community data
        $this->data['communities'] = $this->CI->model('custom/sponsorship_model')->getCommunities();

        // Set javascript data
        $this->data['js']['currentPage'] = $this->data['unsponsoredChildren']['metadata']['page'];
        if(!is_null($selectedChildID)) $this->data['js']['selectedChildID'] = $selectedChildID;
    }

    function getURLParam($name, $isInt = false, $defaultValue = null){
        $value = \RightNow\Utils\Url::getParameter($name);
        if($isInt && !is_null($value)) $value = intval($value);
        if(is_null($value) && !is_null($defaultValue)) $value = $defaultValue;
        return $value;
    }

    /**
     * Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_default_ajax_endpoint($params) {
        // Perform AJAX-handling here...
        // echo response
    }
}