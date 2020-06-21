<?
/***********************************************
 *
 *
 *
 *
 *
 *
 *
 **************************************************/

//require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;

class handleInternalTransactions {

    private $allocateUnallocated = true; //auto allocate unallocated funds
    private $extraMonthsMovedOnDonation = 0;

    private $debug = true;
    private $adminFundCodeId = 235;
    private $unallocatedFundId = 262;
    private $giftFundId = 245;
    private $completedName = "Completed";
    private $refundedName = "Refunded";
    private $reversedName = "Reversed";
    private $oneTimePledgeType = "One-Time";
    private $recurringPledgeType = "Recurring";
    private $remainingFundsAvailable = 0;

    private $sponsorshipMailingID = 4;
    private $receiptMailingID = 5;

    private $monthlyFreqId = 5;
    private $quarterlyFreqId = 7;
    private $annualFreqId = 1;
    private $oneTimeFreqId = 9;
    private $fundAllocation = array();
    private $initialAdminTransactionCreated = array();

    private $updatedNote = array();

    private $logClass = null;
    private $totalItemCount = 0;
    private $displayDateFormat = 'F d, Y';

    private $activePledgeStatus = 1;
    private $endUserPaymentSource = 2;
    private $manualPayPledgeStatus = 43;
    private $cancelledByDoubleSpon = 62;

    /**
     * CPM Entry Point
     *
     * Determines if the transactions status requires further processing
     * Calls appropriate methods for that processing
     */
    public function handleInternalTransactions($run_mode, $action, $obj, $n_cycles) {

        $logger = esgLogger::getInstance();
        if ($this -> debug) {
            $logger -> enable($this -> debug);
            //need to be sure to persist this line of code for logging to work
            $fileLogger = new fileLogger(logWorker::Debug);
            $logger -> registerLogWorker($fileLogger);
            // $stdoutLogger = new stdoutLogger(logWorker::Debug);
            //$logger -> registerLogWorker($stdoutLogger);

        }
        $this -> logClass = $logger;
        $this -> handleLogging("***************************Logging Initialized***************************", logWorker::Debug);
        $this -> handleLogging("Transaction ID:" . $obj -> ID, logWorker::Notice);
        if ($n_cycles > 4) {
            $this -> handleLogging("Reentrant CPM, with $n_cycles cycles");
            return $this -> cleanup($obj);
        }
        // do nothing if no change in status

        $this->logClass->addLog($obj -> prev -> currentStatus -> ID);
        $prevStatus = (isset($obj -> prev)) ? $obj -> prev -> currentStatus -> ID : -1;
        if ($obj -> currentStatus -> ID == $prevStatus) {
            $this -> handleLogging("No Status change (" . $obj -> currentStatus -> Name . ") checking Allocation Completed which = " . $obj -> allocationCompleted, logWorker::Notice);
            if ($obj -> allocationCompleted == 1) {
                $this -> handleLogging("No status change, exiting");
                return $this -> cleanup($obj);
            }
        }

        $this -> handleLogging("Status changed to " . $obj -> currentStatus -> Name, logWorker::Notice);

        if ($obj -> currentStatus -> Name == $this -> completedName) {
            $this -> handleLogging("Calling completed");
            $this -> handleCompletedTransaction($obj);
        }
        if ($obj -> currentStatus -> Name == $this -> refundedName) {
            $this -> handleLogging("Calling refunded");
            //to test returns, first must call completed trans.  Comment for production.
            //  $this->handleCompletedTransaction($obj);
            $this -> handleRefundedTransaction($obj);
        }


        if ($obj -> currentStatus -> Name == $this -> reversedName) {
            $this -> handleLogging("Calling refunded");
            //to test returns, first must call completed trans.  Comment for production.
            //  $this->handleCompletedTransaction($obj);
            $this -> handleRefundedTransaction($obj);
        }

        return $this -> cleanup($obj);

    }

