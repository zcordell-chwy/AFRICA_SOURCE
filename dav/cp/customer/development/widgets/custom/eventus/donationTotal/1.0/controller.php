<?php
namespace Custom\Widgets\eventus;

class donationTotal extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        //return parent::getData();
        //$f_id = getUrlParm('f_id'); //...add the fund id as a widget attribute, so that the controller dosent need to know
        //what page it is actually on. 
        //echo "fund Id from Controller $f_id";
        //logMessage($f_id);
        //$items = $this -> CI -> model('custom/items') -> getSingleDonationItem($f_id);        
        //$this -> data['Items'] = $items;

    }
}