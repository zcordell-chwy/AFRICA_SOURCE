<?php

//if (!defined('DOCROOT')) define('DOCROOT', get_cfg_var('doc_root'));
//require_once(DOCROOT . "/include/ConnectPHP/Connect_init.phph");
//
//use \RightNow\Connect\v1_2 as RNCPHP;

try{

}catch(Exception $e){
    //just let it continue;
}


//if (!empty($_POST["username"])) $username = htmlspecialchars(trim($_POST["username"])); 
//if (!empty($_GET["username"])) $username = htmlspecialchars(trim($_GET["username"])); 
//if (!isset($userName)) $userName = "";
//if (!empty($_GET["password"])) $password = htmlspecialchars(trim($_GET["password"])); 
//if (!empty($_POST["password"])) $password = htmlspecialchars(trim($_POST["password"]));
//if (!isset($password)) $password = "";
//require_once(DOCROOT . '/include/services/AgentAuthenticator.phph');
//AgentAuthenticator::authenticateCookieOrCredentials($username, $password);
//$_GET["mode"] = null;

//print_r($_POST);
date_default_timezone_set('America/Los_Angeles');
$hour = intval(date("H"));

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}



switch ($hour) {

	case 2:
	   require_once("womanupdate.php");
	   break;
	case 3://3am PST
            require_once("pledgepayprocess_cron.php");
            break;
        case 5: //5am PST
            require_once("getbadchecks.php");
            break;
        case 6: //6am PST
            require_once("reconcileOSC_FrontStream.php");
            break;
        case 14:
            require_once("womanupdate.php");
	   break;
        case 22:
            if( date('t',strtotime('today')) == date('d') ){//if last day of the month
               require_once("pledgeupdate.php");
            }
  	    break;
        case 8:
            require_once("updateactivepledgecount.php");
            break;
        case 9:
            require_once("move_child_photos.php");
            break;
        default:
	    require_once("pledgebalanceupdate.php");
             require_once("childupdate.php");
	    //require_once("womanupdate.php");
	    break;
          

}



?>								