    /**
     *
     * Final method called before the class exits.
     * Writes notes to the transaction object.
     *
     */
    private function cleanup($obj) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        if (count($this -> updatedNote) > 0) {
            $f_count = count($obj -> Notes);
            if ($f_count == 0) {
                $obj -> Notes = new RNCPHP\NoteArray();
            }
            $obj -> Notes[$f_count] = new RNCPHP\Note();
            $obj -> Notes[$f_count] -> Channel = new RNCPHP\NamedIDLabel();
            $obj -> Notes[$f_count] -> Channel -> LookupName = "Fax";
            $obj -> Notes[$f_count] -> Text = implode("\n", $this -> updatedNote);
            $obj -> save(RNCPHP\RNObject::SuppressAll);
            //            RNCPHP\ConnectAPI::commit();
        }
    }

    private function handleLogging($logText, $severity = null, $object = null) {

        $this -> logClass -> addLog($logText, $severity, $object);
        if (($severity != null && $severity < logWorker::Debug) || $this -> debug) {
            $this -> updatedNote[] = $logText;
        }
    }

    //seeing some failure where the $obj->donation->ID is not always available at
    //the time this runs.  So, instead of relying on passed in obj, well fetch the DB object and use that
    private function queryTransaction($t_id){
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        if (!is_null($t_id) && is_numeric($t_id) && $t_id > 0){

            $roql = sprintf("SELECT financial.transactions FROM financial.transactions where ID = %s", $t_id);
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": " . $roql);
            $qo = RNCPHP\ROQL::queryObject($roql) -> next();
            $transObj = $qo -> next();
//
            if (!is_null($transObj->ID) && is_numeric($transObj->ID) && $transObj->ID > 0){
                return $transObj;
            }else{
                $this -> handleLogging("Something when wrong fetching transaction");
                return false;
            }
        }else{
            $this -> handleLogging("Bad Trans ID sent in. t_id = ".$t_id);
        }

        return;
    }

    /**
     *
     * Handles creating internal transaction that are reversed of existing.
     * Examines internal transactions that currently exist on the donation and
     * creates new internal transactions that are negations of the existing.
     *
     */
    private function handleRefundedTransaction($trans) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        $amountRemainingOnDonation = $trans -> totalCharge;
        $internalTransactions = $this -> getInternalTransactions($trans -> ID);

        if (count($internalTransactions) < 1) {
            return;
        }
        $pledges = $this -> getPledges($trans -> donation -> ID, $trans->ID);

        //array to count how many months in total to decrement each pledge
        $pledgesDecrementMonths = array();
        foreach ($pledges as $index => $pledge) {
            $pledgesDecrementMonths[$pledge -> ID] = array(
                'pledge' => $pledge,
                'months' => 0,
                'balanceChange' => 0
            );
        }

        foreach ($internalTransactions as $index => $currentIntTrans) {
            $applyDate = time();
            if ($currentIntTrans -> transferDate > time()) {
                $applyDate = $currentIntTrans -> transferDate;
            }
            if (isset($currentIntTrans -> pledge)) {
                $pledgesDecrementMonths[$currentIntTrans -> pledge -> ID]['months'] += $currentIntTrans -> pledgePayedMonths;
                if($currentIntTrans -> appliedToPledgeBalance == true){
                    $amountToDeduct = ($currentIntTrans -> amount > 0) ? $currentIntTrans -> amount : $currentIntTrans -> pledge -> PledgeAmount * -1;
                    $pledgesDecrementMonths[$currentIntTrans -> pledge -> ID]['balanceChange'] -= $amountToDeduct * -1;
                }
            }

$this -> handleLogging("Balance change:".$pledgesDecrementMonths[$currentIntTrans -> pledge -> ID]['balanceChange']);
            //private function createInternalTrans($fundId, $pledgeId = 0, $transactionId = 0, $amount = 0, $date, $donationItemId, $splitFunds = false) {
            $this -> createInternalTrans($currentIntTrans -> Fund -> ID, $currentIntTrans -> pledge -> ID, $trans -> ID, $currentIntTrans -> amount * -1, $applyDate, $currentIntTrans -> donationItemId, false, $currentIntTrans -> pledgePayedMonths * -1);
        }

        $this -> handleLogging("Decrementing Pledges: ");
        foreach ($pledgesDecrementMonths as $id => $pledgeArr) {
            if ($pledgeArr['months'] > 0) {
                $newNextTrans = $this -> strToTimeFixedEndOfMonth("-{$pledgeArr['months']} months", $pledgeArr['pledge'] -> NextTransaction);
            }
            //Update the pledge balance
$this -> handleLogging("Balance Calculation:".$pledgeArr['pledge'] -> Balance - $pledgeArr['balanceChange']);
$this -> handleLogging("Negative balance calculation:".$pledgeArr['pledge'] -> Balance - $pledgeArr['balanceChange'] + $pledgeArr['pledge']->PledgeAmount);
            $newPledgeBalance = ($pledgeArr['pledge'] -> Balance - $pledgeArr['balanceChange'] >= 0) ? $pledgeArr['pledge'] -> Balance - $pledgeArr['balanceChange'] : $pledgeArr['pledge'] -> Balance - $pledgeArr['balanceChange'] + $pledgeArr['pledge']->PledgeAmount;
            $this -> handleLogging("On Refund Previous:".$pledgeArr['pledge'] -> Balance."  New Balance: ".$newPledgeBalance);
            $this -> updatePledgeNextTransDate($pledgeArr['pledge'], $newNextTrans, $newPledgeBalance);
        }

         //instead save a mailing helper object which will fire an asynch CPM for mailings
        try {
            $mailinghelper = new RNCPHP\helpers\mailinghelper;
            $mailinghelper -> transaction = $trans;
            $mailinghelper -> save();
        } catch (Exception $ex) {
            $this -> handleLogging("Exception on " . __LINE__ . ": " . $ex -> getMessage(), logWorker::Notice);
        } catch(RNCPHP\ConnectAPIError $e) {
                $this -> handleLogging("RNCPHP Exception on " . __LINE__ . ": " . $e -> getMessage(), logWorker::Notice);
        }
    }

    /**
     *
     *
     * Handles creating internal transaction for completed customer transactions
     * according to the financial overview document
     *
     */
    private function handleCompletedTransaction($trans) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__, logWorker::Debug);

        $amountRemainingOnDonation = $trans -> totalCharge;

        //call method for each status
        $pledges = $this -> getPledges($trans -> donation -> ID, $trans->ID);
        $gifts = $this -> getGifts($trans -> donation -> ID);

        $this -> totalItemCount = count($gifts) + count($pledges);
        $this -> handleLogging("Total items in donation: " . $this -> totalItemCount);

        $oneTimePledges = array();
        $recurringPledges = array();
        $latePledges = array();
        $pledgesBroughtCurrent = array();

        //put pledges into buckets: late current, recurring, one time, etc.
        $this -> handleLogging("Parsing Pledges into categories");
        foreach ($pledges as $pledge) {
            $this -> handleLogging("In pledge id " . $pledge -> ID);
            if ($pledge -> Frequency -> ID == $this -> oneTimeFreqId || $pledge -> Frequency -> ID == null) {
                $oneTimePledges[] = $pledge;
            } else {
                if ($pledge -> NextTransaction < time() - 172800) {
                    $this -> handleLogging("Found late pledge with transaction date: " . date($this -> displayDateFormat, $pledge -> NextTransaction), logWorker::Notice);
                    $latePledges[] = $pledge;
                } else {
                    $recurringPledges[] = $pledge;
                }
            }
        }

        //one-time pledges
        $this -> handleLogging("Handling " . count($oneTimePledges) . " one time pledges", logWorker::Notice);
        foreach ($oneTimePledges as $oneTimePledge) {
            if ($oneTimePledge -> PledgeAmount <= $amountRemainingOnDonation) {
                $this -> handleLogging("Allocating one time pledge: " . $oneTimePledge -> ID . " with amount: $" . $oneTimePledge -> PledgeAmount, logWorker::Notice);
                $amountRemainingOnDonation -= $this -> handlePledge($oneTimePledge, $trans, true, $amountRemainingOnDonation);
            }
        }

        //late pledges
        $this -> handleLogging("Handling " . count($latePledges) . " late recurring pledges", logWorker::Notice);
        foreach ($latePledges as $latePledge) {
            $amountChargedFromDonation = $this -> bringPledgeCurrent($latePledge, $trans, $amountRemainingOnDonation);
            //keep track of pledges brought current, we'll try to charge them up next
            if ($amountChargedFromDonation !== false) {
                $pledgesBroughtCurrent[] = $latePledge;
            } else {
                $amountChargedFromDonation = 0;
            }
            $amountRemainingOnDonation -= $amountChargedFromDonation;
        }

        //current, recurring pledges
        $this -> handleLogging("Handling " . count($recurringPledges) . " recurring pledges", logWorker::Notice);
        foreach ($recurringPledges as $recurringPledge) {
            $amountRemainingOnDonation -= $this -> handlePledge($recurringPledge, $trans, false, $amountRemainingOnDonation);
        }

        //recurring pledges, that were late
        $this -> handleLogging("Handling " . count($pledgesBroughtCurrent) . " pledges that were brought current, for additional payment", logWorker::Notice);
        foreach ($pledgesBroughtCurrent as $pledge) {
            $this -> handleLogging("Allocating recurring pledge: " . $pledge -> ID, logWorker::Notice);
            $amountRemainingOnDonation -= $this -> handlePledge($pledge, $trans, false, $amountRemainingOnDonation, true);
        }

        //gifts
        $this -> handleLogging("Handling " . count($gifts) . " gifts", logWorker::Notice);
        foreach ($gifts as $gift) {
            $amountToAllocate = $gift -> Item -> Amount * $gift -> Quantity;
            //$gift -> Quantity * $gift -> Item -> Amount;
            if ($amountToAllocate <= $amountRemainingOnDonation) {
                $this -> handleLogging("Found gift with price: {$gift -> Item -> Amount } and quantity: {$gift->Quantity}, allocating funds.", logWorker::Debug);
                $donationDate = (is_null($trans -> donation -> DonationDate)) ? time() : $trans -> donation -> DonationDate;
                $this -> createInternalTrans($this -> giftFundId, null, $trans -> ID, $amountToAllocate, $donationDate, $gift -> ID, true);
                $amountRemainingOnDonation -= $amountToAllocate;
            }
        }

        //unallocated funds //if pledge is moved ahead by an extra month due to overpay + balance, we need a $0 internal trans with +1 month to handle refunds
        if ($amountRemainingOnDonation > 0 || $this->extraMonthsMovedOnDonation > 0) {
            $this -> handleLogging("Amount Remaining on Donation: $amountRemainingOnDonation. creating Excess contribution ");
            $donationDate = (is_null($trans -> donation -> DonationDate)) ? time() : $trans -> donation -> DonationDate;
            $splitFunds = ($amountRemainingOnDonation == 0) ? false:true; //only want 1 internal trans for $0 if no excess but 1 or more extramonthsmoved
            if($this -> allocateUnallocated){
                //zc 9/4/18: Evenly distribute unallocated and apply that amount to that pledges balance.
                $numPledges = count($pledges);
                foreach($pledges as $pledge){
                    
                    if($this->isCoSponFund($pledge->Fund)){
                        //if its the first donation split it with admin, if not just do for the full amount    
                        //if donation amount is from 0 - .5(pledge amount) all of it goes to dvpt
                        //if donation amount is from .5(pledge amount ) or above .5 goes to dvpt and the rest is split
                        
                        if(($amountRemainingOnDonation / $numPledges) <= ($pledge->PledgeAmount / 2) ){
                           $amtToSendToAdmin = ($amountRemainingOnDonation / $numPledges);
                           $amtToSendNormalFunds = 0;
                        }else{
                           $amtToSendToAdmin = ($pledge->PledgeAmount / 2); 
                           $amtToSendNormalFunds = ($amountRemainingOnDonation / $numPledges) - $amtToSendToAdmin;
                        }
           
                        $this -> handleLogging("Sending Admin : $amtToSendToAdmin. Sending Normal Fund Split :  $amtToSendNormalFunds");
                        
                        if($this -> createInitialAdminInternalTrans($pledge, $trans, $amtToSendToAdmin)){
                            $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $trans -> ID, $amtToSendNormalFunds, $donationDate, null ,$splitFunds, $this->extraMonthsMovedOnDonation, true);  //$this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt, $this -> strToTimeFixedEndOfMonth("+$createdTrans Month", $pledge -> NextTransaction), null, true, 1);
                        }else{
                            $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $trans -> ID, $amountRemainingOnDonation / $numPledges, $donationDate, null ,$splitFunds, $this->extraMonthsMovedOnDonation, true);  //$this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt, $this -> strToTimeFixedEndOfMonth("+$createdTrans Month", $pledge -> NextTransaction), null, true, 1);
                        }
                        
                    }else{
                        $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $trans -> ID, $amountRemainingOnDonation / $numPledges, $donationDate, null ,$splitFunds, $this->extraMonthsMovedOnDonation, true);  //$this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt, $this -> strToTimeFixedEndOfMonth("+$createdTrans Month", $pledge -> NextTransaction), null, true, 1);
                    }
                    
                        
                    //Calling to change the pledge balance, were keeping the pledge next trans the same so send in null
                    //$this->updatePledgeNextTransDate($pledge, null, $amountRemainingOnDonation / $numPledges);
                }

            }else{
                $this -> createInternalTrans($this -> unallocatedFundId, null, $trans -> ID, $amountRemainingOnDonation, $donationDate);
            }

        }

        $this -> handleLogging("trans id = " . $trans -> ID, logWorker::Debug);

        ///////do this to handle CPM failure.  ///////
        $trans -> allocationCompleted = 1;
        $trans -> save(RNCPHP\RNObject::SuppressAll);
        RNCPHP\ConnectAPI::commit();
        ////////////////


        try {
            //instead save a mailing helper object which will fire an CPM for mailings
            $mailinghelper = new RNCPHP\helpers\mailinghelper;
            $mailinghelper -> transaction = $trans;
            $mailinghelper -> save();
        } catch (Exception $ex) {
            $this -> handleLogging("Exception on " . __LINE__ . ": " . $ex -> getMessage(), logWorker::Notice);
        }catch(RNCPHP\ConnectAPIError $e) {
            $this -> handleLogging("RNCPHP Exception on " . __LINE__ . ": " . $e -> getMessage(), logWorker::Notice);
        }

        return;
    }

    /**
     *
     * Main point for parsing donations into various internal transactions.
     *
     * @Return: the amount used from the donation to bring the pledge current (may be less than pledge amount due to pledge balance).
     *
     */
    private function handlePledge($pledge, $transaction, $oneTime = false, $amountAvailable, $bringingPledgeCurrent = false) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        if ($amountAvailable <= 0) {
            return 0;
        }

        $finalDonationAmountUsed = 0.00;
        $finalPledgeBalance = 0.00;
        $startingPledgeBalance = number_format($pledge -> Balance, 2, '.', '');
        $balanceUsed = 0.00;
        $firstDonationTowardPledge = false;

        //Figure out how much to move the next transaction date
        $numberTrans = $this -> getNumberTransPerDonation($pledge);
        $monthlyPledgeAmt = $pledge -> PledgeAmount / $numberTrans;
        $numberOfMonthsToMovePledgeAhead = floor(($startingPledgeBalance + $amountAvailable) / $monthlyPledgeAmt);

        //Figure out how many internal transactions to create //we don't want to consider starting balance.
        //if this is the only pledge on the donation, increase the paid through date to use all available funds.  This ignores the frequency of the pledge
        if ($this -> totalItemCount === 1) {
            $numberTrans = floor(($amountAvailable) / $monthlyPledgeAmt);
        }

        $totalDonationAmountToCreditToPledge = $monthlyPledgeAmt * $numberTrans;
