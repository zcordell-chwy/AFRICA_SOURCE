<?php

namespace Custom\Models;

use \RightNow\Connect\v1_3 as RNCPHP;
use RightNow\Utils\Framework;
use RightNow\Utils\Config;
use RightNow\Models\Contact;

require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

class frontstream_model  extends \RightNow\Models\Base
{

    private $errorMessage = array();
    private $endUserError = array();
    public $authCode;
    public $parsedFrontstreamResp;

    function __construct()
    {
        parent::__construct();
        initConnectAPI();
        load_curl();
        $this->CI->load->helper('constants');
        //This model would be loaded by using $this->load->model('custom/frontstream_model');

    }

    public function getEndUserErrorMsg()
    {
        logMessage("\n", $this->endUserError);
        return $this->endUserError;
    }

    public function ProcessPayment($transactionId, RNCPHP\financial\paymentMethod $paymentMethod, $amount = "0", $transType = "")
    {
        logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        try {


            if (!$this->verifyPositiveInt($transactionId)) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, " Failed", "FrontStream");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
            if (!$this->verifyPositiveInt($amount)) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, " -- could not verify positive amount. amount = " . $amount, "FrontStream");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
            if (strlen($paymentMethod->PN_Ref) < 1) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Failed", "FrontStream");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return FALSE;
            }
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Passed Sanity Checks Continuing to process transaction for:" . $transactionId, "FrontStream");

            if ($transactionId)
                if ($this->CI->model('custom/transaction_model')->startProcessingTransaction($transactionId, $transType) !== true) {
                    $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Did not pass processing", "FrontStream");
                    $this->endUserError[] = "Transaction already in progress.";
                    return false;
                }

            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Getting Transaction" . $transactionId, "FrontStream");
            $trans = $this->CI->model('custom/transaction_model')->get_transaction($transactionId);
            if (!$trans instanceof RNCPHP\financial\transactions) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Non-valid transaction", "FrontStream");

                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($trans, true), "FrontStream");

                return false;
            }
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Getting Contact", "FrontStream");
            $contact = $this->CI->model('contact')->get()->result;

            if (!$contact instanceof RNCPHP\Contact) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Failed to get contact", "FrontStream");
                $this->endUserError[] = "Unable to access donor information.";
                return false;
            }
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Retreived Contact " . $contact->ID, "FrontStream");
            //need to choose submission values based on type of payment method.  2=EFT 1=CC
            if ($paymentMethod->PaymentMethodType->ID == 1) {
                $submissionVals = array(
                    'Amount' => $amount,
                    'Password' => getConfig(CUSTOM_CFG_frontstream_pass_id),
                    'UserName' => getConfig(CUSTOM_CFG_frontstream_user),
                    'TransType' => $transType,
                    'PNRef' => $paymentMethod->PN_Ref,
                    'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                    'MagData' => '',
                    'ExtData' => '',
                    'CardNum' => '',
                    'ExpDate' => '',
                    'CVNum' => '',
                    'InvNum' => $transactionId,
                    'NameOnCard' => '',
                    'Zip' => '',
                    'Street' => ''
                );
            } else {
                $submissionVals = array(
                    'Amount' => $amount,
                    'Password' => getConfig(CUSTOM_CFG_frontstream_pass_id),
                    'UserName' => getConfig(CUSTOM_CFG_frontstream_user),
                    'TransType' => 'RepeatSale',
                    'op' => "ArgoFire/transact.asmx/ProcessCheck",
                    'CheckNum' => '',
                    'TransitNum' => '',
                    'AccountNum' => '',
                    'NameOnCheck' => '',
                    'MICR' => '',
                    'DL' => '',
                    'SS' => '',
                    'DOB' => '',
                    'StateCode' => '',
                    'CheckType' => '',
                    'ExtData' => '<InvNum>' . $transactionId . '</InvNum><PNRef>' . $paymentMethod->PN_Ref . '</PNRef>'
                );
            }
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "FS Post Vals", "FrontStream");
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($submissionVals, true), "FrontStream");


            $returnVal = $this->runTransaction($submissionVals, $trans);
            if (count($this->errorMessage) > 0) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($this->errorMessage, true), "FrontStream");
            }

            //don't set trans to complete here.  it doesn't have pledges or a donation associated to it yet and we
            //fire the CPM when transaciton is complete.

            if ($returnVal['isSuccess'] === true) {
                //$this -> CI -> model('custom/transaction_model') -> updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $paymentMethod -> ID);

                return $returnVal;
            } else {
                $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, DEFAULT_TRANSACTION_STATUS_ID);
                return false;
            }
        } catch (Exception $ex) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $ex->getMessage(), "FrontStream");
            return false;
        }
    }

    //this method does not need an associated transaction.
    //will be used to reverse small payment done to add a new payment method at /app/paymentmethods
    public function ReversePayment($pnref, $transType)
    {

        try {
            if ($pnref) {

                if ($transType == 'card') {
                    $submissionVals = array(
                        'Amount' => '',
                        'Password' => getConfig(CUSTOM_CFG_frontstream_pass_id),
                        'UserName' => getConfig(CUSTOM_CFG_frontstream_user),
                        'TransType' => 'Reversal',
                        'PNRef' => $pnref,
                        'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                        'MagData' => '',
                        'ExtData' => '',
                        'CardNum' => '',
                        'ExpDate' => '',
                        'CVNum' => '',
                        'InvNum' => '',
                        'NameOnCard' => '',
                        'Zip' => '',
                        'Street' => ''
                    );
                } else {
                    $submissionVals = array(
                        'Amount' => '',
                        'Password' => getConfig(CUSTOM_CFG_frontstream_pass_id),
                        'UserName' => getConfig(CUSTOM_CFG_frontstream_user),
                        'TransType' => 'Void',
                        'op' => "ArgoFire/transact.asmx/ProcessCheck",
                        'CheckNum' => '',
                        'TransitNum' => '',
                        'AccountNum' => '',
                        'NameOnCheck' => '',
                        'MICR' => '',
                        'DL' => '',
                        'SS' => '',
                        'DOB' => '',
                        'StateCode' => '',
                        'CheckType' => '',
                        'ExtData' => '<PNRef>' . $pnref . '</PNRef>'
                    );
                }
            } else {
                $this->endUserError[] = "No valid PNRef was supplied to reverse the charge.";
                return false;
            }

            logMessage("in reverse payment");
            logMessage($submissionVals);

            //creating a dummy transaction just to get it to pass through runTransaction and parseFrontstreamResp
            $trans = new RNCPHP\financial\transactions;

            $returnVal = $this->runTransaction($submissionVals, $trans);

            logMessage("returned data from runtransaction:");
            logMessage($returnVal);

            if ($returnVal['isSuccess'] === true) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
        }
    }

    private function verifyPositiveInt($testVal)
    {
        if (is_null($testVal)) {
            return false;
        }
        if (!is_int($testVal)) {
            return false;
        }
        if ($testVal < 1) {
            return false;
        }
        return true;
    }

    /**
     *
     * Set isChargingCard to false to not add a ref number to the transaction.  This is useful for informational transactions and setting up recurring transactions
     *
     */
    private function runTransaction(array $postVals, RNCPHP\financial\transactions $trans)
    {
        logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912
        $host = getConfig(CUSTOM_CFG_frontstream_endpoint_id);
        if (!$this->verifyMinTransReqs($postVals, $host, $user, $pass)) {
            $this->endUserError[] = "Unable to run payment";
            return false;
        }

        $host .= "/" . $postVals['op'];

        $mybuilder = array();
        foreach ($postVals as $key => $value) {
            $mybuilder[] = $key . '=' . $value;
        }
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Endpoint: " . $host, "FrontStream");
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Post Data:" . print_r($mybuilder, true), "FrontStream");
        $result = $this->runCurl($host, $mybuilder);
        if ($result == false) {
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Post Data:" . "Unable to run payment", "FrontStream");
            $this->endUserError[] = "Unable to run payment";
            return false;
        }

        $parsedResponse = $this->parseFrontstreamResp($result, $trans);
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "Parsed Response", "FrontStream");
        $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($parsedResponse, true), "FrontStream");
        if ($parsedResponse['isSuccess']) {
            return $parsedResponse;
        } else {
            $this->endUserError[] = "The payment was declined: ";
            $this->endUserError[] = $parsedResponse['message'] . "  " . $parsedResponse['responseMsg'];
        }
    }

    /**
     * Runs a curl POST call
     */
    private function runCurl($host, array $postData)
    {
        try {
            // Initialize Curl and send the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, print_r($result, true), "FrontStream");

            if (curl_errno($ch) > 0) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "runCurl - Curl Error", "FrontStream");
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, curl_error($ch), "FrontStream");
                $this->errorMessage[] = curl_error($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                curl_close($ch);
                return false;
            } else if (strpos($result, "HTTP Error") !== false) {
                $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, "runCurl - http error", "FrontStream");
                curl_close($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
        } catch (Exception $e) {
            curl_close($ch);
            $this->CI->model('custom/log_model')->log(__FILE__, __FUNCTION__, 0, 0, __LINE__, $e->getMessage(), "FrontStream");
            $this->errorMessage[] = $e->getMessage();
            $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
            return false;
        }
        curl_close($ch);
        return $result;
    }

    /**
     * runs some basic sanity checks on frontstream required data
     */
    private function verifyMinTransReqs($postVals, $host)
    {
        if (is_null($host) || strlen($host) < 1) {
            $this->errorMessage[] = "Invalid host passed to runTransaction";
            return false;
        }
        if (is_null($postVals['UserName']) || strlen($postVals['UserName']) < 1) {
            $this->errorMessage[] = "Invalid user passed to runTransaction";
            return false;
        }

        if (is_null($postVals['Password']) || strlen($postVals['Password']) < 1) {
            $this->errorMessage[] = "Invalid password passed to runTransaction";
            return false;
        }
        if (is_null($postVals) || count($postVals) < 1) {
            $this->errorMessage[] = "Invalid post values passed to runTransaction";
            return false;
        }

        if (is_null($postVals['op'])) {
            $this->errorMessage[] = "Invalid operation.";
            return false;
        }
        return true;
    }

    private function parseFrontstreamResp($result, RNCPHP\financial\transactions $trans)
    {
        logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        logMessage("parse Results");
        $xmlparser = xml_parser_create();
        xml_parse_into_struct($xmlparser, $result, $values, $indices);
        xml_parser_free($xmlparser);

        $frontStreamResponse = array();
        $frontStreamResponse['resultCode'] = $values[$indices['RESULT'][0]]['value'];
        $frontStreamResponse['responseMsg'] = $values[$indices['RESPMSG'][0]]['value'];
        $frontStreamResponse['message'] = $values[$indices['MESSAGE'][0]]['value'];
        $frontStreamResponse['pnRef'] = $values[$indices['PNREF'][0]]['value'];

        $frontStreamResponse['rawXml'] = $result;
        $frontStreamResponse['parsedXml'] = $values;

        if ($frontStreamResponse['resultCode'] == 0) {
            $frontStreamResponse['isSuccess'] = true;
        } else {
            $frontStreamResponse['isSuccess'] = false;
        }

        if ($trans->ID > 0)
            $this->CI->model('custom/transaction_model')->addNoteToTrans($trans, print_r($result, TRUE));

        $this->parsedFrontstreamResp = $frontStreamResponse;
        return $frontStreamResponse;
    }

    private function _logToFile($lineNum, $message)
    {

        //$hundredths = ltrim(microtime(), "0");

        // $fp = fopen('/tmp//esgLogPayCron/pledgeLogs_'.date("Ymd").'.log', 'a');
        // fwrite($fp,  date('H:i:s.').$hundredths.": FrontStream model @ $lineNum : ".$message."\n");
        // fclose($fp);

    }
}
