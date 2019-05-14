<?php
namespace Custom\Widgets\eventus;

class IndividualFund extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        parent::getData();// Does the controller capture the parameter?, or where to capture URL parameter
        $f_id = getUrlParm('f_id');
        //echo "fund Id from Controller $f_id";
        logMessage($f_id);
        $items = $this -> CI -> model('custom/items') -> getSingleDonationItem($f_id);        
        $this -> data['Items'] = $items;
        //print_r($items);
        //logMessage($items);
        //$this -> data['Items'] = $items;
        //$mems = $this -> CI -> model('custom/sponsor_model') -> searchwidget("");
        //$this -> data['members'] = $mems;
    }

}
