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
        $this->CI->load->helper('log');
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
                // $this->_logToFile(39, __FUNCTION__ . " Failed");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Failed");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
            if (!$this->verifyPositiveInt($amount)) {
                // $this->_logToFile(44, __FUNCTION__ . " -- could not verify positive amount. amount = " . $amount);
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", " -- could not verify positive amount. amount = " . $amount);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
            if (strlen($paymentMethod->PN_Ref) < 1) {
                // $this->_logToFile(49, __FUNCTION__ . "Failed");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Failed");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return FALSE;
            }
            // $this->_logToFile(53, __FUNCTION__ . "Passed Sanity Checks Continuing to process transaction for:" . $transactionId);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Passed Sanity Checks Continuing to process transaction for:" . $transactionId, "");
            if ($transactionId)
                if ($this->CI->model('custom/transaction_model')->startProcessingTransaction($transactionId, $transType) !== true) {
                    // $this->_logToFile(57, __FUNCTION__ . ": Did not pass processing");
                    helplog(__FILE__, __FUNCTION__ . __LINE__, "", ": Did not pass processing");
                    $this->endUserError[] = "Transaction already in progress.";
                    return false;
                }
            // $this->_logToFile(61, __FUNCTION__ . ": Getting Transaction" . $transactionId);
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": Getting Transaction" . $transactionId, "");
            $trans = $this->CI->model('custom/transaction_model')->get_transaction($transactionId);
            if (!$trans instanceof RNCPHP\financial\transactions) {
                // $this->_logToFile(64, "Non-valid transaction");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Non-valid transaction", "");
                // $this->_logToFile(65, print_r($trans, true));
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($trans, true), "");
                return false;
            }
            // $this->_logToFile(68, __FUNCTION__ . ": Getting Contact");
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": Getting Contact", "");
            $contact = $this->CI->model('contact')->get()->result;

            if (!$contact instanceof RNCPHP\Contact) {
                // $this->_logToFile(72, __FUNCTION__ . ": Failed to get contact");
                helplog(__FILE__, __FUNCTION__ . __LINE__, ": Failed to get contact", "");
                $this->endUserError[] = "Unable to access donor information.";
                return false;
            }
            // $this->_logToFile(76, __FUNCTION__ . " :Retreived Contact " . $contact->ID);
            helplog(__FILE__, __FUNCTION__ . __LINE__, " :Retreived Contact " . $contact->ID, "");
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
            // $this->_logToFile(116, "FS Post Vals");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "FS Post Vals", "");
            // $this->_logToFile(117, print_r($submissionVals, true));
            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($submissionVals, true), "");


            $returnVal = $this->runTransaction($submissionVals, $trans);
            if (count($this->errorMessage) > 0) {
                // $this->_logToFile(112, print_r($this->errorMessage, true));
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($this->errorMessage, true), "");
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
            // $this->_logToFile(139, $ex->getMessage());
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $ex->getMessage());
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
        // $this->_logToFile(251, "Endpoint: " . $host);
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Endpoint: " . $host, "");
        // $this->_logToFile(252, "Post Data:" . print_r($mybuilder, true));
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Post Data:" . print_r($mybuilder, true), "");
        $result = $this->runCurl($host, $mybuilder);
        if ($result == false) {
            // $this->_logToFile(255, "Unable to run payment");
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Unable to run payment", "");
            $this->endUserError[] = "Unable to run payment";
            return false;
        }

        $parsedResponse = $this->parseFrontstreamResp($result, $trans);
        // $this->_logToFile(261, "Parsed Response");
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Parsed Response", "");
        // $this->_logToFile(262, print_r($parsedResponse, true));
        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($parsedResponse, true), "");
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
            // $this->_logToFile(282, "runCurl - result:");
            helplog(__FILE__, __FUNCTION__ . __LINE__,  "runCurl - result:", "");
            // $this->_logToFile(283, $result);
            helplog(__FILE__, __FUNCTION__ . __LINE__, $result, "");


            if (curl_errno($ch) > 0) {
                // $this->_logToFile(287, "runCurl - Curl Error");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - Curl Error");
                // $this->_logToFile(288, curl_error($ch));
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", curl_error($ch));
                $this->errorMessage[] = curl_error($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                curl_close($ch);
                return false;
            } else if (strpos($result, "HTTP Error") !== false) {
                // $this->_logToFile(294, "runCurl - http error");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - http error");
                curl_close($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
        } catch (Exception $e) {
            curl_close($ch);
            // $this->_logToFile(301, $e->getMessage());
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
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
        //$this->_logToFile(__LINE__, __FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        //using id's due to http://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912

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
        //$this->_logToFile(__LINE__, $result);
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

        //$this->_logToFile(__LINE__, "front stream response");
        //$this->_logToFile(__LINE__, print_r($frontStreamResponse, true));
        return $frontStreamResponse;
    }

    private function _logToFile($lineNum, $message)
    {

        $hundredths = ltrim(microtime(), "0");

        $fp = fopen('/tmp//esgLogPayCron/pledgeLogs_' . date("Ymd") . '.log', 'a');
        fwrite($fp,  date('H:i:s.') . $hundredths . ": FrontStream model @ $lineNum : " . $message . "\n");
        fclose($fp);
    }
}
