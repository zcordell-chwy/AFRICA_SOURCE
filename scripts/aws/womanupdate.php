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

/**************CONSTANTS*******************/
CONST FULL_SCHOLARSHIP = 1;
CONST PART_SCHOLARSHIP = 2;

$active_pledge_status_list = array(1,2,43);//active, manual, on hold pay

//Begin
$roql = "Select sponsorship.Woman from sponsorship.Woman";
$womanObj = RNCPHP\ROQL::queryObject($roql)->next();
$returnArray =  array();
while($woman = $womanObj->next()) {
    $WOMANID = ' ';
    $OLDSTATUS = ' ';
    $NEWSTATUS = ' ';
    
    $CHANGED = "NO";
    $newStatusObj = null;

    //set woman age
    if($woman->MonthOfBirth > 0 && $woman->DayOfBirth > 0 && $woman->YearOfBirth > 0){   
        $yearsOld = getAge($woman->MonthOfBirth."/".$woman->DayOfBirth."/".$woman->YearOfBirth);
        if(intval($woman->Age) != intval($yearsOld) && intval($yearsOld) > 0){
            $woman->Age = intVal($yearsOld);
            $CHANGED = "YES";
        }
        
    }
    try{
$WOMANID = $woman->ID;
        if(!$woman->DoNotAutoUpdate){
$OLDSTATUS = $woman->ScholarshipStatus->LookupName;
            $newStatus = getStatus($woman, $active_pledge_status_list);
            if($newStatus){
                $newStatusObj = RNCPHP\sponsorship\ScholarshipStatus::fetch(intval($newStatus));
                $woman->ScholarshipStatus = RNCPHP\sponsorship\ScholarshipStatus::fetch(intval($newStatus));
$NEWSTATUS = $newStatusObj->LookupName;
            
                //echo $woman->ScholarshipStatus->LookupName.",".$newStatusObj->LookupName.",";
                if($NEWSTATUS != $OLDSTATUS){
                    $CHANGED = "YES";
                }
            }
            
        }
    }catch (Exception $e) {
        return outputResponse($returnArray, $e->getMessage(), 500);
    } catch (RNCPHP\ConnectAPIError $err) {
        return outputResponse($returnArray, $err->getMessage(), 500);
    }

    $returnArray[] =  $WOMANID.",".$OLDSTATUS.",".$NEWSTATUS.",".$woman->Age.",".$CHANGED;
    if($CHANGED == "YES"){}
        $woman->save(RNCPHP\RNObject::SuppressAll);



}

return outputResponse($returnArray, null);
//End


function getStatus($woman, $active_pledge_status_list){


    $womanInfo = getActivePledgeTotal($woman->ID, $active_pledge_status_list);
    $activePledgesTotal = $womanInfo['amt'];
    $allPledgeCount = $womanInfo['total_pledges'];
    
    //echo "activePledgesTotal:$activePledgesTotal  allPledgeCount:$allPledgeCount  womanrate:".$woman->Rate."\n<br/>";

    if(!$woman->Rate || $woman->Rate < 1){ return null; }
    //sponsored
    if($activePledgesTotal >= $woman->Rate){
        return FULL_SCHOLARSHIP;
    }else if($activePledgesTotal < $woman->Rate && $activePledgesTotal > 0){
        return PART_SCHOLARSHIP;
    }else{
        return null;
    }
}

function getAge($birthdate){
    //echo $birthdate."<br/>";
    $adjust = (date("md") >= date("md", strtotime($birthdate))) ? 0 : -1; 
    $years = date("Y") - date("Y", strtotime($birthdate));
    return $years + $adjust; 
}

function getActivePledgeTotal($womanId, $active_pledge_status_list){
    $totalAmt = 0;
    $numTotalPledges = 0;
    
    $roql = "Select donation.pledge.PledgeAmount, donation.pledge.PledgeStatus, donation.pledge.Frequency from donation.pledge where donation.pledge.woman.ID = ".$womanId;
    $pledgeObj = RNCPHP\ROQL::query($roql)->next();
    
    while($pledge = $pledgeObj->next()) {

        if(in_array(intval($pledge['PledgeStatus']), $active_pledge_status_list)){
            
            if($pledge['Frequency'] == 1)//annual
                $totalAmt += $pledge['PledgeAmount'] / 12;
            else if($pledge['Frequency'] == 7)//quarterly
                $totalAmt += $pledge['PledgeAmount'] / 3;
            else //monthly
                $totalAmt += $pledge['PledgeAmount'];
            
            
        }
        $numTotalPledges++;
    }
    
    //print_r(array('amt'=>$totalAmt, 'total_pledges'=>$numTotalPledges));
    return array('amt'=>$totalAmt, 'total_pledges'=>$numTotalPledges);
    
}
