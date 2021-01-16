<?php

namespace Custom\Models;

use RightNow\Connect\v1_3 as RNCPHP;
use RightNow\Utils\Framework;

require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI('api_access', 'Password1');

/**
 * This model would be loaded by using $this->load->model('custom/transaction_model');
 */
class transaction_model extends \RightNow\Models\Base
{

    private $allowedRefundStatus = array("Completed");
    private $allowedChargeStatus = array(
        "",
        "Declined",
        DEFAULT_TRANSACTION_STATUS_ID
    );
    private $allowedReversalStatus = array(TRANSACTION_PROCESSING_STATUS_ID);

    function __construct()
    {
        parent::__construct();
        $this->CI->load->helper('constants');
        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD started transaction model");
    }

    /**
     * acts as a lock against multiple charge attempts.  If the transaction processing is set to true, no attempts to access it will succeed.
     */
    public function startProcessingTransaction($t_id, $transactionType = NULL)
    {

        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Looking for transaction:" . $t_id . " with transaction type" . $transactionType, "Transaction");

        $trans = $this->get_transaction($t_id);

        if (!$trans instanceof RNCPHP\financial\transactions) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, ": No Transaction found", "Transaction");
            return false;
        }

        $status = $trans->currentStatus->ID;

        if (is_null($status) || $status < 1) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, " Could not determin status", "Transaction");
            return false;
        }


        switch ($transactionType) {
            case FS_SALE_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedChargeStatus)) {
                    $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Invalid transaction status: " . $status, "Transaction");
                    return false;
                }
                break;
            case FS_REFUND_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedRefundStatus)) {
                    $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Invalid transaction status: " . $status, "Transaction");
                    return false;
                }
                break;
            case FS_REVERSAL_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedReversalStatus)) {
                    $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Invalid transaction status: " . $status, "Transaction");
                    return false;
                }
                break;
            default:
                $statusIdx = false;
                break;
        }


        try {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, ": Setting Transaction status to " . TRANSACTION_PROCESSING_STATUS_ID, "Transaction");
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(TRANSACTION_PROCESSING_STATUS_ID);
            $trans->save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "Transaction");
            return false;
        }
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Returning True", "Transaction");
        return true;
    }

    private function isAllowedStatus($status, $statusArr)
    {
        $statusIdx = array_search($status, $statusArr);
        if ($statusIdx === false || $statusIdx < 1) {
            return false;
        }
        return true;
    }

    public function create_transaction($c_id, $amt, $desc = null, $donationId = null)
    {
        //logMessage("starting " . __FUNCTION__);
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        $this->CI->load->helper('constants');
        $desc = addslashes($desc);
        if (strlen($desc) > 254) {
            $desc = substr($desc, 0, 251) . "...";
        }
        try {
            $trans = new RNCPHP\financial\transactions;
            $transMeta = RNCPHP\financial\transactions::getMetaData();
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(DEFAULT_TRANSACTION_STATUS);

            $trans->totalCharge = number_format($amt, 2, '.', '');
            $trans->contact = intval($c_id);
            $trans->description = is_null($desc) ? DEFAULT_TRANSACTION_DESC : $desc;
            if (!is_null($donationId)) {
                $trans->donation = intval($donationId);
            }
            $trans->save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Exception Found");
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": " . $e -> getMessage());
            return false;
        }
        return $trans->ID;
    }

    /**
     *
     */
    public function update_transaction($t_id, $c_id, $amt = -1, $desc = null, $donationId = null, $statusString = null, $paymentMethodId = -1, $PNRef = null)
    {
        //logMessage("starting " . __FUNCTION__);
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "DonationId:$donationId Amt:$amt ContactId:$c_id TransactionAmount:$t_id Status:$statusString NewPayId:$paymentMethodId PnRef:" . $PNRef, "Transaction");
        if (strlen($desc) > 254) {
            $desc = substr($desc, 0, 251) . "...";
        }
        //logMessgae("in transaction update. payment method =  ".$paymentMethodId);
        try {
            if (!is_null($t_id) && is_numeric($t_id) && $t_id > 0) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "TransID:$t_id", "Transaction");
                $trans = $this->get_transaction(intval($t_id));
            } else {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Returning False", "Transaction");
                return false;
            }
            if (!$trans instanceof RNCPHP\financial\transactions) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Returning False", "Transaction");
                //logMessage("Transaction Not found in " . __FUNCTION__);
                return false;
            }
            $transMeta = RNCPHP\financial\transactions::getMetaData();
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(DEFAULT_TRANSACTION_STATUS_ID);
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($trans->currentStatus, true), "Transaction");
            if ($amt > 0) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, number_format($amt, 2, '.', ''), "Transaction");
                $trans->totalCharge = number_format($amt, 2, '.', '');
            }
            $trans->contact = intval($c_id);
            if (!is_null($desc)) {
                $trans->description = $desc;
            }
            if (!is_null($donationId)) {
                $trans->donation = intval($donationId);
            }
            if (!is_null($statusString) && strlen($statusString) > 0) {
                $this->addNoteToTrans($trans, "Changing status from (LookupName unavailable, ID instead)" . $trans->currentStatus->ID . " to " . $statusString);
                $trans->currentStatus = RNCPHP\financial\transaction_status::fetch($statusString);
            }
            if (!is_null($paymentMethodId) && $paymentMethodId > 0) {
                $trans->paymentMethod = intval($paymentMethodId);
            }

            if (!is_null($PNRef)) {
                $trans->refCode = $PNRef;
            }
            logMessage("Updating Transaction: statusstring = $statusString and TRANSACTION_SALE_SUCCESS_STATUS = " . TRANSACTION_SALE_SUCCESS_STATUS);
            if ($statusString == TRANSACTION_SALE_SUCCESS_STATUS) {
                $trans->save();
                logMessage("not suppressing");
            } else {
                $trans->save(RNCPHP\RNObject::SuppressAll);
                logMessage("suppressing");
            }
            RNCPHP\ConnectAPI::commit();
            //logMessage($trans);

        } catch (\Exception $e) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Exception Found");
            //logMessage($e -> getMessage());
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "Transaction");
            return false;
        }
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Returning:" . $trans->ID, "Transaction");
        return $trans->ID;
    }

    public function addDonationToTransaction($trans_id, $donation_id)
    {

        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Starting " . __FUNCTION__);
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        $trans = $this->get_transaction($trans_id);
        if ($trans == false) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": No valid transaction");
            return false;
        }
        try {
            $trans->donation = intval($donation_id);
            $trans->save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch (Exception $e) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "Transaction");
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function get_transaction($t_id)
    {

        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Starting get transaction");
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        try {
            $roql = sprintf("SELECT financial.transactions FROM financial.transactions where ID = %s", $t_id);
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": " . $roql);
            $qo = RNCPHP\ROQL::queryObject($roql)->next();
            $tr = $qo->next();
        } catch (Exception $e) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": " . $e -> getMessage());
            return false;
        }
        if (!$tr instanceof RNCPHP\financial\transactions) {
            return false;
        }

        if (isset($tr->contact)) {
            if ($this->isContactAllowedToReadTransaction($tr) !== true) {
                //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Contact not allowed access to transaction: " . $tr -> ID);
                return false;
            }
        } else {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": Contact not found on transaction object");
            return false;
        }

        return $tr;
    }

    public function updateTransStatus($tran_id, $statusID = null, $paymentMethodId = null, $PNRef = null)
    {

        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD starting updateTransStatus function");
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        $trans = $this->get_transaction($tran_id);
        if (!$trans instanceof RNCPHP\financial\transactions) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, " invalid type", "Transaction");
            return false;
        }
        if (!is_null($paymentMethodId) && $paymentMethodId > 0) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "valid paymethod id = $paymentMethodId", "Transaction");
            $trans->paymentMethod = $paymentMethodId;
        }

        if ($trans->currentStatus->ID == $statusID) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "returning true", "Transaction");
            return true;
        }

        if (!is_null($PNRef)) {
            $trans->refCode = $PNRef;
        }

        try {
            if (!is_null($statusID) && $statusID > 0) {
                $this->addNoteToTrans($trans, "Changing status from (LookupName unavailable, ID instead) " . $trans->currentStatus->ID . " to " . $statusID);
                $trans->currentStatus = RNCPHP\financial\transaction_status::fetch($statusID);
            }

            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Updating Transaction: statusID = $statusID and TRANSACTION_SALE_SUCCESS_STATUS_ID = " . TRANSACTION_SALE_SUCCESS_STATUS_ID, "Transaction");
            if ($statusID == TRANSACTION_SALE_SUCCESS_STATUS_ID) {
                $trans->save();
            } else {
                $trans->save(RNCPHP\RNObject::SuppressAll);
            }

            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "Transaction");
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "Transaction");
        }
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, ":transaction status updated:" . $statusString, "Transaction");
        return true;
    }

    /**
     * Adds a new note to the passed transaction
     */
    public function addNoteToTrans($trans, $noteContent)
    {
        logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        logMessage("note = " . $noteContent);
        try {
            if (!$trans instanceof RNCPHP\financial\transactions) {
                $trans = $this->get_transaction($trans);
            }
            if (!$trans instanceof RNCPHP\financial\transactions) {
                logMessage(__FUNCTION__ . "@" . __LINE__ . "  303");
                return;
            }

            if (is_null($noteContent) || strlen($noteContent) < 1) {
                logMessage(__FUNCTION__ . "@" . __LINE__ . "  308");
                return;
            }
            $f_count = count($trans->Notes);
            logMessage(__FUNCTION__ . "@" . __LINE__ . "  312");
            if ($f_count == 0) {
                //$trans -> Notes = new RNCPHP\NoteArray();
                logMessage(__FUNCTION__ . "@" . __LINE__ . "  315");
            }
            //$trans -> Notes[$f_count] = new RNCPHP\Note();
            logMessage(__FUNCTION__ . "@" . __LINE__ . "  318");
            //$trans -> Notes[$f_count] -> Text = $noteContent;
            logMessage(__FUNCTION__ . "@" . __LINE__ . "  320");
        } catch (Exception $e) {
            logMessage(__FUNCTION__ . "@" . __LINE__ . $e->getMessage());
        } catch (RNCPHP\ConnectAPIError $e) {
            logMessage("RNCPHP Exception: " . $e->getMessage());
        }
        //$trans->save();
        return $trans;
    }

    /**
     * Utility function to verify trans viewing based on contact ID
     * @param  $trans A Connect transaction object.
     * @return bool True if contact is allowed to read the transaction, false otherwise
     */
    protected function isContactAllowedToReadTransaction(RNCPHP\financial\transactions $trans)
    {
        return true;
        //logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        $contactID = $this->CI->session->getSessionData('theRealContactID');
        if (is_null($contactID) || !is_numeric($contactID) || $contactID < 1) {
            $contactID = $this->CI->session->getProfileData('contactID');
        }
        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Session Contact ID: " . $contactID);
        if (!Framework::isValidID($contactID)) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Contact not valid");
            return false;
        }
        if (!Framework::isLoggedIn()) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Contact not logged in");
            return false;
        }
        //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Trans C_id: " . $trans -> contact -> ID);
        if ($trans->contact->ID === $contactID) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Contact allowed access");
            return true;
        }

        return false;
    }

    private function _logToFile($lineNum, $message)
    {

        //    $hundredths = ltrim(microtime(), "0");

        //     $fp = fopen('/tmp/transactionLogs_'.date("Ymd").'.log', 'a');
        //     fwrite($fp,  date('H:i:s.').$hundredths.": Transaction Model @ $lineNum : ".$message."\n");
        //     fclose($fp);

    }
}
