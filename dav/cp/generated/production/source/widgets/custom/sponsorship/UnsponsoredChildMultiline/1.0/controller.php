<?php

namespace Custom\Widgets\sponsorship;

class UnsponsoredChildMultiline extends \RightNow\Widgets\Multiline
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->CI->load->helper('constants');
        $this->setAjaxHandlers(array(
            'fix_data' => array(
                'method'      => 'fix_data_endpoint',
                'clickstream' => 'custom_action',
            ),
        ));
    }
    function getData()
    {
        parent::getData();
        $event_id = getUrlParm('event');
        // print('<pre>');
        // $data = $this->CI->session->getSessionData('sessionString');
        // $str = substr($data, strpos($data, '/session/'));
        // $prefix = "/session/";
        // $index = strpos($data, $prefix) + strlen($prefix);
        // $result = substr($data, $index);
        // echo $result;
        // die;
        // print_r($this->CI->session->getSessionData('sessionID'));die;
        if($event_id){
            $event = $this->CI->model('custom/event_model')->getEvent($event_id);
            // print_r($event->showDisclaimer);die;
            if($event->showDisclaimer == 1)
                $this->data['showDisclaimer'] = $event->showDisclaimer;
                        
            $this->data['EventName'] = $event->DisplayName;
            $this->data['EventDescription'] = $event->Description;

            $sessionEventData = array('EventDescription' => $event->DisplayName . '<br>' . $event->Description);
            $this -> CI -> session -> setSessionData($sessionEventData);
        }
        $this->data['js']['reportData'] = $this->formatData($this->data['reportData']);
        $this->data['reportData'] = $this->formatData($this->data['reportData']);
        // print_r($this->data['reportData']['formatted']);
        // print('</pre>');
    }

    function formatData($reportData)
    {
        $headers = array_column($reportData['headers'], 'heading');
        $reportData['data'] = array_map(function ($data) use ($headers) {
            $new = array_combine($headers, $data);
            $new['image'] = $this->getImage($new['Child Ref']);
            return (array) $new;
        }, $reportData['data']);
        return $reportData;
    }

    /* Handles the default_ajax_endpoint AJAX request
     * @param array $params Get / Post parameters
     */
    function fix_data_endpoint($params) {
        $this->echoJSON(json_encode($this->formatData(json_decode($params['data'], true))));
    }

    function getImage($child_ref)
    {
        $image_path = $this->CI->model('custom/sponsorship_model')->getChildImg($child_ref) ?: CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
        return $image_path;
    }

    /**
     * Overridable methods from Multiline:
     */
    // function showColumn($value, array $header)    
    // function getHeader(array $header)
}
