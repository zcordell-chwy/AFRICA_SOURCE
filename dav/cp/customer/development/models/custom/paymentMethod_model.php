<?php

namespace Custom\Models;

use \RightNow\Connect\v1_3 as RNCPHP;

require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');

class paymentMethod_model extends \RightNow\Models\Base
{
    function __construct()
    {
        parent::__construct();
        $this->CI->load->helper('constants');
        $this->CI->load->helper('log');
    }

    /**
     * This function can be executed a few different ways depending on where it's being called:
     *
     * From a widget or another model: $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs();
     *
     * From a custom controller: $this->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs();
     *
     * Everywhere else: $CI = get_instance();
     *                  $CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs();
     */
    function getCurrentPaymentMethodsObjs($c_id = null)
    {
        //logMessage("Starting " . __FUNCTION__ . ' in ' . __CLASS__);
        if ($c_id == null) {
            $c_id = $this->CI->session->getProfileData('c_id');
        }

        $paymentMethods = array();
        if (is_null($c_id) || !is_int($c_id) || $c_id < 1) {
            //logMessage("invalid c_id: " . $c_id);
            return $paymentMethods;
        }
        $currentYear = date("Y");
        $currentMonth = date("m");
        $roql = "Select financial.paymentMethod from financial.paymentMethod where financial.paymentMethod.Contact.ID = $c_id and (financial.paymentMethod.Inactive is null OR financial.paymentMethod.Inactive = 0)  and ((expYear > '" . $currentYear . "') or (expYear = '" . $currentYear . "' and expMonth >= '" . $currentMonth . "') OR (CardType = 'Checking'))";
        logMessage($roql);
        try {
            //logMessage($roql);
            $query = RNCPHP\ROQL::queryObject($roql)->next();
            $pm = $query->next();
            while ($pm instanceof RNCPHP\financial\paymentMethod) {
                $paymentMethods[] = $pm;
                $pm = $query->next();
            }
        } catch (Exception $e) {
            return array();
        }
        return $paymentMethods;
    }

    /**
     *
     */
    function createPaymentMethod($c_id, $cardType = null, $pn_ref = null, $paymentMethodType = null, $expMonth = null, $expYear = null, $lastFour = null, $infoKey = null)
    {
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Begin Paymethod","");
        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r(func_get_args(), true),"");

        $pId = -1;

        if (is_null($c_id) || !is_numeric($c_id) || $c_id < 1) {
            return -1;
        }
        try {
            helplog(__FILE__, __FUNCTION__ . __LINE__,78,"");
            $newPM = new RNCPHP\financial\paymentMethod;
            $newPM->Contact = $c_id;
            if (!is_null($cardType)) {
                $newPM->CardType = $cardType;
            }
            if (!is_null($pn_ref)) {
                $newPM->PN_Ref = $pn_ref;
            }
            if (!is_null($paymentMethodType)) {
                $newPM->PaymentMethodType = RNCPHP\financial\paymentMethodType::fetch($paymentMethodType);
            }
            if (!is_null($expMonth)) {
                $newPM->expMonth = $expMonth;
            }
            if (!is_null($expYear)) {
                $newPM->expYear = $expYear;
            }
            if (!is_null($lastFour)) {
                $newPM->lastFour = $lastFour;
            }
            if (!is_null($infoKey)) {
                $newPM->InfoKey = $infoKey;
            }
            helplog(__FILE__, __FUNCTION__ . __LINE__, 87, "Saving PM");
            $pId = $newPM->save();
        } catch (Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, 89, $e->getMessage());
            return -1;
        } catch (RNCPHP\ConnectAPIError $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, 95, $e->getMessage());
            return -1;
        }
        helplog(__FILE__, __FUNCTION__ . __LINE__, "New Payment Method created with ID: " . $newPM->ID,"");
        logMessage("New Payment Method created with ID: " . $newPM->ID);
        return $newPM;
    }

    function deletePaymentMethod($pmID)
    {

        try {
            if ($pmID > 0) {
                //just check one more time to see if its associated with a pledge
                $roql = "Select donation.pledge from donation.pledge where donation.pledge.paymentMethod2 = " . $pmID;
                $results = RNCPHP\ROQL::queryObject($roql)->next();
                $pledge = $results->next();
                if ($pledge) {
                    //found a pledge w this pay method.
                    return "Cannot delete payment " . $pmID . ".  It is currently associated with a pledge";
                } else {
                    $pm = RNCPHP\financial\paymentMethod::fetch($pmID);
                    //dont actually destroy this, we'll lose the history in the transactions table.  set it to disabled
                    $pm->Inactive = 1;
                    $pm->save();
                }
            }
        } catch (Exception $ex) {
            return "Error deleteing payment " . $pmID . "." . $ex->getMessage();
        }

        return 'SuccessDelete!';
    }

    // private function _logToFile($lineNum, $message){

    //     $hundredths = ltrim(microtime(), "0");

    //      $fp = fopen('/tmp/esgLogPayCron/refundNewPay_'.date("Ymd").'.log', 'a');
    //      fwrite($fp,  date('H:i:s.').$hundredths.": Paymethod Model @ $lineNum : ".$message."\n");
    //      fclose($fp);

    //  }
}