$this -> handleLogging("Total Amount from Donation to Apply toward Pledge:$totalDonationAmountToCreditToPledge", logWorker::Notice);
        //final balance
        $finalPledgeBalance = $amountAvailable + $startingPledgeBalance - $monthlyPledgeAmt * $numberOfMonthsToMovePledgeAhead;
$this -> handleLogging("Final Pledge Balance:$finalPledgeBalance", logWorker::Notice);
        //figure out how much to charge from balance, and incoming funds
        // if ($startingPledgeBalance >= $totalCreditToPledge) {
            // $finalDonationAmountUsed = 0.00;
            // $balanceUsed = $totalCreditToPledge;
            // $finalPledgeBalance = $startingPledgeBalance - $balanceUsed;
        // } elseif (($startingPledgeBalance + $amountAvailable) >= $totalCreditToPledge) {
            // $finalPledgeBalance = 0.00;
            // $finalDonationAmountUsed = $totalCreditToPledge - $startingPledgeBalance;
            // $balanceUsed = $startingPledgeBalance;
        // } else {
            // $this -> handleLogging("Pledge: $pledge->ID amount of $totalCreditToPledge is greater than the combined donation ($amountAvailable) and pledge balance ($pledge->Balance) amounts.  Unable to increment pledge date; funds not allocated.", logWorker::Notice);
            // return 0;
        // }

        $this -> handleLogging("Increasing paid through date on pledge $numberOfMonthsToMovePledgeAhead months", logWorker::Notice);
        $this -> handleLogging("Pledge Frequency: {$pledge->Frequency->ID} ", logWorker::Notice);
        $this -> handleLogging("Pledge ID: {$pledge->ID} ", logWorker::Notice);

        $createdTrans = 0;

        if ($numberTrans > 0) {

            $amtToSendToAdmin = $this->isCoSponFund($pledge->Fund) ? ($pledge->PledgeAmount / $this -> getNumberTransPerDonation($pledge)) / 2 : $pledge->PledgeAmount / $this -> getNumberTransPerDonation($pledge);
            $this -> handleLogging(__LINE__." In a current pledge Sending Admin Amt:$amtToSendToAdmin", logWorker::Notice);
            if (!$oneTime &&
                    $this -> createInitialAdminInternalTrans($pledge, $transaction, $amtToSendToAdmin) &&
                        (!$this->isCoSponFund($pledge->Fund) || $bringingPledgeCurrent)) {
                    $createdTrans++;
            }

            $this -> handleLogging(__LINE__." Number Trans:$numberTrans  CreatedTrans:$createdTrans", logWorker::Notice);
            //create internal transactions to indicate date and location of fund transfers.
            for (; $createdTrans < $numberTrans; $createdTrans++) {
                $donationDate = (is_null($transaction -> donation -> DonationDate)) ? time() : $transaction -> donation -> DonationDate;
                //zc Jun 7 2018: if its the first donation on a COSPON pledge, we want to send in half to internal admin and half to noral fund allocation.
                //if we get here after a late pledge the Admin fund is already taken care of, so don't use the half monthly pledge amount.
                //zc Oct 11 2018, changed Pledge Payed Months to 0, initial admin charge has 1 so we don't want to set it back too far with a refund.
                if($createdTrans == 0 &&
                    $this->isCoSponFund($pledge->Fund) &&
                        $this -> getCountPledgeDonations($pledge -> ID) == 1 &&
                            !$bringingPledgeCurrent
                   ){
                       
                   
                   $this -> handleLogging("Creating Half Donation amount internal trans---");
                   $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt/2, $this -> strToTimeFixedEndOfMonth("+$createdTrans Month", $pledge -> NextTransaction), null, true, 0);
                }else
                    $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt, $this -> strToTimeFixedEndOfMonth("+$createdTrans Month", $pledge -> NextTransaction), null, true, 1);

            }
        }

        //number of months moved forward that come from extra funds(previously unallocated) and an existing balance.  need this so when a donation is refunded we can
        //set the next trans date  back to what it was.  Gonna set the pledgePayedMonths to that number
        //should never equal more than 1, but we'll see.
        $this->extraMonthsMovedOnDonation = $numberOfMonthsToMovePledgeAhead - $createdTrans;
