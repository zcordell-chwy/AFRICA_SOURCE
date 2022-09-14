<?php

ini_set('display_errors', 'Off');
error_reporting(0);

$ip_dbreq = true;
require_once('include/init.phph');

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');

load_curl();
  
$colors = array("118575","115832","122765","103626","114437","95632","111759","101264");

foreach ($colors as $value) {
	  echo "$value <br>";
	$pledge = RNCPHP\donation\pledge::fetch(intval($value));
	$pledge->NextTransaction = date( "Y-m-d", strtotime( "2021-11-24") );
	$pledge->save(RNCPHP\RNObject::SuppressAll);
}
?>