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

CONST CANCELLED_STATUS = 41;
CONST RAISE_TO_78 = 1;
CONST RAISE_TO_936 = 75;//prod 75
CONST RAISE_TO_234 = 76;//prod 76
CONST LEAVE_39 = 2;
CONST ADVOCATE = 3;
CONST MOVE_TO_DLEAD = 70;
CONST CANCELLED_BY_DOUBLE_SPON = 62;
CONST ACTIVE = 1;
CONST NEW_MONTHLY_PLEDGE_AMT = "78.00";
CONST NEW_QUARTERLY_PLEDGE_AMT = "234.00";
CONST NEW_ANNUAL_PLEDGE_AMT = "936.00";
CONST NEW_APPEAL = 3526;
CONST ON_HOLD_PAY = 2;
CONST MANUAL_PAY = 43;
CONST DLEAD_FUND = 229;
CONST DLEAD_APPEAL = 3327;
CONST CANCELLED_BY_GRAD = 45;



$roql = "Select donation.pledge from donation.pledge where donation.pledge.AlterSponsorshipPledge is not NULL AND (donation.pledge.PledgeStatus = ".ACTIVE." OR donation.pledge.PledgeStatus = ".ON_HOLD_PAY." OR donation.pledge.pledgeStatus = ".MANUAL_PAY.")";
//echo $roql;

$pledgeObj = RNCPHP\ROQL::queryObject($roql)->next();
while($pledge = $pledgeObj->next()) {
    
    switch($pledge->AlterSponsorshipPledge->ID){
        case RAISE_TO_78:
            increase_pledge($pledge, NEW_MONTHLY_PLEDGE_AMT);
            break;
        case RAISE_TO_936:
            increase_pledge($pledge, NEW_ANNUAL_PLEDGE_AMT, getCreditAmount($pledge));
    
            break;
        case RAISE_TO_234:
            increase_pledge($pledge, NEW_QUARTERLY_PLEDGE_AMT, getCreditAmount($pledge));
            break;
        case LEAVE_39:  
            break;
        case ADVOCATE:
            break;
        case MOVE_TO_DLEAD:
            move_to_dlead($pledge);
            break;
            
    }

}

function move_to_dlead($pledgeObj){
    
    copy_pledge($pledgeObj, true, $pledgeObj->PledgeAmount);
    //cancel old pledge
    cancel_old_pledge($pledgeObj, true);
    
    return true;
}

function increase_pledge($pledgeObj, $pledgeAmount, $firstDonationCredit = "0.00"){

    //make a copy of old pledge with some changes
    copy_pledge($pledgeObj, false, $pledgeAmount, $firstDonationCredit);
    //cancel old pledge
    cancel_old_pledge($pledgeObj);
    
    return true;
}

