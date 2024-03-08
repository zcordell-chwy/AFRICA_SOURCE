<?
/*
 * CPMObjectEventHandler: donation_createUpdate
 * Package: RN
 * Objects: donation\Donation
 * Actions: Create,Update
 * Version: 1.3
 */

// This object procedure binds to v1_1 of the Connect PHP API
use \RightNow\Connect\v1_3 as RNCPHP;

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

class donation_createUpdate

implements RNCPM\ObjectEventHandler {

    public static function apply($run_mode, $action, $obj, $n_cycles) {

        if ($n_cycles < 1) {
            
            echo "setting donation amount:".number_format($obj->Amount, 2, '.', '');
            $obj->Amount_n = number_format($obj->Amount, 2, '.', '');

            $pledges = self::getPledges($obj->ID);
            foreach($pledges as $pledge){
                if($pledge && $pledge->PledgeAmount_n != number_format($pledge->PledgeAmount, 2, '.', '')){
                    echo "Setting pledge charge to:".number_format($pledge->PledgeAmount, 2, '.', '')."\n";
                    $pledge->PledgeAmount_n = number_format($pledge->PledgeAmount, 2, '.', '');
                    $pledge->save();
                }
            }

            $transaction = self::getTransaction($obj->ID);
            echo "totalCharge_n:". $transaction->totalCharge_n." totalCharge formatted:".number_format($transaction->totalCharge, 2, '.', '')."\n";
            if($transaction && $transaction->totalCharge_n != number_format($transaction->totalCharge, 2, '.', '')){
                echo "Setting total charge to:".number_format($transaction->totalCharge, 2, '.', '')."\n";
                $transaction->totalCharge_n = number_format($transaction->totalCharge, 2, '.', '');
                $transaction->save();
            }
            

            $obj -> save();
            
        }

    }// apply()
    
    public  static function getPledges($donationId = 0) {
        $amount = 0;
        try {
            $roql = sprintf("SELECT don.PledgeRef FROM donation.donationToPledge as don where donation.donationToPledge.DonationRef.ID = %s", $donationId);
            $pages = RNCPHP\ROQL::queryObject($roql) -> next();
            $pledges = array();
            while ($pledge = $pages -> next()) {
                $pledges[] = $pledge;
            }
        } catch(Exception $e) {
            return false;
        }

        return $pledges;
    }

    public static function getTransaction($donationId) {
         
        $roql = 'SELECT financial.transactions FROM financial.transactions WHERE financial.transactions.donation.ID = ' . $donationId;
        $trans = RNCPHP\ROQL::queryObject($roql) -> next();

        return $trans->next();;
    }

    private static function _getValues($parent) {
        try {
            // $parent is a non-associative (numerically-indexed) array
            if (is_array($parent)) {

                foreach ($parent as $val) {
                    self::_getValues($val);
                }
            }

            // $parent is an associative array or an object
            elseif (is_object($parent)) {

                while (list($key, $val) = each($parent)) {

                    $tmp = $parent->$key;

                    if ((is_object($parent->$key)) || (is_array($parent->$key))) {
                       self::_getValues($parent->$key);
                    }
                }
            }
        } catch (exception $err) {
            // error but continue
        }
    }
    

}

/*
 The Test Harness
 */
class donation_createUpdate_TestHarness
implements RNCPM\ObjectEventHandler_TestHarness {
    static $donation = NULL;

    public static function setup() {
        return;
    }

    public static function fetchObject($action, $object_type) {
        static::$donation = RNCPHP\donation\Donation::fetch(1282827);
        return static::$donation;
    }

    public static function validate($action, $object) {
        return true;
    }

    public static function cleanup() {
        return;
    }
    
    

}