$this -> handleLogging(__LINE__." Extra months on Next trans date due to over paid amount and previous balance: ".$this->extraMonthsMovedOnDonation, logWorker::Notice);

        //update the pledge
$this -> handleLogging(__LINE__." Number Trans:$numberTrans  CreatedTrans:$createdTrans", logWorker::Notice);
        $newNextTransDate = $this -> strToTimeFixedEndOfMonth("+$numberOfMonthsToMovePledgeAhead Month", $pledge -> NextTransaction);
        $this -> updatePledgeNextTransDate($pledge, $newNextTransDate, $finalPledgeBalance);

        return $totalDonationAmountToCreditToPledge;
    }

    /**
     *
     * Calculates amount of catchup donation, and number of months behind.  Creates a single catchup
     * internal transaction with the current date, updates the paid through date on the pledge.
     *
     * @Return: the amount used from the donation to bring the pledge current (may be less than pledge amount due to pledge balance).
     * or false if no funds were allocated
     */
    private function bringPledgeCurrent($pledge, $transaction, $amountAvailable) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        if($amountAvailable < $pledge->PledgeAmount){return "0.00";}
            
        $finalDonationAmountUsed = 0.00;
        $finalPledgeBalance = 0.00;
        $startingPledgeBalance = 0.00;
        $monthsBehind = 0;
        //$amountToCreditToPledge = 0.00;
        $balanceUsed = 0.00;
        //$donationUsed = 0.00;
        $amountForgiven = 0.00;
        $fromEUP = ($transaction -> donation -> PaymentSource -> ID == $this -> endUserPaymentSource) ? true : false;

        try {
            //figure out the current pledge balance
            if ($pledge -> Balance != null && number_format($pledge -> Balance, 2, '.', '') > 0) {
                $startingPledgeBalance = number_format($pledge -> Balance, 2, '.', '');
            } else {
                $startingPledgeBalance = 0;
            }
            //amount available is the transaction total
            $potentialCreditAmt = $amountAvailable + $startingPledgeBalance;

            //figure out how much is needed to bring current
            $catchupTargeDate = $this -> strToTimeFixedEndOfMonth("+" . $monthsBehind . " Month", $pledge -> NextTransaction);
            $monthsPerDonation = $this -> getNumberTransPerDonation($pledge);
            $monthlyCharge = $pledge -> PledgeAmount / $monthsPerDonation;
            $totalCatchup = 0;

            while ($catchupTargeDate < time() && ($fromEUP || (($totalCatchup + $monthlyCharge) <= $potentialCreditAmt))) {//if we're coming from the end-user pages, forgive all.  Otherwise only apply actual funds
                $totalCatchup += $monthlyCharge;
                $monthsBehind++;
                $catchupTargeDate = $this -> strToTimeFixedEndOfMonth("+" . $monthsBehind . " month", $pledge -> NextTransaction);
                $this -> handleLogging("Incrementing months behind: $monthsBehind.  new catchupTargetDate: " . date($this -> displayDateFormat, $catchupTargeDate) . ", new catchup amount: $" . number_format($totalCatchup, 2, '.', '') . ". current date: " . date($this -> displayDateFormat, time()));
            }

            //figure out how much to charge from balance, and incoming funds
            if ($startingPledgeBalance >= $totalCatchup) {//the pledge has a balance greater than the catchup amount
                $this -> handleLogging("IF #1 StartingPledgeBalance:$startingPledgeBalance totalCatchup:$totalCatchup", logWorker::Notice);
                $finalPledgeBalance = $startingPledgeBalance - $totalCatchup;
                $finalDonationAmountUsed = 0.00;
                $balanceUsed = $totalCatchup;

            } elseif ($potentialCreditAmt >= $totalCatchup) {//the donation and balance are less than the catchup amount
                $this -> handleLogging("IF #2 PotentialCreditAmt:$potentialCreditAmt totalCatchup:$totalCatchup", logWorker::Notice);

                //want to use as much donation as possible first // then go to balance
                if($totalCatchup <= $amountAvailable){
                    $finalDonationAmountUsed = $totalCatchup;
                    $balanceUsed = 0.00;
                }else if($totalCatchup > $amountAvailable){
                    $finalDonationAmountUsed = $amountAvailable;
                    $balanceUsed = $totalCatchup - $amountAvailable;
                }

                $finalPledgeBalance = $startingPledgeBalance - $balanceUsed;

            } else {//the catchup amount is greater than the balance and donation amount

                $finalPledgeBalance = 0.00;
                $finalDonationAmountUsed = $amountAvailable;
                $balanceUsed = $startingPledgeBalance;
                $amountForgiven = $totalCatchup - $potentialCreditAmt;

                if ($amountForgiven > 0 && $fromEUP) {
                    $transaction -> donation -> forgivenAmountTXT = number_format($amountForgiven, 2, '.', '');
                    $transaction -> donation -> save(RNCPHP\RNObject::SuppressAll);
                    $this -> handleLogging("Setting forgiven amount on donation to: " . number_format($amountForgiven, 2, '.', ''), logWorker::Notice);
                }
                $this -> handleLogging("IF #3 balanceUsed:$balanceUsed finaldonationamountused:$finalDonationAmountUsed", logWorker::Notice);
            }
            //$this->logClass->addLog($pledge);
            //zc 7/12/16 - if a pledge is "manual pay" it need to remain that.  Manual pays will never change status to on hold -non payment
            if ( ($fromEUP && $pledge -> PledgeStatus -> ID != $this -> activePledgeStatus) && $pledge -> PledgeStatus -> ID != $this -> manualPayPledgeStatus) {
                $pledge -> PledgeStatus = RNCPHP\donation\PledgeStatus::fetch($this -> activePledgeStatus);
                $pledge -> save(RNCPHP\RNObject::SuppressAll);
                $this -> handleLogging("Changing pledge status to 'Active'", logWorker::Notice);
            }

            $donationRemaining = $amountAvailable - $finalDonationAmountUsed;
            $this -> handleLogging("Pledge: {$pledge->ID} is $monthsBehind months behind.  New target date is: " . date($this -> displayDateFormat, $catchupTargeDate) . " with a total catchup amount of $totalCatchup", logWorker::Notice);
            $this -> handleLogging("Crediting pledge balance of \$$balanceUsed and donation amount of \$$finalDonationAmountUsed to pledge.  Donation amount remaining is: \$$donationRemaining. Pledge balance remaining is: \$$finalPledgeBalance. Bringing Pledge Current.", logWorker::Notice);
            $donationDate = (is_null($transaction -> donation -> DonationDate)) ? time() : $transaction -> donation -> DonationDate;

            if ($this -> createInitialAdminInternalTrans($pledge, $transaction, ($this->isCoSponFund($pledge->Fund) ? ($totalCatchup - $amountForgiven)/2:($totalCatchup - $amountForgiven) ))) {
                //if this is the first transaction on an auto created pledge created pledge with a "special", we don't need to take
                //the whole monthly charge, just half and let the other half pass through to do the fund splits
                //zc 3/9/18
                 //$finalDonationAmountUsed -= ($this->isCoSponFund($pledge->Fund)) ? $monthlyCharge/2 : $monthlyCharge;
                //$totalCatchup -= $monthlyCharge;
                
                $this -> handleLogging("After Initial Admin Trans final donation amount used:".$finalDonationAmountUsed, logWorker::Notice);
                //zc 3/11/19 : if its 1st cospon donation only half got internal transactions created on it 
                //if it wasn't all of it went to admin fund, so no int trans necessary
                if ($this->isCoSponFund($pledge->Fund)){
                    $amtToCharge = $finalDonationAmountUsed - ($monthlyCharge / 2); //
                    $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $amtToCharge, $donationDate, null, true, $monthsBehind);
                }
                    
               
            }else{
                $this -> createInternalTrans($pledge -> Fund -> ID, $pledge -> ID, $transaction -> ID, $finalDonationAmountUsed, $donationDate, null, true, $monthsBehind);
            }

                
            $this -> handleLogging("Updating Final Pledge Balance:".$finalPledgeBalance, logWorker::Notice);
            $this -> updatePledgeNextTransDate($pledge, $catchupTargeDate, $finalPledgeBalance);

        } catch(\Exception $ex) {
            $this -> handleLogging("Exception in bringPledgeCurrent on" . __LINE__ . ": " . $ex -> getMessage(), logWorker::Notice);
        }

        $this -> handleLogging("Returning Final Donation Amount used:".$finalDonationAmountUsed, logWorker::Notice);

        return $finalDonationAmountUsed;
    }

    /**
     * Determine if first payment to pledge, pledge is a sponsorship, and no other admin transactions this session, if so credit 100% to SPON
     *
     * @return boolean if internal transaction created
     */
    private function createInitialAdminInternalTrans($pledge, $transaction, $availableAmt = null) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        //if this is a dlead, don't want to charge an admin fee
        if($pledge->Fund->ID == 229){ return false; }


        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        //determine if first payment to pledge, and pledge is a sponsorship, and no other admin trans this session, if so credit 100% to SPON
        if ( !(empty($pledge -> Child) && empty($pledge -> Woman)) 
                && (!isset($this -> initialAdminTransactionCreated[$pledge -> ID])) 
                    && $this -> getCountPledgeDonations($pledge -> ID) == 1) {

            $monthlyPledgeAmt = $pledge -> PledgeAmount / $this -> getNumberTransPerDonation($pledge);
            $monthlyPledgeAmt = (!is_null($availableAmt) && $availableAmt < $monthlyPledgeAmt) ? $availableAmt : $monthlyPledgeAmt;
            //for pledges created from the co sponsorship build out (pledgeupdate.php) half of the total will go to spon, the rest of the money will
            //$monthlyPledgeAmt = ($this->isCoSponFund($pledge->Fund)) ? $monthlyPledgeAmt/2 : $monthlyPledgeAmt;
            $this -> handleLogging(__LINE__." Monthly Pledge Amount:".$monthlyPledgeAmt);

            $donationDate = (is_null($transaction -> donation -> DonationDate)) ? time() : $transaction -> donation -> DonationDate;
            $this -> initialAdminTransactionCreated[$pledge -> ID] = true;
            return $this -> createInternalTrans($this -> adminFundCodeId, $pledge -> ID, $transaction -> ID, $monthlyPledgeAmt, $donationDate, null, false, 1);
        } else {
            return false;
        }
    }

    /**
     *
     * Determines how funds should be allocated internally, based on values of the pledge fund and the
     * fund table.
     */
    private function getInternalAllocationFund($fundId) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        if (count($this -> fundAllocation) < 1) {
            try {
                $roql = sprintf("select donation.fund from donation.fund");
                $funds = RNCPHP\ROQL::queryObject($roql) -> next();
                //$this->logClass -> addLog("starting pledges");
                while ($fund = $funds -> next()) {
                    $this -> fundAllocation[$fund -> ID] = array(
                        'percent' => $fund -> internalAllocationPercent,
                        'fund' => $fund -> internalAllocationFund
                    );
                }
            } catch(Exception $e) {
                $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
                return null;
            }
            $this -> handleLogging("Fund allocation queried ");
        }
        if (isset($this -> fundAllocation[$fundId])) {
            return $this -> fundAllocation[$fundId];
        } else {
            return null;
        }
    }

    /**
     * Calculates the next transaction date.  If the date of next transaction falls on a non-existant day, returns the maximum date within that month
     * ex: request for Feb 30th returns Feb 29 or Feb 28
     */
    private function strToTimeFixedEndOfMonth($strToTimeOffset = "+0 months", $currentTransactionDate = null) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        if (is_null($currentTransactionDate)) {
            $currentTransactionDate = time();
        }

        $currentYear = date("Y", $currentTransactionDate);
        $currentMonth = date("n", $currentTransactionDate);
        $currentDay = date('j', $currentTransactionDate);
        $futureDay = $currentDay;
        $numDaysFutureMonth = date("t", strtotime($currentMonth . "/1/" . $currentYear . " " . $strToTimeOffset));
        $returnDate = strtotime($strToTimeOffset, $currentTransactionDate);

        if ($currentDay > $numDaysFutureMonth) {
            $futureDay = $numDaysFutureMonth;
            $returnDate = strtotime($currentMonth . "/" . $futureDay . "/" . $currentYear . " " . $strToTimeOffset);
            $this -> handleLogging("Future day of month is non-existent: $currentDay resetting to last day of month: " . date($this -> displayDateFormat, $returnDate), logWorker::Notice);
        }

        return $returnDate;
    }

    public function isCoSponFund($fund){
        $coSponFund = ( substr($fund->LookupName, -1) == "2") ? true: false;
        return $coSponFund;
    }

    /**
     *
     * Changes the pledge paid through date
     */
    private function updatePledgeNextTransDate($pledge, $nextTransaction, $newPledgeBalance = null) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        try {
            $oldPledgeBalance = number_format($pledge -> Balance, 2, '.', '');
            if ($newPledgeBalance !== null) {
                $pledge -> Balance = number_format($newPledgeBalance, 2, '.', '');
            } else {
                $newPledgeBalance = 0.00;
            }
            $pledge -> numProcessingAttempts=0;//if we have a good transaction numProcessingAttempts should be reset
            $this -> handleLogging("Updating pledge ({$pledge->ID}) paid through date from " . date($this -> displayDateFormat, $pledge -> NextTransaction) . " to " . date($this -> displayDateFormat, $nextTransaction) . ", and changing pledge balance from \$$oldPledgeBalance to \${$pledge -> Balance} ", logWorker::Notice);

            if($nextTransaction)
                $pledge -> NextTransaction = $nextTransaction;

            $pledge -> save(RNCPHP\RNObject::SuppressAll);
            //RNCPHP\ConnectAPI::commit();
        } catch(exception $e) {
            $this -> handleLogging("Exception on " . __LINE__ . ":: ", logWorker::Notice, $e -> getMessage());
            return false;
        }
    }

    /**
     *
     *Creates an internal transaction object.  If splitfunds is true, 2 internal transactions may be created: one with the passed fund id, one with the internal allocation fund id.
     *
     *
     * private function createInternalTrans($fundId, $pledgeId = 0, $transactionId = 0, $amount = 0, $date, $donationItemId, $splitFunds = false, $payedMonths = 0, $appliedToPledgeBalance = false)
     *
     */
    private function createInternalTrans($fundId, $pledgeId = 0, $transactionId = 0, $amount = 0, $date, $donationItemId, $splitFunds = false, $payedMonths = 0, $appliedToPledgeBalance = false) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        if (is_null($fundId)) {
            $fundId = $this -> unallocatedFundId;
            $this -> handleLogging("No fund code found, using default: " . $fundId, logWorker::Notice);
        }
        // if ($amount <= 0) {
            // $this -> handleLogging("$amount <= 0",logWorker::Notice);
            // return false;
        // }

        if ($splitFunds) {

            $allocationData = $this -> getInternalAllocationFund($fundId);
            if (isset($allocationData['percent']) && isset($allocationData['fund'])) {

                $diversionAmount = number_format($amount * ($allocationData['percent'] / 100), 2, '.', '');
                $amount = $amount * ((100 - $allocationData['percent']) / 100);

                $this -> handleLogging("Creating Internal Transaction - fund: {$allocationData['fund']->ID}, pledge ID: $pledgeId, transID: $transactionId, amount: \$$diversionAmount, transaction Date: " . date($this -> displayDateFormat, $date));

                $internalTrans = new RNCPHP\financial\internalTransaction;
                $internalTrans -> Fund = $allocationData['fund'] -> ID;
                $internalTrans -> transactionRef = $transactionId;
                $internalTrans -> pledge = $pledgeId;
                $internalTrans -> amount = $diversionAmount;
                $internalTrans -> transferDate = $date;
                $internalTrans -> donationItemId = $donationItemId;
                $internalTrans -> appliedToPledgeBalance = $appliedToPledgeBalance;
                try {
                    $result = $internalTrans -> save(RNCPHP\RNObject::SuppressAll);
                    $this -> handleLogging("Added internal transaction ({$internalTrans->ID}) of $" . number_format($diversionAmount, 2, '.', '') . " to fund " . $allocationData['fund'] -> ID . " for " . date($this -> displayDateFormat, $date), logWorker::Notice);
                } catch(exception $e) {
                    $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
                    return false;
                }

            }
        }

        $this -> handleLogging("creating Internal Transaction - fund: $fundId, pledge ID: $pledgeId, transID: $transactionId, amount: $" . number_format($amount, 2, '.', '') . ", transaction Date: " . date($this -> displayDateFormat, $date));
        $internalTrans = new RNCPHP\financial\internalTransaction;
        $internalTrans -> Fund = $fundId;
        $internalTrans -> transactionRef = $transactionId;
        $internalTrans -> pledge = $pledgeId;
        $internalTrans -> amount = number_format($amount, 2, '.', '');
        $internalTrans -> transferDate = $date;
        $internalTrans -> donationItemId = $donationItemId;
        $internalTrans -> pledgePayedMonths = $payedMonths;
        $internalTrans -> appliedToPledgeBalance = $appliedToPledgeBalance;
        try {
            $result = $internalTrans -> save(RNCPHP\RNObject::SuppressAll);
            // RNCPHP\ConnectAPI::commit();
            $this -> handleLogging("Added internal transaction ({$internalTrans->ID}) of $" . number_format($amount, 2, '.', '') . " to fund $fundId for " . date($this -> displayDateFormat, $date), logWorker::Notice);
            return true;
        } catch(exception $e) {
            $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
            return false;
        }
    }

    /**
     *
     * Returns the number of months each donation should cover, based on frequency
     *
     */
    private function getNumberTransPerDonation($pledge) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        switch ($pledge->Frequency->ID) {
            case $this->annualFreqId :
                return 12;
                break;
            case $this->quarterlyFreqId :
                return 3;
                break;
            case $this->oneTimeFreqId :
            case $this->monthlyFreqId :
                return 1;
                break;
            default :
                return 1;
                break;
        }
    }

    /**
     *
     * Determines how many donations a pledge has had, used to determine if inital credit should go to SPON
     */
    private function getCountPledgeDonations($pledgeId) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        try {
            $roql = sprintf(" SELECT donation.donationToPledge FROM donation.donationToPledge where donation.donationToPledge.PledgeRef.ID = '%s'", $pledgeId);
            $pages = RNCPHP\ROQL::queryObject($roql) -> next();
            //$this->logClass -> addLog("starting pledges ");
            return ($pages -> count());
        } catch(Exception $e) {
            $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
            return false;
        }
    }

    /**
     *
     *
     */
    private function getPledges($donationId = 0, $transId) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        try {
            if (is_null($donationId) || $donationId <= 0){
                $this -> handleLogging("Bad donation id, getting Trans ID=$transId from DB");
                $transObj = $this->queryTransaction($transId);
                $donationId = $transObj->donation->ID;
                $this -> handleLogging("New Donation ID=$donationId from Database");
            }

            $roql = sprintf("SELECT don.PledgeRef FROM donation.donationToPledge as don where donation.donationToPledge.DonationRef.ID = '%s'", $donationId);
            $this -> handleLogging("ROQL getPledges = " . $roql, logWorker::Debug);
            $pages = RNCPHP\ROQL::queryObject($roql) -> next();
            $this -> logClass -> addLog("starting pledges");
            $pledges = array();
            while ($pledge = $pages -> next()) {
                $this -> handleLogging("pledge found pledge id = " . $pledge -> ID);
                $pledges[] = $pledge;
            }
        } catch(Exception $e) {
            $this -> handleLogging("Exception on " . __LINE__ . $e -> getMessage(), logWorker::notice);
            return false;
        }

        return $pledges;
    }

    /**
     *
     * I want a pony
     *
     */
    private function getGifts($donationId = 0) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);
        try {
            $roql = sprintf("select donation.DonationItem from  donation.DonationItem where donation.DonationItem.DonationId = '%s'", $donationId);
            //$this->logClass -> addLog($roql );
            $giftReturn = RNCPHP\ROQL::queryObject($roql) -> next();
            $gifts = array();
            while ($gift = $giftReturn -> next()) {
                $gifts[] = $gift;
                //$this->logClass -> addLog("Found Gift: " . $gift -> ID . " ");
            }
        } catch(\Exception $e) {
            $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
            return false;
        }
        return $gifts;
    }

    /**
     *
     * Used to collect existing internal transactions, used on returns
     */
    private function getInternalTransactions($transactionId = 0) {
        $this -> handleLogging("Starting " . __FUNCTION__ . " at " . __LINE__);

        try {
            $roql = sprintf("select financial.internalTransaction from financial.internalTransaction where transactionRef =  '%s'", $transactionId);
            $this -> handleLogging($roql);

            $pages = RNCPHP\ROQL::queryObject($roql) -> next();
            $intTrans = array();
            while ($trans = $pages -> next()) {
                $intTrans[] = $trans;
            }
        } catch(\Exception $e) {
            $this -> handleLogging("Exception on " . __LINE__, logWorker::Notice, $e);
            return false;
        }
        return $intTrans;
    }



    /*
     * Function to calculate the donation.Doantion.forgivenAmount value for a late pledge donation
     */
    private function setDonationForgivenAmount($transaction, $finalDonationAmountUsed, $totalCatchup) {
        $amountForgiven = 0;
        if ($totalCatchup > $finalDonationAmountUsed) {
            $amountForgiven = $totalCatchup - $finalDonationAmountUsed;
        }
        $transaction -> donation -> forgivenAmountTXT = number_format($amountForgiven, 2, '.', '');
        $this -> handleLogging("Setting the amount forgiven of $amountForgiven for the transaction  Id of $transaction->ID  ", logWorker::Notice);
        return $transaction;
    }

}

