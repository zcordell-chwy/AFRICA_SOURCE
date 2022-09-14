<?php

//Author: Zach Cordell
//Date: 5/1/15
//Purpose: cron utility will be run every 1 time per day.  Will process pledges that are due to be run today

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

define('ALLOW_POST', false);
define('ALLOW_GET', true);
define('ALLOW_PUT', false);
define('ALLOW_PATCH', false);
require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';
$returnArray = array();

$pledgeID = $_GET['pledgeid']; //for testing


$roql = "SELECT donation.pledge FROM donation.pledge WHERE (donation.pledge.PledgeStatus = 1 or donation.pledge.PledgeStatus = 43 or donation.pledge.PledgeStatus = 2) and donation.pledge.Type1 = 2";
$roql .= ($pledgeID > 0) ? " and donation.pledge.id = $pledgeID" : "";

$pledgeObj = RNCPHP\ROQL::queryObject($roql)->next();
while($pledge = $pledgeObj->next()) {
    $old = $pledge->AheadBehind ;
    $pledge->AheadBehind = GetAheadBehind($pledge);
    $new = $pledge->AheadBehind;
    //echo "<br/>Pledge ID: ".$pledge->ID." Pledge New Balance = ".$pledge->AheadBehind." \n ";
    if($old != $new){
        $pledge->save(RNCPHP\RNObject::SuppressAll);
        RNCPHP\ConnectAPI::commit();
    }
}

return outputResponse($returnArray, null);

function GetAheadBehind(RNCPHP\donation\pledge $pledge)
{
    $nextTrans = $pledge->NextTransaction;
    $pledgeAmount = $pledge->PledgeAmount;
    $frequency = $pledge->Frequency->LookupName;
    
    $incrementsAheadBehind = ($nextTrans < time()) ? _getIncrementsBehind($nextTrans, time(), $frequency) : _getIncrementsAhead($nextTrans, time(), $frequency);
    
    //echo "<br/> Number of Increments = $incrementsAheadBehind";
    //echo "<br/> Pledge amount = $pledgeAmount";
    $newBalance = ($incrementsAheadBehind * $pledgeAmount);
    $aheadBehind = strval(number_format($newBalance, 2, '.', '')) ;
    //echo "<br/> Ahead Behind = $aheadBehind";
    return $aheadBehind;
}                                                                   
    
function _getIncrementsBehind($date1, $date2, $frequency)
{
    $months = 1;
    
    while (strtotime('+1 MONTH', $date1) < $date2) {
        $months++;
        $date1 = strtotime('+1 MONTH', $date1);
    }
    //echo "<br/>Months Behind = $months";
    if($frequency == 'Monthly'){
            $increments = $months;
    }else if($frequency == 'Annually'){
            $increments = ceil($months / 12);
    }else if($frequency == "Quarterly"){
            $increments = ceil($months / 3);
    }

    return $increments * -1;
    
}

function _getIncrementsAhead($date1, $now, $frequency)
{
    $months = 0;

    while (strtotime('-1 MONTH', $date1) > $now) {
        $months++;
        $date1 = strtotime('-1 MONTH', $date1);
    }
    //echo "<br/>Months Ahead = $months";
    if($frequency == 'Monthly'){
            $increments = $months;
    }else if($frequency == 'Annually'){
            $increments = floor($months / 12);
    }else if($frequency == "Quarterly"){
            $increments = floor($months / 3);
    }

   
    return $increments;
    
}

?>	