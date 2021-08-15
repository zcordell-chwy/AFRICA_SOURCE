<?php
namespace Custom\Widgets\eventus;

use \RightNow\Connect\v1_2 as RNCPHP;

require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();


class managepaymethods extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        
        $this -> CI -> load -> helper('constants');
    }

    function getData() {

        parent::getData();
    }

    function _disableDeletePayMethods($payMethods){
        $disabledArray = array();
        
        foreach ($payMethods as $pm) {
            //don't disable 1 time pledges.
            $roql = "Select donation.pledge from donation.pledge where donation.pledge.Frequency != 9 AND donation.pledge.paymentMethod2 = ".$pm->ID;
            $results = RNCPHP\ROQL::queryObject($roql) -> next();
            $pledge = $results -> next();
            if($pledge)
                $disabledArray[] = $pm->ID;
           
        }
        return $disabledArray;
    }
}