/**
 * Class esgLogger
 * @author: Ben Hussey
 * @version: 1.0
 *
 * The esgLogger class provides the main entry point to loggging.  This class is a singleton that serves as an event bus, that marshals log messages to each
 * of the log worker classes that subscribe to the logging event.  The basic usage is straightforward:
 *
 *          $logger = esgLogger::getInstance();
 *          esgLogger::enable(true);
 *          $fileLogger = new fileLogger(fileLogger::Debug);  //file logger is a log worker
 *          $logger -> registerLogWorker($fileLogger);  //register the log worker with the esgLogger event bus singleton
 *          $logger -> addLog("Logging Initialized");  //add a basic log message
 *
 * There are several items that may be added to logs: message, severity, and an object.
 *
 * This logging structure may be used from cp by adding this file to scripts custom, and including:
 *
 * require_once (get_cfg_var('doc_root') . '/custom/esglog-v2.0.php');
 *  $this -> logger = esgLogger::getInstance();
 $this -> logger -> enable(ENABLE_LOGGING);
 $stdOutLogger = new stdoutLogger(logWorker::All);
 $this -> logger -> registerLogWorker($stdOutLogger);
 $this -> logger -> addLog("Logging Initialized");
 esgLogger::log('you can also log like this');

 *
 */
class esgLogger {

