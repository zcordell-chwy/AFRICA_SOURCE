<?
/*
 * CPMObjectEventHandler: transaction_createUpdate
 * Package: RN
 * Objects: financial\transactions
 * Actions: Create,Update
 * Version: 1.2
 */

// This object procedure binds to v1_3 of the Connect PHP API
use \RightNow\Connect\v1_2 as RNCPHP;

// This object procedure binds to the v1 interface of the process
// designer
use \RightNow\CPM\v1 as RNCPM;

/**
 * An Object Event Handler must provide two classes:
 * - One with the same name as the CPMObjectEventHandler tag
 * above that implements the ObjectEventHandler interface.
 * - And one of the same name with a "_TestHarness" suffix
 * that implements the ObjectEventHandler_TestHarness interface.
 *
 * Each method must have an implementation.
 */
require_once ("./scripts/custom/handleinternaltransactions.php");

class transaction_createUpdate

implements RNCPM\ObjectEventHandler {
    /**
     * CPM Entry Point
     */
    public static function apply($run_mode, $action, $obj, $n_cycles) {

        try {
            if ($n_cycles < 1){
                new \handleInternalTransactions($run_mode, $action, $obj, $n_cycles);
            }
            
        } catch(Exception $e) {
            print_r($e);
        }
        return true;
    }

}

/*
 The Test Harness
 */
