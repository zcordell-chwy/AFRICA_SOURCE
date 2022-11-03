<?php

namespace Custom\Models;

use RightNow\Connect\v1_3 as RNCPHP;
use RightNow\Utils\Framework;

require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI('cp_082022_user', '$qQJ616xWWJ9lXzb$');

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
        $this->CI->load->helper('log');
    }

    /**
     * acts as a lock against multiple charge attempts.  If the transaction processing is set to true, no attempts to access it will succeed.
     */
    public function startProcessingTransaction($t_id, $transactionType = NULL)
    {

        helplog(__FILE__, __FUNCTION__ . __LINE__, ": Looking for transaction:" . $t_id . " with transaction type" . $transactionType, "");
        $trans = $this->get_transaction($t_id);

        if (!$trans instanceof RNCPHP\financial\transactions) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": No Transaction found", "");
            return false;
        }

        $status = $trans->currentStatus->ID;

        if (is_null($status) || $status < 1) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, " Could not determin status", "");
            return false;
        }


        switch ($transactionType) {
            case FS_SALE_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedChargeStatus)) {
                    helplog(__FILE__, __FUNCTION__ . __LINE__, ": Invalid transaction status: " . $status, "");
                    return false;
                }
                break;
            case FS_REFUND_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedRefundStatus)) {
                    helplog(__FILE__, __FUNCTION__ . __LINE__, ": Invalid transaction status: " . $status, "");
                    return false;
                }
                break;
            case FS_REVERSAL_TYPE:
                if (!$this->isAllowedStatus($status, $this->allowedReversalStatus)) {
                    helplog(__FILE__, __FUNCTION__ . __LINE__, ": Invalid transaction status: " . $status, "");
                    return false;
                }
                break;
            default:
                $statusIdx = false;
                break;
        }

        try {
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": Setting Transaction status to " . TRANSACTION_PROCESSING_STATUS_ID, "");
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(TRANSACTION_PROCESSING_STATUS_ID);
            $trans->save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        }
        helplog(__FILE__, __FUNCTION__ . __LINE__, ": Returning True", "");
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
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        }
        return $trans->ID;
    }

    /**
     *
     */
    public function update_transaction($t_id, $c_id, $amt = -1, $desc = null, $donationId = null, $statusString = null, $paymentMethodId = -1, $PNRef = null)
    {
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Begin Update transaction", ""); //133,
        helplog(__FILE__, __FUNCTION__ . __LINE__, "DonationId:$donationId Amt:$amt ContactId:$c_id TransactionAmount:$t_id Status:$statusString NewPayId:$paymentMethodId PnRef:" . $PNRef, "");
        if (strlen($desc) > 254) {
            $desc = substr($desc, 0, 251) . "...";
        }
        try {
            if (!is_null($t_id) && is_numeric($t_id) && $t_id > 0) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "TransID:$t_id", "");
                $trans = $this->get_transaction(intval($t_id));
            } else {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Returning False", "");
                return false;
            }
            if (!$trans instanceof RNCPHP\financial\transactions) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Returning False", "");
                return false;
            }
            $transMeta = RNCPHP\financial\transactions::getMetaData();
            $trans->currentStatus = RNCPHP\financial\transaction_status::fetch(DEFAULT_TRANSACTION_STATUS_ID);
            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($trans->currentStatus, true), "");
            if ($amt > 0) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, number_format($amt, 2, '.', ''), "");
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
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Updating Transaction: statusstring = $statusString and TRANSACTION_SALE_SUCCESS_STATUS = " . TRANSACTION_SALE_SUCCESS_STATUS, "");
            if ($statusString == TRANSACTION_SALE_SUCCESS_STATUS) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "");
                $trans->save();
                helplog(__FILE__, __FUNCTION__ . __LINE__, "not suppressing", "");
            } else {
                $trans->save(RNCPHP\RNObject::SuppressAll);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "suppressing", "");
            }
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        }
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Returning:" . $trans->ID, "");
        return $trans->ID;
    }

    public function addDonationToTransaction($trans_id, $donation_id)
    {
        $trans = $this->get_transaction($trans_id);
        if ($trans == false) {
            return false;
        }
        try {
            $trans->donation = intval($donation_id);
            $trans->save(RNCPHP\RNObject::SuppressAll);
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function get_transaction($t_id)
    {

        try {
            $roql = sprintf("SELECT financial.transactions FROM financial.transactions where ID = %s", $t_id);
            $qo = RNCPHP\ROQL::queryObject($roql)->next();
            $tr = $qo->next();
        } catch (\Exception $e) {
            return false;
        }
        if (!$tr instanceof RNCPHP\financial\transactions) {
            return false;
        }

        if (isset($tr->contact)) {
            if ($this->isContactAllowedToReadTransaction($tr) !== true) {
                return false;
            }
        } else {
            return false;
        }

        return $tr;
    }

    public function updateTransStatus($tran_id, $statusID = null, $paymentMethodId = null, $PNRef = null)
    {
        $trans = $this->get_transaction($tran_id);
        if (!$trans instanceof RNCPHP\financial\transactions) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, " invalid type", "");
            return false;
        }
        if (!is_null($paymentMethodId) && $paymentMethodId > 0) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "valid paymethod id = $paymentMethodId", "");
            $trans->paymentMethod = $paymentMethodId;
        }

        if ($trans->currentStatus->ID == $statusID) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "returning true", "");
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

            helplog(__FILE__, __FUNCTION__ . __LINE__, "Updating Transaction: statusID = $statusID and TRANSACTION_SALE_SUCCESS_STATUS_ID = " . TRANSACTION_SALE_SUCCESS_STATUS_ID, "");
            if ($statusID == TRANSACTION_SALE_SUCCESS_STATUS_ID) {
                $trans->save();
            } else {
                $trans->save(RNCPHP\RNObject::SuppressAll);
            }

            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            return false;
        } catch (RNCPHP\ConnectAPIError $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
        }
        helplog(__FILE__, __FUNCTION__ . __LINE__, ":transaction status updated:" . $statusString, "");
        return true;
    }

    /**
     * Adds a new note to the passed transaction
     */
    public function addNoteToTrans($trans, $noteContent)
    {
        helplog(__FILE__, __FUNCTION__ . __LINE__, "note = " . print_r($noteContent,true), "");
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
            if ($f_count == 0) {
                //$trans -> Notes = new RNCPHP\NoteArray();
            }
            //$trans -> Notes[$f_count] = new RNCPHP\Note();
            //$trans -> Notes[$f_count] -> Text = $noteContent;
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "RNCPHP Exception: ", $e->getMessage());
        } catch (RNCPHP\ConnectAPIError $e) {
            helplog(__FILE__,__FUNCTION__.__LINE__,"RNCPHP Exception: ", $e->getMessage());
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
        if ($trans->contact->ID === $contactID) {
            //logMessage(__FUNCTION__ . "@" . __LINE__ . ": TR MOD Contact allowed access");
            return true;
        }

        return false;
    }
}
