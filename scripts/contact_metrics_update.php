<?php
/*
 Version     :   1.0
 Author      :   Zach Cordell
 Purpose     :   Metrics update for contacts anlm

Should be used with a shell script

linux
for var in {702..703}; do curl -o "contact_updatelogs_$var.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/contact_metrics_update.php?start=$var"; done
for var in {270..700}; do curl -o "contact_updatelogs_$var.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/contact_metrics_update.php?start=$var"; done

windows
Create a folder in /users/documents/<username>/ called logs (you just have to do this once)
Open Windows Command Line.  
From Command Line Paste in the following command and hit return 
cd ..\Documents\logs
From Command Line Paste in the following command and hit return.  Should run for about 2 hours-ish. 
for /l %x in (0, 1, 670) do curl -o "contact_updatelogs_%x.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/contact_metrics_update.php?start=%x"
 
AWS:


*/

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
define('CONTACT_CHUNK_SIZE', 30);


require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';

if (!function_exists("\curl_init"))
    \load_curl();


// /********CONSTANTS*********/

switch(intval($_GET['start'])){

    case intval($_GET['start']) < 0:
        returnContactIdChunks();
        break;
    default:
        executeUpdatesForRecords($_GET['start']);
        break;

}


function returnContactIdChunks(){
    try {
        $response = array();

        $lowestBound = 0;
        $highestBound = 0;

        $lowestContactROQL = "SELECT Contact.ID FROM Contact ORDER BY Contact.ID ASC LIMIT 1";
        $highestContactROQL = "SELECT Contact.ID FROM Contact ORDER BY Contact.ID DESC LIMIT 1";

        $lowestRes = RNCPHP\ROQL::query($lowestContactROQL)->next();
 
        while($contact = $lowestRes->next()) {
            $lowestBound = intval($contact['ID']) / 100;
        }

        $highestRes = RNCPHP\ROQL::query($highestContactROQL)->next();
 
        while($contact = $highestRes->next()) {
            $highestBound = intval($contact['ID']) / 100;
        }

        $loopCounter = intval($lowestBound);
        $arrayCounter = 0;

        while($loopCounter < $highestBound){

            for($x = $loopCounter; $x < $loopCounter + CONTACT_CHUNK_SIZE; $x++){
                $response['chunks'][$arrayCounter]['startingIds'][] = $x;
            }
            $arrayCounter++;
            $loopCounter += CONTACT_CHUNK_SIZE;

        }

        $response['getChunks'] = true;

 
        return outputResponse($response, null);
    } catch (\Exception $ex) {
        return outputResponse(null, $ex->getMessage());
    } catch (RNCPHP\ConnectAPIError $ex) {
        return outputResponse(null, $ex->getMessage());
    }


}