    private $logWorkers;
    private $enableLogging = false;

    /**
     * Singleton members
     *
     */
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new esgLogger();
        }
        return self::$instance;
    }

    /**
     * Turns logging on and off.  Defaults to off, no logging will happen unless explicitly turned on.
     * No changes are permanent until this is turned on.
     * esgLogger::enable(ENABLE_LOGGING);
     */
    public static function enable($enableLogging = false) {
        self::getInstance() -> enableLogging = $enableLogging;
    }

    /**
     * Private because singleton
     *
     */
    private function __construct() {
        $this -> logWorkers = array();
    }

    /**
     * Use this method to register or replace a log observer
     */
    public function registerLogWorker(logWorker $workerInstance) {
        if ($this -> enableLogging) {

            //make sure we don't already have a worker of this type, if we do replace it.
            foreach ($this->logWorkers as $key => $worker) {
                if ($workerInstance instanceof $worker) {
                    $this -> logWorkers[$key] = $workerInstance;
                    return;
                }
            }

            $this -> logWorkers[] = $workerInstance;

        }
    }

    /**
     * Convenience method to allow simpler calls to the logger.  Removes the need to call getInstance in user code.
     * equivalent to esgLogger::getInstance()->addLog(...);
     */
    public static function log($message, $severity = null, $object = null) {
        return self::getInstance() -> addLog($message, $severity, $object);
    }

    /**
     * Use this message to send a log message to the observers (worker classes)
     */
    public function addLog($message, $severity = null, $object = null) {
        if ($this -> enableLogging) {
            foreach ($this->logWorkers as $index => $worker) {
                $worker -> addLog($message, $severity, $object);
            }
        }
    }

}

