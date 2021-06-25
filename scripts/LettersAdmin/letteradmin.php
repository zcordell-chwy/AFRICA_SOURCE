<?

ini_set('display_errors', 'On');
error_reporting(E_ALL);

if (!defined('DOCROOT')) define('DOCROOT', get_cfg_var('doc_root'));
require_once(DOCROOT . "/include/ConnectPHP/Connect_init.phph");

require_once('/cgi-bin/africanewlife.cfg/scripts/cp/customer/development/libraries/fpdf181/fpdf.php');

use RightNow\Connect\v1_2 as RNCPHP;
use RightNow\Utils\Framework;
//use RightNow\API as cApi;
//initConnectAPI("api_admin","Api_admin1");

// Authenticate with site
require_once (DOCROOT . '/include/services/AgentAuthenticator.phph');
$username = getDataFromPOST('username');
$password = getDataFromPOST('password');
$account = AgentAuthenticator::authenticateCookieOrCredentials($username,$password);

/** 
 * Santizes input from $_POST or $_GET
 * @param string $key the key of the data in the $_POST or $_GET array
 */
function getDataFromPOST($key){
	if(!isset($_POST[$key])){
		if(!isset($_GET[$key])){
			$value = null;
		}else{
			$value = htmlspecialchars(trim($_GET[$key]));
		}
	}else{
		$value = htmlspecialchars(trim($_POST[$key]));
	}
	return $value; 
}

?>
<html>
	<head>
		
	</head>
	<body>
		<button id='createletters' onclick='window.open("/cgi-bin/africanewlife.cfg/php/custom/LettersAdmin/letterscreate.php")'>Generate Letters</button>
		<div>
			
		</div>
	</body>
</html>