function executeUpdatesForRecords($startVal){

    $start = $startVal * 100;
    $end = $start + 100; 

    $returnVal = array();
    //array of funds to disreguard when getting the yearly total donations
    $fundsToIgnore = array('STM');

    for($currentRecord = $start; $currentRecord < $end; $currentRecord++){

        try{

            //fetching contact w/o id will terminate if we don't stick it in its own try
            try{
                $contactObj = RNCPHP\Contact::fetch($currentRecord);
            } catch (\Exception $ex) {
                continue;
            } catch (RNCPHP\ConnectAPIError $ex) {
                continue;
            }

            if(empty($contactObj)){ continue; }


            /*********Get report results */

            $contact_filter= new RNCPHP\AnalyticsReportSearchFilter;
            $contact_filter->Name = 'contactID';
            $contact_filter->Values = array( intval($currentRecord) );
            $filters = new RNCPHP\AnalyticsReportSearchFilterArray;
            $filters[] = $contact_filter;
            $transactionList = array();
            $ar = RNCPHP\AnalyticsReport::fetch(101751);
            $transactionList = $ar->run(0, $filters);

            if ($transactionList->count() < 1 ) { 
                continue; 
            }

            $firstIsSet = false;
            $count = 0;
            $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear = 0;
            $contactObj->CustomFields->Metrics->totalDonationAmtLastYear = 0;
            $contactObj->CustomFields->Metrics->largestDonationDate2Years = null;
            $contactObj->CustomFields->Metrics->largestDonationAmt2Years = null;
            $contactObj->CustomFields->Metrics->largestDonationFund2Years = null;

            $uniqueFundArray = array();
            
            while($transaction = $transactionList->next()) {

                //get the fund for the transaction.
                if($transaction['Donation Type'] == 'Gift'){
                    $fund = getGiftFund($transaction['Donation ID']);
                }else{
                    list($fund, $child) = getPledgeDetails($transaction['Donation ID']);
                }

                //first donation
                if($firstIsSet == false){
                    $contactObj->CustomFields->Metrics->firstDonationAmt = intval($transaction['Total Charge']);
                    $contactObj->CustomFields->Metrics->firstDonationDate = $transaction['Donation Date'];
                    if(!empty($fund))
                        $contactObj->CustomFields->Metrics->firstDonationFund = $fund;
                    $firstIsSet = true;
                }
                
                //set largest donation
                if(intval($transaction['Total Charge']) >= $contactObj->CustomFields->Metrics->largestDonationAmt){
                    $contactObj->CustomFields->Metrics->largestDonationAmt = intval($transaction['Total Charge']);
                    $contactObj->CustomFields->Metrics->largestDonationDate = $transaction['Donation Date'];
                    if(!empty($fund))
                        $contactObj->CustomFields->Metrics->largestDonationFund = $fund;
                }
                
                //set largest 2 years
                if(intval($transaction['Total Charge']) >= $contactObj->CustomFields->Metrics->largestDonationAmt2Years && 
                    strtotime($transaction['Donation Date']) > strtotime('-2 years')){
                    $contactObj->CustomFields->Metrics->largestDonationAmt2Years = intval($transaction['Total Charge']);
                    $contactObj->CustomFields->Metrics->largestDonationDate2Years = $transaction['Donation Date'];
                    if(!empty($fund))
                        $contactObj->CustomFields->Metrics->largestDonationFund2Years = $fund;
                }

                //set total for donations that are not sponsorship or STM
                if(strtotime($transaction['Donation Date']) > strtotime('Midnight January 1 '.date(Y)) && !in_array($fund->LookupName, $fundsToIgnore)){
                    $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear += $transaction['Total Charge'];
                }

                //set total for donations last year that are not sponsorship or STM
                if(strtotime($transaction['Donation Date']) < strtotime('Midnight January 1 '.date(Y)) && 
                     strtotime($transaction['Donation Date']) > strtotime('Midnight January 1 '.date("Y",strtotime("-1 year"))) &&  !in_array($fund->LookupName, $fundsToIgnore)){
                    $contactObj->CustomFields->Metrics->totalDonationAmtLastYear += $transaction['Total Charge'];
                }

                

                //last donation is just the last record we have after the loop.
                $contactObj->CustomFields->Metrics->recentDonationAmt = intval($transaction['Total Charge']);
                $contactObj->CustomFields->Metrics->recentDonationDate = $transaction['Donation Date'];
                if(!empty($fund))
                    $contactObj->CustomFields->Metrics->recentDonationFund = $fund;

                //total donation with completed transactions
                $count++;

                //number of unique funds
                //summary of donations per fund
                if(!empty($fund)){
                    $uniqueFundArray[$fund->LookupName] = $uniqueFundArray[$fund->LookupName] + 1;
                }

                
            }

            arsort($uniqueFundArray);
            
            $contactObj->CustomFields->Metrics->totalCompletedDonations = $count;
            $contactObj->CustomFields->Metrics->numUniqueFunds = count($uniqueFundArray);
            $contactObj->CustomFields->Metrics->donationsPerUniqueFund = substr(json_encode($uniqueFundArray), 0, 255);

            //start date of first recurring pledge
            list($startDate, $startDateSpon) = getStartDateOfFirstPledge($contactObj->ID);

            if(!empty($startDate))
                $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge = $startDate;

            if(!empty($startDateSpon))
                $contactObj->CustomFields->Metrics->startDateFirstSponsorship = $startDateSpon;
            
            
            $results = array();
            $results['contactId'] = $contactObj->ID;
            $results['firstDonationDate'] = (empty($contactObj->CustomFields->Metrics->firstDonationDate)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->firstDonationDate);
            $results['firstDonationAmt'] = $contactObj->CustomFields->Metrics->firstDonationAmt;
            $results['firstDonationFund'] = $contactObj->CustomFields->Metrics->firstDonationFund->LookupName;
            $results['recentDonationDate'] = (empty($contactObj->CustomFields->Metrics->recentDonationDate)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->recentDonationDate);
            $results['recentDonationAmt'] = $contactObj->CustomFields->Metrics->recentDonationAmt;
            $results['recentDonationFund'] = $contactObj->CustomFields->Metrics->recentDonationFund->LookupName;
            $results['largestDonationDate'] = (empty($contactObj->CustomFields->Metrics->largestDonationDate)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->largestDonationDate);
            $results['largestDonationAmt'] = $contactObj->CustomFields->Metrics->largestDonationAmt;
            $results['largestDonationFund'] = $contactObj->CustomFields->Metrics->largestDonationFund->LookupName;
            $results['largestDonationDate2Yrs'] = (empty($contactObj->CustomFields->Metrics->largestDonationDate2Years)) ? "": date('Y/m/d', $contactObj->CustomFields->Metrics->largestDonationDate2Years);
            $results['largestDonationAmt2Yrs'] = $contactObj->CustomFields->Metrics->largestDonationAmt2Years;
            $results['largestDonationFund2Yrs'] = $contactObj->CustomFields->Metrics->largestDonationFund2Years->LookupName;
            $results['startDateFirstRecurringPledge'] = (empty($contactObj->CustomFields->Metrics->startDateFirstRecurringPledge)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge);
            $results['startDateFirstRecurringSponsorship'] = (empty($contactObj->CustomFields->Metrics->startDateFirstSponsorship)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->startDateFirstSponsorship);
            $results['totalDonationAmtCurrentYear'] = (empty($contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear)) ? "" : $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear;
            $results['totalDonationAmtLastYear'] = (empty($contactObj->CustomFields->Metrics->totalDonationAmtLastYear)) ? "" : $contactObj->CustomFields->Metrics->totalDonationAmtLastYear;
            $results['numUniqueFunds'] = count($uniqueFundArray);
            $results['donationsPerUniqueFund'] = $contactObj->CustomFields->Metrics->donationsPerUniqueFund;

            $contactObj->save();
            
            $returnVal[] = $results;
            
        } catch (\Exception $ex) {
            return outputResponse(null, $ex->getMessage());
        } catch (RNCPHP\ConnectAPIError $ex) {
            return outputResponse(null, $ex->getMessage());
        }

    }
    return outputResponse(json_encode($returnVal), null);

}