/**
 * Class esgLogger
 * @author: Ben Hussey
 * @version: 1.0
 */
abstract class logWorker {

    /**
     * Logging Levels
     */
    const None = 0;
    const Notice = 1;
    const Debug = 2;
    const All = 3;

    /**
     * @var array Labels for log output, these are used for english readable log outputs
     */
    protected $severityLabels = array(
        self::Notice => "Notice",
        self::Debug => "Debug",
        self::All => "All"
    );

    /**
     * Indicates the default level of logging if none specified
     */
    protected $debugLevel = self::None;

    /**
     *Indicates the default severity that log message will have if none specified
     */
    protected $defaultSeverity = self::Debug;

    //force logs to only be created through the singleton event class
    abstract protected function log($message, $severity = null, $object = null, $srcFile = null, $srcLine = null) ;
    public function addLog($message, $severity = null, $object = null) {

        //determine the calling file and line
        $backtrace = debug_backtrace();
        $callingIndex = 0;
        foreach ($backtrace as $index => $trace) {
            if ($trace['file'] != __FILE__) {
                $callingIndex = $index;
                break;
            }
        }

        $this -> log($message, $severity, $object, $backtrace[$callingIndex]['file'], $backtrace[$callingIndex]['line']);
    }

}

/**
 * Class fileLogger
 * @author: Ben Hussey
 * @version: 1.0
 *
 * Main logging worker class used to write logs to a file.  This class should not be used alone - it should be used as part of the esgLogger event bus.
 *
 *
 */
