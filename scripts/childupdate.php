<?php

//Author: Zach Cordell
//Date: 5/1/15
//Purpose: cron utility will be run every 1 time per day.  Will process pledges that are due to be run today

ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$ip_dbreq = true;
require_once('include/init.phph');

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');

/**************CONSTANTS*******************/
CONST EVT_UPDATE_NEEDED = 3;
CONST EVT_WEB_HOLD = 1;
CONST EVT_EVENT_HOLD = 2;
CONST SPON_SPONSORED = 3;
CONST SPON_CO_SPONSOR_NEEDED = 4;
CONST SPON_NCR_CO_SPONSOR_NEEDED = 65;
CONST SPON_DROPPED = 5;
CONST SPON_REGISTERED = 2;
CONST SPON_DEPARTED = 7;
CONST SPON_GRADUATED = 8;
CONST SPON_DEPARTURE_ALERT = 6;
CONST SPON_DEATH = 49;
CONST SPON_UPDATE = 10;
CONST SPON_EVENT_HOLD = 12;
CONST SPON_WEB_HOLD = 11;
CONST NCR_COMMUNITY_ID = 6;
CONST PLEDGE_SPECIAL_HOLD = 81;
$check_spon_status_list = array(SPON_UPDATE, SPON_EVENT_HOLD, SPON_WEB_HOLD, SPON_SPONSORED, SPON_CO_SPONSOR_NEEDED, SPON_NCR_CO_SPONSOR_NEEDED, SPON_DROPPED, SPON_REGISTERED);//only checking children of this status
$active_pledge_status_list = array(1,2,43);//active, manual, on hold pay


echo "Child ID, Old Status, New Status, Old Priority, New Priority, Changed?, Event Status \n";

//Begin
$roql = "Select sponsorship.Child from sponsorship.Child";
$childObj = RNCPHP\ROQL::queryObject($roql)->next();
while($child = $childObj->next()) {
    $CHILDID = ' ';
    $OLDSTATUS = ' ';
    $NEWSTATUS = ' ';
    $OLDPRIORITY = ' ';
    $NEWPRIORITY = ' ';
    $CHANGED = "NO";
    $newStatusObj = null;
    //set child age
    if($child->MonthOfBirth > 0 && $child->DayOfBirth > 0 && $child->YearOfBirth > 0){   
        $yearsOld = getAge($child->MonthOfBirth."/".$child->DayOfBirth."/".$child->YearOfBirth);
        if(intval($child->Age) != intval($yearsOld) && intval($yearsOld) > 0){
            $child->Age = intVal($yearsOld);
            $CHANGED = "YES";
        }
        //fill in the  birthday date field if it its not already filled in.
        if(!$child->Birthday){
            $child->Birthday = mktime(0,0,0,intval($child->MonthOfBirth),intval($child->DayOfBirth), intval($child->YearOfBirth));
            $CHANGED = "YES";
        }
               
    }

$CHILDID = $child->ID;
    if(!$child->DoNotAutoUpdate && in_array($child->SponsorshipStatus->ID, $check_spon_status_list)){
$OLDSTATUS = $child->SponsorshipStatus->LookupName;     
        $newStatus = getStatus($child, $active_pledge_status_list);
        $newStatusObj = RNCPHP\sponsorship\SponsorshipStatus::fetch(intval($newStatus));
        $child->SponsorshipStatus = RNCPHP\sponsorship\SponsorshipStatus::fetch(intval($newStatus));
$NEWSTATUS = $newStatusObj->LookupName;
        //echo $child->SponsorshipStatus->LookupName.",".$newStatusObj->LookupName.",";
        if($NEWSTATUS != $OLDSTATUS){
$CHANGED = "YES";
        }
    }
    
$OLDPRIORITY =  $child->Priority;
    $child->Priority = ($child->ManualOverridePriority) ? $child->ManualOverridePriority : getPriority($newStatusObj->ID, $child);
$NEWPRIORITY = $child->Priority;
    if($OLDPRIORITY != $NEWPRIORITY)
$CHANGED = "YES";
    
   
    
    if($child->SponsorshipStatus->ID == SPON_DROPPED && 
        $OLDSTATUS != $NEWSTATUS && 
            ($OLDSTATUS != "Web Hold" && $OLDSTATUS != "Event Hold")){//changed to dropped
        $child->ChildEventStatus = RNCPHP\sponsorship\ChildEventStatus::fetch(EVT_UPDATE_NEEDED);
    }else if($child->SponsorshipStatus->ID == SPON_UPDATE){
        $child->ChildEventStatus = RNCPHP\sponsorship\ChildEventStatus::fetch(EVT_UPDATE_NEEDED);
    }else if($child->SponsorshipStatus->ID == SPON_EVENT_HOLD){
        $child->ChildEventStatus = RNCPHP\sponsorship\ChildEventStatus::fetch(EVT_EVENT_HOLD);
    }else if($child->SponsorshipStatus->ID == SPON_WEB_HOLD){
        $child->ChildEventStatus = RNCPHP\sponsorship\ChildEventStatus::fetch(EVT_WEB_HOLD);
    }else if($child->SponsorshipStatus->ID == SPON_SPONSORED && $child->ChildEventStatus != null){
        $CHANGED = "YES";
        $child->ChildEventStatus = null;
    }


    echo $CHILDID.",".$OLDSTATUS.",".$NEWSTATUS.",".$OLDPRIORITY.",".$NEWPRIORITY.",".$CHANGED.",".$child->ChildEventStatus->LookupName."\n";


    if($CHANGED == "YES")
        $child->save(RNCPHP\RNObject::SuppressAll);
}
//End


