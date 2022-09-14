﻿<?php

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

$lastContactID = 0;
$continue = true;
 
while($continue){

    $roql = "Select Contact from Contact where Contact.ID > $lastContactID";
    //$roql = "Select Contact from Contact where Contact.ID = 27880";
    $contactObjs = RNCPHP\ROQL::queryObject($roql)->next();
    
    $continue = false;
    while($contact = $contactObjs->next()) {
        $save = false;
        //echo $contact->ID."<br/>\n";
        try{

            $roql_pledge = "SELECT donation.pledge.Contact.CustomFields.c.activepledgecount, count() FROM donation.pledge WHERE donation.pledge.Contact.ID = ".$contact->ID." and (donation.pledge.Type1 = 2 or donation.pledge.Type1 = 39) and (donation.pledge.PledgeStatus.LookupName = 'Active' or donation.pledge.PledgeStatus.LookupName = 'On Hold - Non Payment' or donation.pledge.PledgeStatus.LookupName = 'Manual Pay' )";
        
            $pledgeResp = RNCPHP\ROQL::query($roql_pledge)->next();
            $pledge = $pledgeResp->next();
            
            if($pledge['count()'] != $pledge['activepledgecount']){
              //echo $contact->ID." change pledge:".$pledge['count()'].":".$pledge['activepledgecount'].":\n";
              $contact->CustomFields->c->activepledgecount = $pledge['count()'];
              $save = true;
            }
            
            $roql_spon_pledge = "SELECT donation.pledge.Contact.CustomFields.c.activesponsorshipcount, count() FROM donation.pledge WHERE donation.pledge.Contact.ID = ".$contact->ID." and donation.pledge.Child.ID > 0 and (donation.pledge.Type1 = 2 or donation.pledge.Type1 = 39) and (donation.pledge.PledgeStatus.LookupName = 'Active' or donation.pledge.PledgeStatus.LookupName = 'On Hold - Non Payment' or donation.pledge.PledgeStatus.LookupName = 'Manual Pay' )";
            //echo $roql_spon_pledge;
    
            $pledgeSponResp = RNCPHP\ROQL::query($roql_spon_pledge)->next();
            $pledgeSpon = $pledgeSponResp->next();
            
            if($pledgeSpon['count()'] != $pledgeSpon['activesponsorshipcount']){
              //echo $contact->ID." change spon:".$pledgeSpon['count()'].":".$pledgeSpon['activesponsorshipcount'].":\n";
              $contact->CustomFields->c->activesponsorshipcount = $pledgeSpon['count()'];
              $save = true;
            }
            
    
            if($save == true){
                //echo $contact->ID." saving \n";
                $contact->save();
                RNCPHP\ConnectAPI::commit();
            }
        
        }catch(Exception $e){
            $returnArray[] = $e->getMessage();
        }
        catch(RNCPHP\ConnectAPIError $err){
            $returnArray[] = $err->getMessage();
        }
        
        $contcount++;
        $continue = true;
        $lastContactID = $contact->ID;
    }

    
}

return outputResponse($returnArray, null);
    


?>