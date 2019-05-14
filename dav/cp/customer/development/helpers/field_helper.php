<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use RightNow\Connect\v1_2 as RNCPHP;
require_once (get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
       
function getPledgeDescr ($pledgeId) {
    if($pledgeId)    
        $pledge = RNCPHP\donation\pledge::fetch(intval($pledgeId));      
    
    return $pledge->Descr;
}
