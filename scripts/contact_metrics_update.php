<?php
/*
 Version     :   1.0
 Author      :   Zach Cordell
 Purpose     :   Metrics update for contacts anlm

Should be used with a shell script

linux
for var in {270..300}; do curl -o "contact_updatelogs_$var.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/contact_metrics_update.php?start=$var"; done

windows
Create a folder in /users/documents/<username>/ called logs (you just have to do this once)
Open Windows Command Line.  
From Command Line Paste in the following command and hit return 
cd ..\Documents\logs
From Command Line Paste in the following command and hit return.  Should run for about 2 hours-ish. 
for /l %x in (0, 1, 670) do curl -o "contact_updatelogs_%x.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/contact_metrics_update.php?start=%x"
 
*/
error_reporting(E_ALL);
 $ip_dbreq = true;

if (!defined('DOCROOT'))
    define('DOCROOT', get_cfg_var('doc_root'));

require_once (DOCROOT . '/include/config/config.phph');
require_once (DOCROOT . '/include/ConnectPHP/Connect_init.phph');

initConnectAPI();
use RightNow\Connect\v1_3 as RNCPHP;
//Agent Session for Agent Console, Credentials for Portal/
require_once(DOCROOT . '/include/services/AgentAuthenticator.phph');
$account = AgentAuthenticator::authenticateCredentials('zcordell', 'Password1');
if (!function_exists("\curl_init"))
    \load_curl();


/********CONSTANTS*********/

$start = $_GET['start'] * 100;
$end = $start + 100;

// $start = $_GET['start'];
// $end = $start + 1;

if($start < 1){
    echo "****No start defined********\n";
    return ;
}

//array of funds to disreguard when getting the yearly total donations
$fundsToIgnore = array('STM');

for($currentRecord = $start; $currentRecord < $end; $currentRecord++){

    try{
        echo "****Contact:$currentRecord ********\n";
        $contactObj = RNCPHP\Contact::fetch($currentRecord);
        if(empty($contactObj)){ continue; }

        $roql = "Select financial.transactions from financial.transactions as trans where trans.donation.Contact.ID = $currentRecord AND trans.CurrentStatus.LookupName = 'Completed' order by trans.donation.DonationDate ASC";
        echo "$roql \n";
        $res = RNCPHP\ROQL::queryObject($roql)->next();
        if ($res->count() < 1 ) { 
            continue; 
        }

        $firstIsSet = false;
        $count = 0;
        $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear = 0;
        $contactObj->CustomFields->Metrics->totalDonationAmtLastYear = 0;
        
        while($transaction = $res->next()) {

            //get the fund for the transaction.
            if($transaction->donation->Type->LookupName == 'Gift'){
                $fund = getGiftFund($transaction->donation->ID);
            }else{
                list($fund, $child) = getPledgeDetails($transaction->donation->ID);
            }

            //first donation
            if($firstIsSet == false){
                $contactObj->CustomFields->Metrics->firstDonationAmt = intval($transaction->totalCharge);
                $contactObj->CustomFields->Metrics->firstDonationDate = $transaction->donation->DonationDate;
                if(!empty($fund))
                    $contactObj->CustomFields->Metrics->firstDonationFund = $fund;
                $firstIsSet = true;
            }
            
            //set largest donation
            if(intval($transaction->totalCharge) >= $contactObj->CustomFields->Metrics->largestDonationAmt){
                $contactObj->CustomFields->Metrics->largestDonationAmt = intval($transaction->totalCharge);
                $contactObj->CustomFields->Metrics->largestDonationDate = $transaction->donation->DonationDate;
                if(!empty($fund))
                    $contactObj->CustomFields->Metrics->largestDonationFund = $fund;
            }
            
            //set largest 2 years
            if(intval($transaction->totalCharge) >= $contactObj->CustomFields->Metrics->largestDonationAmt2Years && $transaction->donation->DonationDate > strtotime('-2 years')){
                $contactObj->CustomFields->Metrics->largestDonationAmt2Years = intval($transaction->totalCharge);
                $contactObj->CustomFields->Metrics->largestDonationDate2Years = $transaction->donation->DonationDate;
                if(!empty($fund))
                    $contactObj->CustomFields->Metrics->largestDonationFund2Years = $fund;
            }

            //set total for donations that are not sponsorship or STM
            if($transaction->donation->DonationDate > strtotime('Midnight January 1 '.date(Y)) && empty($child) && !in_array($fund->LookupName, $fundsToIgnore)){
                $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear += $transaction->totalCharge;
            }

            //set total for donations last year that are not sponsorship or STM
            if($transaction->donation->DonationDate < strtotime('Midnight January 1 '.date(Y)) && 
                $transaction->donation->DonationDate > strtotime('Midnight January 1 '.date("Y",strtotime("-1 year"))) && 
                empty($child) && !in_array($fund->LookupName, $fundsToIgnore)){
                $contactObj->CustomFields->Metrics->totalDonationAmtLastYear += $transaction->totalCharge;
            }

            

            //last donation is just the last record we have after the loop.
            $contactObj->CustomFields->Metrics->recentDonationAmt = intval($transaction->totalCharge);
            $contactObj->CustomFields->Metrics->recentDonationDate = $transaction->donation->DonationDate;
            if(!empty($fund))
                $contactObj->CustomFields->Metrics->recentDonationFund = $fund;

            //total donation with completed transactions
            $count++;
            
        }
        
        $contactObj->CustomFields->Metrics->totalCompletedDonations = $count;

        //start date of first recurring pledge
        list($startDate, $startDateSpon) = getStartDateOfFirstPledge($contactObj->ID);

        if(!empty($startDate))
            $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge = $startDate;

        if(!empty($startDateSpon))
            $contactObj->CustomFields->Metrics->startDateFirstSponsorship = $startDateSpon;
        
        
        $results = array();
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


        echo "Results:";
        print_r($results);
        echo "\n\n";

        $contactObj->save();
    }catch(Exception $e){
        echo "Error:".$e->getMessage()."\n";
    }catch(RNCP\ConnectAPIError $err) {
        echo "Error:".$err->getMessage()."\n";
    }

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
        $roql = "select donation.Pledge from donation.pledge where donation.Pledge.Contact.ID = $contactId AND donation.Pledge.Type1.LookupName = 'Recurring' Order by donation.Pledge.CreatedTime ASC";
        echo "/n $roql /n";
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