function copy_pledge($oldPledge, $move_to_dlead = false, $newpledgeAmount, $firstDonationCredit = "0.00"){
    
    try{
        $newPledge = new RNCPHP\donation\pledge();
    
        $newPledge->PledgeAmount = $newpledgeAmount;
        
        //set transaction date as first day of next month
        $pledgeAheadBehind = getNextTransactionDate($oldPledge, $firstDonationCredit);
        $newPledge->NextTransaction = ($move_to_dlead) ? $oldPledge->NextTransaction: $pledgeAheadBehind["nextTransDate"];
        $newPledge->Balance = ($move_to_dlead) ? $oldPledge->Balance : $pledgeAheadBehind["balance"];
        //$newPledge->Balance += $firstDonationCredit;
        $newPledge->StartDate = strtotime('first day of next month');
        $newPledge->firstTimeDonationCredit = $firstDonationCredit;
        
        $newPledge->Acknowledgement = $oldPledge->Acknowledgement;
        $newPledge->AmountDescription = $oldPledge->AmountDescription;
        $appealId = ($move_to_dlead) ? DLEAD_APPEAL : NEW_APPEAL;
        $newPledge->Appeals = RNCPHP\donation\Appeal::fetch( $appealId );


        $newPledge->Child = $oldPledge->Child;
        $newPledge->ChildSponsorship = $oldPledge->ChildSponsorship;
        $newPledge->Closed = $oldPledge->Closed;
        $newPledge->Contact = $oldPledge->Contact;
        $newPledge->CorrespondenceContact = $oldPledge->CorrespondenceContact;
        $newPledge->Descr = $oldPledge->Descr; 
        $newPledge->EventTable = $oldPledge->EventTable;
        $newPledge->EventTicket = $oldPledge->EventTicket;
        $newPledge->Frequency = $oldPledge->Frequency;
        $newPledge->FromWhom = $oldPledge->FromWhom;
        
        //put in a new fund (fundName2) if it exists, if not, go with old fund and let them change it.
        $newfund = ($move_to_dlead) ? RNCPHP\donation\Fund::fetch( DLEAD_FUND) : getNewFund($oldPledge->Fund->AccountingCode);
        $newPledge->Fund = ($newfund)? $newfund : $oldPledge->Fund;
        //---
        
        $newPledge->Household = $oldPledge->Household;
        $newPledge->LegacyChildID = $oldPledge->LegacyChildID;
        $newPledge->LegacyContactID = $oldPledge->LegacyContactID;
        $newPledge->LegacyPledgeID = $oldPledge->LegacyPledgeID;
        $newPledge->Months = $oldPledge->Months;
        $newPledge->MotivationCode = $oldPledge->MotivationCode;
        $newPledge->PTD = $oldPledge->PTD;

        $newPledge->PledgeNotes = $oldPledge->PledgeNotes;
        $newPledge->PledgeStatus = $oldPledge->PledgeStatus;
        $newPledge->Receipts = $oldPledge->Receipts;
        $newPledge->SendAcknowledgement = $oldPledge->SendAcknowledgement;
        $newPledge->SendReceipt = $oldPledge->SendReceipt;
        $newPledge->SentSponsorEmail = $oldPledge->SentSponsorEmail;
        $newPledge->Sponsorship = $oldPledge->Sponsorship;
        
        $newPledge->StatementOptions = $oldPledge->StatementOptions;
        $newPledge->Type1 = $oldPledge->Type1;
        $newPledge->paymentMethod2 = $oldPledge->paymentMethod2;
        $newPledge->copiedFromPledge = $oldPledge;
        
        echo "New Pledge created from ".$newPledge->copiedFromPledge->ID.
        " Pledge Amount:".$newPledge->PledgeAmount.
        " NextTransactionDate:".date("Y-m-d",$newPledge->NextTransaction).
        " Balance:".$newPledge->Balance.
        " First Time Donation Credit:".$newPledge->firstTimeDonationCredit.
        " StartDate:".date("Y-m-d",$newPledge->StartDate).
        " NewAppeal:".$newPledge->Appeals->LookupName." (ID ".$newPledge->Appeals->ID.")".
        " NewFund:".$newPledge->Fund->LookupName."<br/>\n";
        
        $newPledge->save(RNCPHP\RNObject::SuppressAll);
        echo "<br/><br/>new pledge created".$newPledge->ID;
    }catch(Exception $e){
       echo($e->getMessage());
    }catch(RNCPHP\ConnectAPIError $err){
       echo($err->getMessage());
    }
    
}

function cancel_old_pledge($pledgeObj, $cancelled_by_grad = false){
    $pledgeObj->PledgeStatus = ($cancelled_by_grad) ? CANCELLED_BY_GRAD : CANCELLED_BY_DOUBLE_SPON;
    $pledgeObj->StopDate = time();
    $pledgeObj->save(RNCPHP\RNObject::SuppressAll);
}

function leave_pledge($pledgeObj){
    
    return true;
}

function advocate_pledge($pledgeObj){
    
    return true;
}

function getNewFund($fundRef){
    $fundRef = $fundRef.'2';
    $roql = "SELECT donation.fund FROM donation.fund WHERE donation.fund.AccountingCode = '$fundRef'";

    $fundObj = RNCPHP\ROQL::queryObject($roql)->next();
    while($fund = $fundObj->next()) {
        return $fund;
    }
    
    return null;
    
}