function getPledgeDetails($donationId = 0) {
    try {
        $roql = sprintf("SELECT don.PledgeRef FROM donation.donationToPledge as don where donation.donationToPledge.DonationRef.ID = %d Limit 1", $donationId);
        $pledges = RNCPHP\ROQL::queryObject($roql) -> next();
        
        while ($pledge = $pledges -> next()) {
            return array($pledge->Fund, $pledge->Child);
        }

    } catch(\Exception $e) {
        return false;
    }

    return null;
}

function getGiftFund($donationId = 0) {
    try {
        $roql = sprintf("Select donation.DonationItem from donation.DonationItem where donation.DonationItem.DonationId = %d Limit 1", $donationId);
        $items = RNCPHP\ROQL::queryObject($roql) -> next();
        
        while ($item = $items -> next()) {
            return $item->Item->DonationFund;
        }
    } catch(\Exception $e) {
        return false;
    }

    return null;
}

function getStartDateOfFirstPledge($contactId){
    
    try {
        $roql = "select donation.Pledge from donation.pledge where donation.Pledge.Contact.ID = $contactId AND donation.Pledge.Type1.LookupName = 'Recurring' Order by donation.Pledge.StartDate ASC";
        $pledges = RNCPHP\ROQL::queryObject($roql) -> next();
        
        $startDateSpon = null;
        $startDate = null;

        while ($pledge = $pledges -> next()) {
            
            if(empty($startDate)){
                $startDate = $pledge->StartDate;
            }

            if(!empty($pledge->Child) && !$startDateSpon){
                $startDateSpon = $pledge->StartDate;
            }
            
        }

        return array($startDate, $startDateSpon);
    } catch(\Exception $e) {
        return false;
    }

    return null;
}


function logError($table, $recordID, $message){
    try{
        $log = new RNCPHP\ESG\Log();
        $log->RecordType = $table;
        $log->RecordId = $recordID;
        $log->Message = $message;
        $log->save();
    }catch(Exception $e){
    }
}

