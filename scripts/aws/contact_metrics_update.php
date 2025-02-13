<?php
/*
 Version     :   1.0
 Author      :   Zach Cordell
 Purpose     :   Metrics update for contacts anlm

Should be used with a shell script

linux
for var in {436..436}; do curl -o "contact_updatelogs_$var.txt" -H "HTTP_X_CUSTOM_AUTHORIZATION: emNvcmRlbGw6UGFzc3dvcmQx" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/aws/contact_metrics_update.php?start=$var"; done

windows
Create a folder in /users/documents/<username>/ called logs (you just have to do this once)
Open Windows Command Line.  
From Command Line Paste in the following command and hit return 
cd ..\Documents\logs
From Command Line Paste in the following command and hit return.  Should run for about 2 hours-ish. 
for /l %x in (0, 1, 670) do curl -o "contact_updatelogs_%x.txt" "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/aws/contact_metrics_update.php?start=%x"
 
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
define('CONTACT_CHUNK_SIZE', 100);


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
    $nonSponFundArray = array("ACT","ACT Dorm","ACT Endow","ACT Mobile","ADMIN","AfricRadio","AGIFT","ANLM","Austin","BBAN","BEN","BFBLK","BIBLE","BNF","BNQT","BRICKS","BRKMCH","Bugesera","BUILD","CAMP","Chicago","Comm. Devp","COMP","CONTF","CONTI","CROSS","DAY","DBA","DCBAN","DCTR","DHF","DMC","DORM","DRIGG College","DRM Tutoring","DVPT","Estate & Planned Giving","Executive","FAC","Gallery","Gashora Community","GEAR","GENFND","GIFT","GIFTCH","GIFTCO","Halo Endowment","HINDDVLP","HINDURWA","HINDURWACD","HINDURWAED","HINDURWATO","Huye Community","INTM","INTMO","Karangazi Community","KATAF","Kayonza C","KBASK","KCL","Keyhole Gardens","KGB Comm","KGIFT","Kigali Community","Kilimanjaro","KNBB","KRC","kyz","LEADE","Life Skills","LittleRock","LIVE","MedSchol","Multnomah","NATM","NATMC","NLBC","NLBRENT","NOK","Nurse","Nyagatare Community","Nyamagabe Community","Nyamirama Community","Nyanza Community","NYC","OPER","PDX Banque","PPR","PUBHLTH","RADIO","Refugee","REST","RSSF","RSV","RTK","Rubavu","RUGAE","SA Fundraiser","Scholastic","Sewing Machines","SLK","SPCPJT","STKids","STS","SysDVLP","TEACH","TTM","UK","Unallocated","US Program Management","USOPR","VANMANT","VSP","WMN","Women's Development Programs","ZOPHFM");
            
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

            $filters = new RNCPHP\AnalyticsReportSearchFilterArray;

            $contact_filter= new RNCPHP\AnalyticsReportSearchFilter;
            $contact_filter->Name = 'PledgeContact';
            $contact_filter->Values = array( intval($currentRecord) );
            $filters[] = $contact_filter;

            $contact_filter= new RNCPHP\AnalyticsReportSearchFilter;
            $contact_filter->Name = 'DonationContact';
            $contact_filter->Values = array( intval($currentRecord) );
            $filters[] = $contact_filter;
            
            $transactionList = array();
            $ar = RNCPHP\AnalyticsReport::fetch(101751);
            $transactionList = $ar->run(0, $filters);

            // if ($transactionList->count() < 1 ) { 
            //     continue; 
            // }

            $firstIsSet = false;
            $firstNonSponSet = false;
            $saveNeeded = false;
            

            $count = 0;
            
            //firsts

            //recents

            //largest

            //totals

            //initialize
            $totalDonationAmtCurrentYear = 0;
            $totalDonationAmtLastYear = 0;
            $largestDonationDate2Years = null;
            $largestDonationAmt2Years = null;
            $largestDonationFund2Years = null;
            $totalDonationsLifetime = 0;
            $totalNumberDonationsLifetime = 0;
            $totalSoftDonationsLifetime = 0;
            $firstDonationDate = null;
            $firstDonationAmt = null;
            $firstDonationFund = null;
            $largestDonationAmtWGifts = null;
            $largestDonationDateWGifts = null;
            $recentDonationDate = null;
            $recentDonationAmt = null;
            $recentDonationFund = null;
            $recentNonSponDonationDate = null;
            $recentNonSponDonationAmt = null;
            $recentNonSponDonationFund = null;
            $largestDonationDate = null;
            $largestDonationAmt = null;
            $largestDonationFund = null;
            $recentSponsorshipDonation = null;
            $uniqueFundArray = array();
            $largestNonSponAmt = 0;
            $largestNonSponDate = null;
            $largestNonSponSoft = false;
            $largestNonSponFund = null;
            $firstNonSponAmt = 0;
            $firstNonSponDate = null;
            $firstNonSponSoft = false;
            $firstNonSponFund = null;
            $totalNonSponAmt = 0;
            $contactYearlyMetrics = array();

            

            while($transaction = $transactionList->next()) {

                //total Donation lifetime including gifts.
                $totalDonationsLifetime += $transaction['Total Charge'];
                //echo "This transaction:".$transaction['Total Charge']." Current Total:".$totalDonationsLifetime." DonationID:".$transaction['Donation ID']."\n";

                $totalNumberDonationsLifetime++;

                //aggregate all transactions for each year
                $yearOfDonation = getYearOfDonation($transaction);
                if(!empty($yearOfDonation)){
                    $contactYearlyMetrics[$yearOfDonation] += $transaction['Total Charge'];
                }

                //get the fund for the transaction.
                if($transaction['Donation Type'] == 'Gift'){
                    $fund = getGiftFund($transaction['Donation ID']);
                }else{
                    list($fund, $child) = getPledgeDetails($transaction['Donation ID']);
                }

                //set largest donation (include gifts) //need to use donation amount because gifts don't have pledges
                if(intval($transaction['Donation Amount']) >= $largestDonationAmtWGifts){
                    $largestDonationAmtWGifts = intval($transaction['Donation Amount']);
                    $largestDonationDateWGifts = $transaction['Donation Date'];
                }

                //most metrics we don't want gifts included
                if($transaction['Donation Type'] != 'Gift'){
                    
                    //first donation
                    if($firstIsSet == false){
                        $firstDonationAmt = intval($transaction['Total Charge']);
                        $firstDonationDate = $transaction['Donation Date'];

                        if(!empty($fund)){
                            $firstDonationFund = $fund;
                        }
                        $firstIsSet = true;
                    }

                    //set largest donation
                    if(intval($transaction['Total Charge']) >= $largestDonationAmt){
                        $largestDonationAmt = intval($transaction['Total Charge']);
                        $largestDonationDate = $transaction['Donation Date'];
                        if(!empty($fund))
                            $largestDonationFund = $fund;
                    }
                    
                    //set largest 2 years
                    if(intval($transaction['Total Charge']) >= $largestDonationAmt2Years && 
                        strtotime($transaction['Donation Date']) > strtotime('-2 years')){
                        $largestDonationAmt2Years = intval($transaction['Total Charge']);
                        $largestDonationDate2Years = $transaction['Donation Date'];
                        if(!empty($fund))
                            $largestDonationFund2Years = $fund;
                    }

                    //set total for donations that are not sponsorship or STM
                    if(strtotime($transaction['Donation Date']) > strtotime('Midnight January 1 '.date('Y')) && !in_array($fund->LookupName, $fundsToIgnore)){
                        $totalDonationAmtCurrentYear += $transaction['Total Charge'];
                    }

                    //set total for donations last year that are not sponsorship or STM
                    if(strtotime($transaction['Donation Date']) < strtotime('Midnight January 1 '.date('Y')) && 
                        strtotime($transaction['Donation Date']) > strtotime('Midnight January 1 '.date("Y",strtotime("-1 year"))) &&  !in_array($fund->LookupName, $fundsToIgnore)){
                        $totalDonationAmtLastYear += $transaction['Total Charge'];
                    }

                    //sponsorship donations the last 3 years. report sorted by date so the most recent should be last
                    if($child){
                        $recentSponsorshipDonation = $transaction['Donation Date'];
                    }

                    //last donation is just the last record we have after the loop.
                    $recentDonationAmt = intval($transaction['Total Charge']);
                    $recentDonationDate = $transaction['Donation Date'];
                    if(!empty($fund)){
                        $recentDonationFund = $fund;
                    }

                    //Latest Non Spon Donation excluding gifts
                    if(in_array($fund->LookupName, $nonSponFundArray)){
                        $recentNonSponDonationAmt = intval($transaction['Total Charge']);
                        $recentNonSponDonationDate = $transaction['Donation Date'];
                        if(!empty($fund)){
                            $recentNonSponDonationFund = $fund;
                        }
                    }
                        

                    //total donation with completed transactions
                    $count++;

                    //number of unique funds
                    //summary of donations per fund
                    if(!empty($fund)){
                        $uniqueFundArray[$fund->LookupName] = $uniqueFundArray[$fund->LookupName] + 1;
                    }
                }


                //total lifetime non spon donations
                if(in_array($fund->LookupName, $nonSponFundArray)){
                    $totalNonSponAmt += $transaction['Total Charge'];
                }

                //First Non Spon Donations (can include gift)
                if(in_array($fund->LookupName, $nonSponFundArray) && $firstNonSponSet == false){
                    $firstNonSponAmt = $transaction['Total Charge'];
                    $firstNonSponDate = $transaction['Donation Date'];
                    $firstNonSponSoft = ($transaction['Pledge Contact'] != $transaction['Donation Contact']) ? true : false;
                    $firstNonSponFund = $fund;
                    $firstNonSponSet = true;
                }

                //largest Non Spon Donations (can include gift)
                if( in_array($fund->LookupName, $nonSponFundArray) && intval($transaction['Total Charge']) >= $largestNonSponAmt){
                    $largestNonSponAmt = $transaction['Total Charge'];
                    $largestNonSponDate = $transaction['Donation Date'];
                    $largestNonSponSoft = ($transaction['Pledge Contact'] != $transaction['Donation Contact']) ? true : false;
                    $largestNonSponFund = $fund;
                }

                
            }

            $results = array();
            $results['contactId'] = $contactObj->ID;

            
            //number of total completed donations
            if($count != $contactObj->CustomFields->Metrics->totalCompletedDonations){
                $contactObj->CustomFields->Metrics->totalCompletedDonations = $count;
                $results['totalCompletedDonations'] = $count;
                $saveNeeded = true;
            }
            
            //number of total completed donations
            if($totalNumberDonationsLifetime != $contactObj->CustomFields->Metrics->totalNumberDonationsLifetime){
                $contactObj->CustomFields->Metrics->totalNumberDonationsLifetime = $totalNumberDonationsLifetime;
                $results['totalNumberDonationsLifetime'] = $totalNumberDonationsLifetime;
                $saveNeeded = true;
            }
            

            //number of unique funds for all donations
            arsort($uniqueFundArray);
            //$contactObj->CustomFields->Metrics->numUniqueFunds = count($uniqueFundArray);
            if(count($uniqueFundArray) != $contactObj->CustomFields->Metrics->numUniqueFunds){
                $contactObj->CustomFields->Metrics->numUniqueFunds = count($uniqueFundArray);
                $results['numUniqueFunds'] = count($uniqueFundArray);
                $saveNeeded = true;
            }

            //string representing all unique funds
            //$contactObj->CustomFields->Metrics->donationsPerUniqueFund = substr(json_encode($uniqueFundArray), 0, 255);
            if(substr(json_encode($uniqueFundArray), 0, 255) != $contactObj->CustomFields->Metrics->donationsPerUniqueFund){
                $contactObj->CustomFields->Metrics->donationsPerUniqueFund = substr(json_encode($uniqueFundArray), 0, 255);
                $results['donationsPerUniqueFund'] = substr(json_encode($uniqueFundArray), 0, 255);
                $saveNeeded = true;
            }
            

            //start date of first recurring pledge
            list($startDate, $startDateSpon) = getStartDateOfFirstPledge($contactObj->ID);

            //Start Date of First Recurring Pledge all time
            //$results['startDateFirstRecurringPledge'] = (empty($contactObj->CustomFields->Metrics->startDateFirstRecurringPledge)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge);
            if($startDate  != $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge){
                $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge = $startDate;
                $results['startDateFirstRecurringPledge'] = date('Y/m/d', $contactObj->CustomFields->Metrics->startDateFirstRecurringPledge);
                $saveNeeded = true;
            }
                
            //Start Date of first sponsorship
            //$results['startDateFirstRecurringSponsorship'] = (empty($contactObj->CustomFields->Metrics->startDateFirstSponsorship)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->startDateFirstSponsorship);
            if($startDateSpon != $contactObj->CustomFields->Metrics->startDateFirstSponsorship){
                $contactObj->CustomFields->Metrics->startDateFirstSponsorship = $startDateSpon;
                $results['startDateFirstRecurringSponsorship'] = date('Y/m/d', $startDateSpon);
                $saveNeeded = true;
            }
                

            //First Donation Date All Time
            //$results['firstDonationDate'] = (empty($contactObj->CustomFields->Metrics->firstDonationDate)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->firstDonationDate);
            if($firstDonationDate != $contactObj->CustomFields->Metrics->firstDonationDate){
                $contactObj->CustomFields->Metrics->firstDonationDate = $firstDonationDate;
                $results['firstDonationDate'] = date('Y/m/d', $firstDonationDate);
                $saveNeeded = true;
            }

            //First Donation Amt All Time
            //$results['firstDonationAmt'] = $contactObj->CustomFields->Metrics->firstDonationAmt;
            if($firstDonationAmt != $contactObj->CustomFields->Metrics->firstDonationAmt){
                $contactObj->CustomFields->Metrics->firstDonationAmt = $firstDonationAmt;
                $results['firstDonationAmt'] = $firstDonationAmt;
                $saveNeeded = true;
            }

            //First Donation Fund All Time
            //$results['firstDonationFund'] = $contactObj->CustomFields->Metrics->firstDonationFund->LookupName;
            if($firstDonationFund->LookupName != $contactObj->CustomFields->Metrics->firstDonationFund->LookupName){
                $contactObj->CustomFields->Metrics->firstDonationFund = $firstDonationFund;
                $results['firstDonationFund'] = $firstDonationFund->LookupName;
                $saveNeeded = true;
            }

            //Most Recent Donation Date
            if($recentDonationDate != $contactObj->CustomFields->Metrics->recentDonationDate){
                $contactObj->CustomFields->Metrics->recentDonationDate = $recentDonationDate;
                $results['recentDonationDate'] = date('Y/m/d', $contactObj->CustomFields->Metrics->recentDonationDate);
                $saveNeeded = true;
            }

            //Most Recent Donation Amount
            if($recentDonationAmt != $contactObj->CustomFields->Metrics->recentDonationAmt){
                $contactObj->CustomFields->Metrics->recentDonationAmt = $recentDonationAmt;
                $results['recentDonationAmt'] = $recentDonationAmt;
                $saveNeeded = true;
            }

            //Most Recent Donation Fund
            if($recentDonationFund->LookupName != $contactObj->CustomFields->Metrics->recentDonationFund->LookupName){
                $contactObj->CustomFields->Metrics->recentDonationFund = $recentDonationFund;
                $results['recentDonationFund'] = $recentDonationFund->LookupName;
                $saveNeeded = true;
            }
            
            //Most Recent Non Spon Donation Date
            if($recentNonSponDonationDate != $contactObj->CustomFields->Metrics->recentNonSponDonationDate){
                $contactObj->CustomFields->Metrics->recentNonSponDonationDate = $recentNonSponDonationDate;
                $results['recentNonSponDonationDate'] = date('Y/m/d', $contactObj->CustomFields->Metrics->recentNonSponDonationDate);
                $saveNeeded = true;
            }

            //Most Recent Donation Amount
            if($recentNonSponDonationAmt != $contactObj->CustomFields->Metrics->recentNonSponDonationAmt){
                $contactObj->CustomFields->Metrics->recentNonSponDonationAmt = $recentNonSponDonationAmt;
                $results['recentNonSponDonationAmt'] = $recentNonSponDonationAmt;
                $saveNeeded = true;
            }

            //Most Recent Donation Fund
            if($recentNonSponDonationFund->LookupName != $contactObj->CustomFields->Metrics->recentNonSponDonationFund->LookupName){
                $contactObj->CustomFields->Metrics->recentNonSponDonationFund = $recentNonSponDonationFund;
                $results['recentNonSponDonationFund'] = $recentNonSponDonationFund->LookupName;
                $saveNeeded = true;
            }

            //Date of Largest Donation All Time
            //$results['largestDonationDate'] = (empty($contactObj->CustomFields->Metrics->largestDonationDate)) ? "" : date('Y/m/d', $contactObj->CustomFields->Metrics->largestDonationDate);
            if($largestDonationDate != $contactObj->CustomFields->Metrics->largestDonationDate){
                $contactObj->CustomFields->Metrics->largestDonationDate = $largestDonationDate;
                $results['largestDonationDate'] = date('Y/m/d', $largestDonationDate);
                $saveNeeded = true;
            }

            if($largestDonationDateWGifts != $contactObj->CustomFields->Metrics->largestDonationDateWGifts){
                $contactObj->CustomFields->Metrics->largestDonationDateWGifts = $largestDonationDateWGifts;
                $results['largestDonationDateWGifts'] = date('Y/m/d', $largestDonationDateWGifts);
                $saveNeeded = true;
            }

            //Amount of largest donation all time
            //$results['largestDonationAmt'] = $contactObj->CustomFields->Metrics->largestDonationAmt;
            if($largestDonationAmt != $contactObj->CustomFields->Metrics->largestDonationAmt){
                $contactObj->CustomFields->Metrics->largestDonationAmt = $largestDonationAmt;
                $results['largestDonationAmt'] = $largestDonationAmt;
                $saveNeeded = true;
            }

            if($largestDonationAmtWGifts != $contactObj->CustomFields->Metrics->largestDonationAmtWGifts){
                $contactObj->CustomFields->Metrics->largestDonationAmtWGifts = $largestDonationAmtWGifts;
                $results['largestDonationAmtWGifts'] = $largestDonationAmtWGifts;
                $saveNeeded = true;
            }

            //Fund of largest donation all time
            //$results['largestDonationFund'] = $contactObj->CustomFields->Metrics->largestDonationFund->LookupName;
            if($largestDonationFund->LookupName != $contactObj->CustomFields->Metrics->largestDonationFund->LookupName){
                $contactObj->CustomFields->Metrics->largestDonationFund = $largestDonationFund;
                $results['largestDonationFund'] = $largestDonationFund->LookupName;
                $saveNeeded = true;
            }

            //Date of largest donation last 2 years
            //$results['largestDonationDate2Yrs'] = (empty($contactObj->CustomFields->Metrics->largestDonationDate2Years)) ? "": date('Y/m/d', $contactObj->CustomFields->Metrics->largestDonationDate2Years);
            if($largestDonationDate2Years != $contactObj->CustomFields->Metrics->largestDonationDate2Years){
                $contactObj->CustomFields->Metrics->largestDonationDate2Years = $largestDonationDate2Years;
                $results['largestDonationDate2Yrs'] = date('Y/m/d', $largestDonationDate2Years);
                $saveNeeded = true;
            }

            //Amount of largest donation last 2 years
            //$results['largestDonationAmt2Yrs'] = $contactObj->CustomFields->Metrics->largestDonationAmt2Years;
            if($largestDonationAmt2Years != $contactObj->CustomFields->Metrics->largestDonationAmt2Years){
                $contactObj->CustomFields->Metrics->largestDonationAmt2Years = $largestDonationAmt2Years;
                $results['largestDonationAmt2Yrs'] = $largestDonationAmt2Years;
                $saveNeeded = true;
            }

            //Fund for largest donation last 2 years
            //$results['largestDonationFund2Yrs'] = $contactObj->CustomFields->Metrics->largestDonationFund2Years->LookupName;
            if($largestDonationFund2Years->LookupName != $contactObj->CustomFields->Metrics->largestDonationFund2Years->LookupName){
                $contactObj->CustomFields->Metrics->largestDonationFund2Years = $largestDonationFund2Years;
                $results['largestDonationFund2Yrs'] = $largestDonationFund2Years->LookupName;
                $saveNeeded = true;
            }

            //total donation $ for current year
            //$results['totalDonationAmtCurrentYear'] = (empty($contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear)) ? "" : $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear;
            if($totalDonationAmtCurrentYear != $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear){
                $contactObj->CustomFields->Metrics->totalDonationAmtCurrentYear = $totalDonationAmtCurrentYear;
                $results['totalDonationAmtCurrentYear'] = $totalDonationAmtCurrentYear;
                $saveNeeded = true;
            }

            //total donation $ for last year
            //$results['totalDonationAmtLastYear'] = (empty($contactObj->CustomFields->Metrics->totalDonationAmtLastYear)) ? "" : $contactObj->CustomFields->Metrics->totalDonationAmtLastYear;
            if($totalDonationAmtLastYear != $contactObj->CustomFields->Metrics->totalDonationAmtLastYear){
                $contactObj->CustomFields->Metrics->totalDonationAmtLastYear = $totalDonationAmtLastYear;
                $results['totalDonationAmtLastYear'] = $totalDonationAmtLastYear;
                $saveNeeded = true;
            }


            //Total Donations Lifetime
            $results['totalDonationsLifetime'] = $totalDonationsLifetime;
            if($contactObj->CustomFields->Metrics->totalDonationsLifetime != $totalDonationsLifetime){
                    $contactObj->CustomFields->Metrics->totalDonationsLifetime = intval($totalDonationsLifetime);
                    $saveNeeded = true;
                }

            //Total Soft Donation Lifetime
            $totalSoftDonationsLifetime = getSoftDonationsForContact($contactObj->ID);
            $results['totalSoftDonationsLifetime'] =  $totalSoftDonationsLifetime;
            //echo "Before Saving soft donations for ".$contactObj->ID.":".$totalSoftDonationsLifetime.":".$contactObj->CustomFields->Metrics->totalSoftDonationsLifetime;
            if($contactObj->CustomFields->Metrics->totalSoftDonationLifetime != $totalSoftDonationsLifetime){
                    //echo "saving soft donations:".$totalSoftDonationsLifetime;
                    $contactObj->CustomFields->Metrics->totalSoftDonationLifetime = intval($totalSoftDonationsLifetime);
                    $saveNeeded = true;
                }


            //Date of last sponsorship donation
            if($recentSponsorshipDonation != $contactObj->CustomFields->Metrics->recentSponsorshipDonation){
                $contactObj->CustomFields->Metrics->recentSponsorshipDonation = $recentSponsorshipDonation;
                $results['recentSponsorshipDonation'] = $recentSponsorshipDonation;
                $saveNeeded = true;
            }

            //Date of first nonspon
            if($firstNonSponDate != $contactObj->CustomFields->Metrics->firstNonSponDate){
                $contactObj->CustomFields->Metrics->firstNonSponDate = $firstNonSponDate;
                $results['firstNonSponDate'] = date('Y/m/d', $firstNonSponDate);
                $saveNeeded = true;
            }

            //Date of largest nonspon
            if($largestNonSponDate != $contactObj->CustomFields->Metrics->largestNonSponLifetimeDate){
                $contactObj->CustomFields->Metrics->largestNonSponLifetimeDate = $largestNonSponDate;
                $results['largestNonSponLifetimeDate'] = date('Y/m/d', $largestNonSponDate);
                $saveNeeded = true;
            }

            //Amt of largest nonspon
            if($largestNonSponAmt && $largestNonSponAmt != $contactObj->CustomFields->Metrics->largestNonSponLifetimeAmt){
                $contactObj->CustomFields->Metrics->largestNonSponLifetimeAmt = $largestNonSponAmt;
                $results['largestNonSponAmt'] = $largestNonSponAmt;
                $saveNeeded = true;
            }

            //Amt of first nonspon
            if($firstNonSponAmt != $contactObj->CustomFields->Metrics->firstNonSponAmt){
                $contactObj->CustomFields->Metrics->firstNonSponAmt = $firstNonSponAmt;
                $results['firstNonSponAmt'] = $firstNonSponAmt;
                $saveNeeded = true;
            }

            //largest non spon soft?
            if($largestNonSponSoft != $contactObj->CustomFields->Metrics->largestNonSponLifetimeSoft){
                $contactObj->CustomFields->Metrics->largestNonSponLifetimeSoft = $largestNonSponSoft;
                $results['largestNonSponSoft'] = ($largestNonSponSoft) ? 'true':'false';
                $saveNeeded = true;
            }

            //first non spon soft?
            if($firstNonSponSoft != $contactObj->CustomFields->Metrics->firstNonSponSoft){
                $contactObj->CustomFields->Metrics->firstNonSponSoft = $firstNonSponSoft;
                $results['largestNonSponSoft'] = ($largestNonSponSoft) ? 'true':'false';
                $saveNeeded = true;
            }
            //first non spon fund
            if($firstNonSponFund != $contactObj->CustomFields->Metrics->firstNonSponFund){
                $contactObj->CustomFields->Metrics->firstNonSponFund = $firstNonSponFund;
                $results['firstNonSponFund'] = $firstNonSponFund->LookupName;
                $saveNeeded = true;
            }

            //largest non spon fund
            if($largestNonSponFund != $contactObj->CustomFields->Metrics->largestNonSponFund){
                $contactObj->CustomFields->Metrics->largestNonSponFund = $largestNonSponFund;
                $results['largestNonSponFund'] = $largestNonSponFund->LookupName;
                $saveNeeded = true;
            }

            //Amt of total nonspon
            if($totalNonSponAmt != $contactObj->CustomFields->Metrics->totalNonSponDonationAmt){
                $contactObj->CustomFields->Metrics->totalNonSponDonationAmt = $totalNonSponAmt;
                $results['totalNonSponAmt'] = $totalNonSponAmt;
                $saveNeeded = true;
            }

            //update contact yearly metrics if necessary
            foreach($contactYearlyMetrics as $key => $val){
                $metricRecord = RNCPHP\Metrics\ContactsYearlyStats::first("Contact = ".$contactObj->ID." AND Year = '".$key."'");
                //if not exists, create
                //if exists and different value, update
                //if exists and not different, skip
                if(!$metricRecord){
                    $mr = new RNCPHP\Metrics\ContactsYearlyStats();
                    $mr->TotalDonations = intval($val);
                    $mr->Contact = $contactObj->ID;
                    $mr->Year = $key;
                    $mr->save();
                }elseif($metricRecord->TotalDonations != $val){
                    $metricRecord->TotalDonations = $val;
                    $metricRecord->save();
                }
            }

            if($saveNeeded){
                $contactObj->save();
            }
                
            $results['saveNeeded'] = ($saveNeeded) ? "True" : "False";
            $returnVal[] = $results;

            //print_r($contactYearlyMetrics);
            
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

function getSoftDonationsForContact($contactId){

        $donationContact_filter= new RNCPHP\AnalyticsReportSearchFilter;
        $donationContact_filter->Name = 'donationContactID';
        $donationContact_filter->Values = array( intval($contactId) );

        $pledgeContact_filter= new RNCPHP\AnalyticsReportSearchFilter;
        $pledgeContact_filter->Name = 'pledgeContactID';
        $pledgeContact_filter->Values = array( intval($contactId) );

        $filters = new RNCPHP\AnalyticsReportSearchFilterArray;
        $filters[] = $donationContact_filter;
        $filters[] = $pledgeContact_filter;

        $transactionList = array();
        $ar = RNCPHP\AnalyticsReport::fetch(101869);
        $transactionList = $ar->run(0, $filters);

        $totalSoftDonationsLifetime = 0;

        while($transaction = $transactionList->next()) {
            $totalSoftDonationsLifetime += $transaction['Total Charge'];
        }

        return $totalSoftDonationsLifetime;
}

function getYearOfDonation($transaction){
    $year = '';
    $dateValue = strtotime($transaction['Donation Date']);                  
    $year = date("Y", $dateValue); 
    return $year;
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