function getNextTransactionDate($oldPledge, $firstMonthCredit){
    //$pledge->AheadBehind stores the pledge balance
    //$pledge->Balance hold extra money they have donated, this will be subtracted from their first payment
    $remainder = "0.00";
    
    if($oldPledge->Frequency->LookupName == "Annually"){
        $divisor = NEW_ANNUAL_PLEDGE_AMT;
    }else if($oldPledge->Frequency->LookupName == "Quarterly"){
        $divisor = NEW_QUARTERLY_PLEDGE_AMT;
    }else if($oldPledge->Frequency->LookupName == "Monthly"){
        $divisor = NEW_MONTHLY_PLEDGE_AMT;
    }
    
    
    if($oldPledge->AheadBehind > 0){
        $numIncrementsMove = floor($oldPledge->AheadBehind / $divisor); 
        $remainder = $oldPledge->AheadBehind % $divisor;
    }else if($oldPledge->AheadBehind < 0){
        $numIncrementsMove = ceil($oldPledge->AheadBehind / $divisor);
        //$remainder = $oldPledge->AheadBehind % NEW_MONTHLY_PLEDGE_AMT;  //we don't carry forward a negative balance.  this will scxri
    }else{
        $numIncrementsMove = 0;
    }

    //6/26/22 ZC: Change will take balance on quarterly/annual pledges and move ahead 1 month per 78 of balance. Balances < 78 will move the next trans date
    //up 1 month and set to 0.  i.e. if there is $1 of balance, set to 0 and move up 1 month.
    if( ($remainder > 0 || $firstMonthCredit > 0)&& 
        ($oldPledge->Frequency->LookupName == "Annually" || $oldPledge->Frequency->LookupName == "Quarterly")){
            $monthsToMoveUp = ceil($remainder + $firstMonthCredit / 78);
            $numIncrementsMove += $monthsToMoveUp;
            $remainder = 0;
    }

    $dayOftheMonth =  date("d", $oldPledge->NextTransaction);
    $curMonth = date('n');
    $curYear  = date('Y');
    
    $nextTransDate = ($curMonth == 12) ? mktime(0, 0, 0, 0, $dayOftheMonth, $curYear+1) : mktime(0, 0, 0, $curMonth+1, $dayOftheMonth);
    $modifiedTransDate = strtotime("+$numIncrementsMove months",$nextTransDate);
    //echo "<br/>Standard Next Transaction Date = ".date("Y-m-d", $nextTransDate)." modified to ".date("Y-m-d", $modifiedTransDate)." with a remainder of $remainder";
    return  array("nextTransDate"=>$modifiedTransDate, "balance"=>$remainder);
    
}

//for non monthly pledges, there can be some internal transactions that have already been created but not yet sent to rwanda.  we need to credit
//the new pledge's balance with these amounts.
function getCreditAmount($oldPledge){
    
    $additionalBalanceAmount = 0;
    
    
    if($oldPledge->ID){
        $roql = "SELECT donation.donationToPledge FROM donation.donationToPledge WHERE donation.donationToPledge.PledgeRef = $oldPledge->ID ORDER BY donation.donationToPledge.DonationRef DESC Limit 1";

        $d2pObj = RNCPHP\ROQL::queryObject($roql)->next();
        while($d2pL = $d2pObj->next()) {
            //echo "d2p ID:".$d2pL->DonationRef->ID."<br/><br/>";
            $d2p = $d2pL;
            
        }
    }
        
    
    
    if($d2p->DonationRef->ID)
        $trans = RNCPHP\financial\transactions::first("donation = ".$d2p->DonationRef->ID);
    
    if($trans->ID)
        $intTrans = RNCPHP\financial\internalTransaction::find("transactionRef = ".$trans->ID);

    $firstDayOfMonth = intval(date(U, strtotime("First Day of this month")));
   
    $creditAmount = 0;
    foreach($intTrans as $intTran){
        $transferDate = intval($intTran->transferDate);

        if($firstDayOfMonth <= $transferDate){
            $creditAmount += $intTran->amount;
        }

    }

    return number_format($creditAmount, 2, '.', '');
}


?>