class transaction_createUpdate_TestHarness
implements RNCPM\ObjectEventHandler_TestHarness {
    static $donation_invented = NULL;
    static $pledgeMonthly_invented = null;
    static $pledgeAnnual_invented = null;
    static $pledgeOneTime_invented = null;
    static $pledgeQuarterly_invented = null;
    static $d2p_invented = null;
    static $item = null;
    static $gift_invented = null;
    static $completedTrans = null;
    static $refundedTrans = null;


    static $unallocatedFundId = "262"; //prod
    //static $unallocatedFundId = "125";//test
    static $giftFundId = 245; //prod
    //static $giftFundId = 126;//test
    static $testPledgeFundId = 215;//prod
    //static $testPledgeFundId = 111;//text
    static $testPledgeInternalAllocationFundId = 247;//prod
    //static $testPledgeInternalAllocationFundId = 127;//test
    static $testPledgeAllocationPercent = .09;

    static $completedName = "Completed";
    static $refundedName = "Refunded";
    static $oneTimePledgeType = "Special/One Time";
    static $recurringPledgeType = "Recurring";
    static $adminFundCodeId = 89;//prod
    //static $adminFundCodeId = 48;//test
    static $remainingFundsAvailable = 0;

    static $monthlyFreqId = 5;//prod
    //static $monthlyFreqId = 6;//test
    static $quarterlyFreqId = 7;
    static $annualFreqId = 1;
    static $oneTimeFreqId = 9;

    static $completeTransStatusId = 3;
    static $refundedTransStatusId = 7;

    static $testsPass = true;

    public static function setup() {
        try {
            // For this test, create a new
            // organization as expected.
            $donation = new RNCPHP\donation\Donation;
            $donation -> Amount = "250.00";
            $donation -> save();

            //added to avoid the 100% admin on quarterly
            $dummyDonation = new RNCPHP\donation\Donation;
            $dummyDonation -> Amount = "250.00";
            $dummyDonation -> save();

            //one $7 pledge, behind
            $monthlyPledge = new RNCPHP\donation\pledge;
            $monthlyPledge -> Fund = static::$testPledgeFundId;
            $monthlyPledge -> PledgeAmount = "7.00";
            $monthlyPledge -> NextTransaction = strtotime("last Monday");
            $monthlyPledge -> Frequency = static::$monthlyFreqId;
            $monthlyPledge -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $monthlyPledge -> ID;
            $d2p -> DonationRef = $donation -> ID;
            $d2p -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $monthlyPledge -> ID;
            $d2p -> DonationRef = $dummyDonation -> ID;
            $d2p -> save();


            //one $7 pledge
            $quarterlyPledge = new RNCPHP\donation\pledge;
            $quarterlyPledge -> Fund = static::$testPledgeFundId;
            $quarterlyPledge -> PledgeAmount = "24.00";
            $quarterlyPledge -> Frequency = static::$quarterlyFreqId;
            $quarterlyPledge -> NextTransaction = strtotime("+1 day");
            $quarterlyPledge -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $quarterlyPledge -> ID;
            $d2p -> DonationRef = $donation -> ID;
            $d2p -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $quarterlyPledge -> ID;
            $d2p -> DonationRef = $dummyDonation -> ID;
            $d2p -> save();

            //Annual, pledge, initial
            $child = new RNCPHP\sponsorship\Child;
            $child -> FamilyName = "test";
            $child -> GivenName = "givenname";
            $child -> save();

            $annualPledge = new RNCPHP\donation\pledge;
            $annualPledge -> PledgeAmount = "120.00";
            $annualPledge -> Frequency = static::$annualFreqId;
            $annualPledge -> NextTransaction = strtotime("+1 day");
            $annualPledge -> Fund = static::$testPledgeFundId;
            $annualPledge -> Child = $child;
            $annualPledge -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $annualPledge -> ID;
            $d2p -> DonationRef = $donation -> ID;
            $d2p -> save();

            $oneTimePledge = new RNCPHP\donation\pledge;
            $oneTimePledge -> Fund = static::$testPledgeFundId;
            $oneTimePledge -> PledgeAmount = "75.00";
            $oneTimePledge -> Frequency = static::$oneTimeFreqId;
            $oneTimePledge -> save();

            $d2p = new RNCPHP\donation\donationToPledge;
            $d2p -> PledgeRef = $oneTimePledge -> ID;
            $d2p -> DonationRef = $donation -> ID;
            $d2p -> save();

            $item = new RNCPHP\online\Items;
            $item -> Amount = "3.00";
            $item -> Title = "Test Title";
            $item -> save();

            $gift = new RNCPHP\donation\DonationItem;
            $gift -> DonationId = $donation -> ID;
            $gift -> Quantity = 2;
            $gift -> Item = $item -> ID;
            $gift -> save();

            $trans = new RNCPHP\financial\transactions;
            $trans -> totalCharge = "400.00";
            $trans -> donation = $donation -> ID;
            $trans -> currentStatus = static::$completeTransStatusId;
            $trans -> save();

            $refundedTrans = new RNCPHP\financial\transactions;
            $refundedTrans -> totalCharge = "400.00";
            $refundedTrans -> donation = $donation -> ID;
            $refundedTrans -> currentStatus = static::$refundedTransStatusId;
            $refundedTrans -> save();

            static::$donation_invented = $donation;
            static::$pledgeMonthly_invented = $monthlyPledge;
            static::$pledgeAnnual_invented = $annualPledge;
            static::$pledgeOneTime_invented = $oneTimePledge;
            static::$pledgeQuarterly_invented = $quarterlyPledge;
            static::$gift_invented = $gift;
            //static::$completedTrans = $trans;
            static::$completedTrans = RNCPHP\financial\transactions::fetch(743346);
            static::$refundedTrans = $refundedTrans;

        } catch(exception $e) {
            print_r($e -> message);
        }
        return;
    }

    /**
     *
     *
     */
    public static function fetchObject($action, $object_type) {
        // Return the object that we
        // want to test with.
        // You could also return an array of objects
        // to test more than one variation of an object.
        return ( array(static::$completedTrans));
    }

    /**
     *
     *
     */
    public static function validate($action, $object) {

        // Add one note.
        //return (count($object -> Notes) === 1);
        // $allInternalTrans = array();
        // try {
            // $roql = "select financial.internalTransaction from financial.internalTransaction where financial.internalTransaction.transactionRef = '" . static::$completedTrans -> ID . "'";
            // $internalTransObjs = RNCPHP\ROQL::queryObject($roql) -> next();
            // while ($internalTrans = $internalTransObjs -> next()) {
                // //print_r("looping\n");
                // $allInternalTrans[] = $internalTrans;
            // }
        // } catch(\Exception $e) {
            // print_r("FAIL: Exception on " . __LINE__ . ": " . $e -> getMessage() . "\n");
            // return false;
        // }
// 
        // //print_r($allInternalTrans);
// 
        // static::checkTransPledge(23, "Annual", static::$pledgeAnnual_invented, $allInternalTrans, 12, true);
        // static::checkTransPledge(6, "Quarterly", static::$pledgeQuarterly_invented, $allInternalTrans, 3, false);
        // static::checkTransPledge(4, "Monthly", static::$pledgeMonthly_invented, $allInternalTrans, 1, TRUE);
        // static::checkTransPledge(2, "Special/One Time", static::$pledgeOneTime_invented, $allInternalTrans, 1, false);
        // static::checkExcessContribution($allInternalTrans);
        // static::checkTransGift(1, static::$gift_invented, $allInternalTrans);
// 
// 
        // static::checkPaidThroughDate(static::$pledgeAnnual_invented, 12);
        // static::checkPaidThroughDate(static::$pledgeMonthly_invented, 1);
        // static::checkPaidThroughDate(static::$pledgeQuarterly_invented, 3);

        return true;
    }

    /**
     *
     *
     */
    private static function checkPaidThroughDate($pledge, $numberOfMonths) {
        if ($pledge -> NextTransaction < strtotime("+$numberOfMonths months")) {
            print("FAIL: Pledge {$pledge->ID} not paid through $numberOfMonths months\n");
            static::$testsPass = false;
        } else {
            print("PASS: Pledge {$pledge->ID} paid through $numberOfMonths months\n");
        }
    }

    /**
     *
     *
     */
    private static function checkExcessContribution(array $allInternalTrans) {
        //let's do this slowly!
        $foundTrans = false;
        foreach ($allInternalTrans as $internalTrans) {
            if ($internalTrans -> Fund -> ID == static::$unallocatedFundId && $internalTrans -> pledge == null) {
                if ($foundTrans) {
                    print("FAIL: multiple excess contributions");
                    static::$testsPass = false;
                }
                $foundTrans = true;

                if ($internalTrans -> amount == "161.00") {
                    print("PASS: Excess contribution amount of {$internalTrans->amount} correct\n");
                } else {
                    print("FAIL: Excess contribution amount of {$internalTrans->amount} != $161.00\n");
                    static::$testsPass = false;
                }
            }
        }
        if (!$foundTrans) {
            print("FAIL:No excess contribution found\n");
            static::$testsPass = false;
        }
    }

    /**
     *
     *
     *
     */
    private static function checkTransPledge($numberOfTrans, $nameOfFrequency, $pledge, array $allInternalTrans, $fractionPerPledge = 1, $expectInitialAdmin = false) {
        $startTrans = $numberOfTrans;
        $foundAdmin = ($expectInitialAdmin) ? false : true;
        //let's do this slowly!
        foreach ($allInternalTrans as $internalTrans) {
            if ($internalTrans -> pledge && $internalTrans -> pledge -> ID == $pledge -> ID) {
                $numberOfTrans--;
                //spon funds
                if ($internalTrans -> Fund -> ID == static::$adminFundCodeId) {
                    $initialAdminAmount = number_format(($pledge -> PledgeAmount / $fractionPerPledge), 2, ".", "");
                    if ($internalTrans -> amount == $initialAdminAmount && !$foundAdmin) {
                        $foundAdmin = true;
                        print("PASS: Initial Admin amount correct of $nameOfFrequency\n");
                    } else {
                        print("FAIL:  Initial Admin amount invalid for fund expected amount: $initialAdminAmount on $nameOfFrequency \n");
                        static::$testsPass = false;
                    }

                    //allocation funds
                } elseif ($internalTrans -> Fund -> ID == static::$testPledgeInternalAllocationFundId) {
                    $expectedamount = number_format(($pledge -> PledgeAmount / $fractionPerPledge) * static::$testPledgeAllocationPercent, 2, ".", "");
                    if ($internalTrans -> amount == $expectedamount) {
                        print("PASS: allocation amount Correct $nameOfFrequency\n");
                    } else {
                        print("FAIL: Internal transaction amount {$internalTrans->amount} invalid for allocation fund expected amount: $expectedamount on $nameOfFrequency \n ");
                        static::$testsPass = false;
                    }

                    //regular funds
                } else {
                    $expectedAmount = number_format(($pledge -> PledgeAmount / $fractionPerPledge) * (1 - static::$testPledgeAllocationPercent), 2, ".", '');
                    if ($internalTrans -> amount == $expectedAmount) {
                        print("PASS: Allocation amount Correct $nameOfFrequency\n");
                    } else {
                        print("FAIL: Internal transaction amount: {$internalTrans->amount} invalid for fund expected amount: $expectedAmount on $nameOfFrequency \n");
                        static::$testsPass = false;
                    }
                }
            }
        }
        if ($numberOfTrans == 0) {
            print("PASS: Found correct number of $nameOfFrequency transactions.\n");
        } else {
            print("FAIL: Invalid number of $nameOfFrequency transactions.  Started with $startTrans and have $numberOfTrans left. \n");
            static::$testsPass = false;
        }
    }

    /**
     *
     *
     */
    private static function checkTransGift($numberOfTrans, $gift, array $allInternalTrans) {
        //let's do this slowly!
        foreach ($allInternalTrans as $internalTrans) {
            if ($internalTrans -> donationItemId && $internalTrans -> donationItemId -> ID == $gift -> ID) {
                $numberOfTrans--;
                if ($internalTrans -> Fund -> ID == static::$unallocatedFundId && $internalTrans -> amount != ($gift -> Item -> Amount * $gift -> Quantity)) {
                    print("FAIL: Internal transaction amount invalid for admin fund on gift \n");
                    static::$testsPass = false;
                }
            }
        }
        if ($numberOfTrans != 0) {
            print("FAIL: Invalid number of $nameOfFrequency transactions \n");
            static::$testsPass = false;
        }
    }

    /**
     *
     *
     */
    public static function cleanup() {
        // Destroy every object invented
        // by this test.
        // Not necessary since in test
        // mode and nothing is committed,
        // but good practice if only to
        // document the side effects of
        // this test.


        return;
    }

}