function getStatus($child, $active_pledge_status_list){

    $childInfo = getActivePledgeTotal($child->ID, $active_pledge_status_list);
    if(empty($childInfo)){
        //return their current sponsorship status and it won't update.
        //echo "/n returning old sponsorhsip status:".$child->SponsorshipStatus->LookupName."\n";
        return $child->SponsorshipStatus->ID;
    }
    $activePledgesTotal = $childInfo['amt'];
    $allPledgeCount = $childInfo['total_pledges'];
    
    //echo "activePledgesTotal:$activePledgesTotal  allPledgeCount:$allPledgeCount \n<br/>";
    //sponsored
    if($activePledgesTotal >= $child->Rate){
        return SPON_SPONSORED;
    }
    
    //co spon
    if($activePledgesTotal < $child->Rate && $activePledgesTotal > 0){
        return ($child->Community->ID == NCR_COMMUNITY_ID) ? SPON_NCR_CO_SPONSOR_NEEDED : SPON_CO_SPONSOR_NEEDED;
    }
    
    //dropped
    if( (!$activePledgesTotal || $activePledgesTotal <= 0) &&  $allPledgeCount > 0){
        return SPON_DROPPED;
    }
    
    //registered
    if( (!$activePledgesTotal || $activePledgesTotal <= 0) &&  $allPledgeCount <= 0){
        return SPON_REGISTERED;
    }
    
    
    //echo "Child:".$child->ID." TotalPledges:".$allPledgeCount." TotalAmount:".$activePledgesTotal." isChildSenior:".$senior." Community:".$child->Community->ID."\n<br/>";
}

function getAge($birthdate){
    //echo $birthdate."<br/>";
    $adjust = (date("md") >= date("md", strtotime($birthdate))) ? 0 : -1; 
    $years = date("Y") - date("Y", strtotime($birthdate));
    return $years + $adjust; 
}

function getActivePledgeTotal($childId, $active_pledge_status_list){
    $totalAmt = 0;
    $numTotalPledges = 0;
    
    $roql = "Select donation.pledge.PledgeAmount, donation.pledge.PledgeStatus, donation.pledge.Frequency from donation.pledge where donation.pledge.child.ID = ".$childId;
    $pledgeObj = RNCPHP\ROQL::query($roql)->next();
    
    while($pledge = $pledgeObj->next()) {

        if(intval($pledge['PledgeStatus']) == PLEDGE_SPECIAL_HOLD){
            return false;
        }

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
    
    return array('amt'=>$totalAmt, 'total_pledges'=>$numTotalPledges);
    
}

//return 12+, 6-12, <6 months.
function getChildEnteredDate($child){

    if($child->CreatedTime < strtotime("12 months ago")){
        return 12;
    }else if($child->CreatedTime < strtotime("6 months ago") && $child->CreatedTime > strtotime("12 months ago")){
        return 6;
    }else if($child->CreatedTime > strtotime("6 months ago")){
        return 0;
    }else{
        return null;
    }
}

function getPriority($statusId, $child){
    
    switch($statusId){
        
        case SPON_NCR_CO_SPONSOR_NEEDED:
            return 2;
            break;
        case SPON_CO_SPONSOR_NEEDED:
        case SPON_DROPPED:
            return 1;
            break;
        case SPON_REGISTERED:
            $howOld = getChildEnteredDate($child);
            if($howOld == 12)
                return 2;
            if($howOld == 6)
                return 3;
            if($howOld == 0)
                return 4;
            break;   
        default:
            return null; 
    }

}

?>