class fileLogger extends logWorker {

    /**
     * @var string default directory logfile will be written to
     */
    private $logDir = "/tmp/esgLog-cpm";

    /**
     * @var null Contains full server path to logfile
     */
    private $fullyQualifiedFilename = null;

    /**
     * @param null $debugLevel Indicates the level of debugging that will be logged
     * @throws Exception
     */
    public function __construct($debugLevel = null) {

        if (!empty($debugLevel)) {
            $this -> debugLevel = $debugLevel;
        }

        //@todo: remove this call, and implement method chaining to allow configuration prior to initialization
        $this -> _initialize($this -> logDir, true);
    }

    /**
     * Creates log directory if needed, and sets logFile property.
     *
     * @static
     * @param string $logDir Log directory to initialize.
     * @param bool $clearLog Whether to clear logfile.
     * @return string Path to current log file.
     * @throws Exception
     */
    private function _initialize($logDir, $clearLog = false) {
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            $oldumask = umask(0);
            mkdir($logDir, 0775, true);
            umask($oldumask);
            if (!is_dir($logDir)) {
                throw new \Exception(sprintf("Log directory could not be created: %s", $logDir));
            }
        }

        // Generate log filename based on date
        $this -> fullyQualifiedFilename = sprintf("%s/%s.log", $this -> logDir, date('Y-m-d'));
        if ($clearLog) {
            file_put_contents($this -> fullyQualifiedFilename, "");
        }
    }

    /**
     *  Create a log message.
     *
     * @param string $message a text message indicating the nature of the log
     * @param int $severity indicates the log level required for this message
     * @param oibject $object passed object that will be included in log
     */
    protected function log($message, $severity = null, $object = null, $srcFile = null, $srcLine = null) {
        //make sure we want to make this log
        if (empty($severity)) {
            $severity = $this -> defaultSeverity;
        }
        if ($severity > $this -> debugLevel) {
            return;
        }
        if (empty($this -> fullyQualifiedFilename)) {
            return;
        }

        //format strings
        $objectStr = "";
        if (!empty($object)) {
            $objectStr = "\n" . print_r($object, true);
        }
        if (!empty($source)) {
            $sourceStr = $source;
        }
        if (!empty($lineNumber)) {
            $lineStr = '@' . $lineNumber;
        }

        //send the log out.
        $output = sprintf("%s (%s) %s@%s:  %s %s \n\n", date('c'), $this -> severityLabels[$severity], $srcFile, $srcLine, $message, $objectStr);
        file_put_contents($this -> fullyQualifiedFilename, $output, FILE_APPEND);
    }

}

/**
 * Class stdoutLogger
 * @author: Ben Hussey
 * @version: 1.0
 *
 * Main logging worker class used to write logs to a standard output.  This class should not be used alone - it should be used as part of the esgLogger event bus.
 *
 */
class stdoutLogger extends logWorker {

    /**
     * @param null $debugLevel Indicates the level of debugging that will trigger a log message
     * @throws Exception
     */
    public function __construct($debugLevel = null) {

        if (!empty($debugLevel)) {
            $this -> debugLevel = $debugLevel;
        }
    }

    /**
     * Overridden addLog function - removes need for backtrace to improve efficiency
     */
    public function addLog($message, $severity = null, $object = null) {
        $this -> log($message, $severity, $object);
    }

    /**
     *  Create a log message.
     *
     * @param string $message a text message indicating the nature of the log
     * @param int $severity indicates the log level required for this message
     * @param object $object passed object that will be included in log
     */
    protected function log($message, $severity = null, $object = null, $srcFile = null, $srcLine = null) {
        //make sure we want to make this log
        if (empty($severity)) {
            $severity = $this -> defaultSeverity;
        }
        if ($severity > $this -> debugLevel) {
            return;
        }

        if ($this -> debugLevel < logWorker::Debug) {
            //send the log out.
            printf("\n%s\n", $message);
        } else {
            //format strings
            $objectStr = "";
            if (!empty($object)) {
                $objectStr = "\n" . print_r($object, true);
            }
            if (!empty($source)) {
                $sourceStr = $source;
            }
            if (!empty($lineNumber)) {
                $lineStr = '@' . $lineNumber;
            }

            //send the log out.
            printf("%s: %s %s \n\n", date('c'), $message, $objectStr);
        }
    }

}

/**
 * Class javascriptConsoleDebugger
 * @author: Ben Hussey
 * @version: 1.0
 *
 * Main logging worker class used to write logs to a firebugs console output.  This class should not be used alone - it should be used as part of the esgLogger event bus.
 *
 */
class javascriptConsoleDebugger extends logWorker {

    /**
     * @param null $debugLevel Indicates the level of debugging that will trigger a log message
     * @throws Exception
     */
    public function __construct($debugLevel = null) {

        if (!empty($debugLevel)) {
            $this -> debugLevel = $debugLevel;
        }
    }

    /**
     * Overridden addLog function - removes need for backtrace to improve efficiency
     */
    public function addLog($message, $severity = null, $object = null) {
        $this -> log($message, $severity, $object);
    }

    /**
     *  Create a log message.
     *
     * @param string $message a text message indicating the nature of the log
     * @param int $severity indicates the log level required for this message
     * @param object $object passed object that will be included in log
     */
    protected function log($message, $severity = null, $object = null, $srcFile = null, $srcLine = null) {
        //make sure we want to make this log
        if (empty($severity)) {
            $severity = $this -> defaultSeverity;
        }
        if ($severity > $this -> debugLevel) {
            return;
        }

        //send the log out.
        printf("
<script>console.debug('%s')</script>", addslashes($message));
        if (!is_null($object)) {
            printf("
<script>console.debug('%s')</script>", addslashes(print_r($object, true)));
        }

    }

}
