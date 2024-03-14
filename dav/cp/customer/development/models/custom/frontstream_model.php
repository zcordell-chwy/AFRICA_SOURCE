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
        if (!extension_loaded('curl')) {
            load_curl();
        }
        // load_curl();
        $this->CI->load->helper('constants');
        $this->CI->load->helper('log');
        //This model would be loaded by using $this->load->model('custom/frontstream_model');

    }

    public function getEndUserErrorMsg()
    {
        logMessage("\n", $this->endUserError);
        return $this->endUserError;
    }

    public function GuestPaymentProcess($data = "", $host = "")
    {
        try {
            logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));

            logMessage("GuestPaymentProcess Front Stream Request : " . print_r($data, true));
            logMessage("GuestPaymentProcess Front Stream Host URL : " . print_r($host, true));
            // Initialize Curl and send the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $result = curl_exec($ch);
            helplog(__FILE__, __FUNCTION__ . __LINE__,  "runCurl - result:", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, $result, "");

            if (curl_errno($ch)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - Curl Error");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", curl_error($ch));
                logMessage("GuestPaymentProcess Front Stream Host URL : " . print_r(curl_error($ch), true));
                $this->errorMessage[] = curl_error($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                curl_close($ch);
                return false;
            } else if (strpos($result, "HTTP Error") !== false) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - http error");
                logMessage("GuestPaymentProcess Front Stream Host URL : " . print_r('runCurl - http error', true));
                curl_close($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
        } catch (\Exception $e) {
            curl_close($ch);
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
            $this->errorMessage[] = $e->getMessage();
            $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
            return false;
        }

        logMessage("GuestPaymentProcess Front Stream Response : " . print_r($result, true));

        curl_close($ch);
        return $result;
    }

    public function ProcessPayment($transactionId, RNCPHP\financial\paymentMethod $paymentMethod, $amount = "0", $transType = "",$ccard="", $cvv="",$expdate="", $isguest=false, $ipaddress="",$savedcard=false)
    {
        logMessage(__FUNCTION__ . "@" . __LINE__ . " args: " . print_r(func_get_args(), true));
        try {


            if (!$this->verifyPositiveInt($transactionId)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Failed");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                logMessage("ProcessPayment 1 =>: " . print_r(getConfig(CUSTOM_CFG_general_cc_error_id, true)));
                return false;
            }
            if (!$this->verifyPositiveInt($amount)) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", " -- could not verify positive amount. amount = " . $amount);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                logMessage("ProcessPayment 2 =>: " . print_r(" -- could not verify positive amount. amount = " . $amount, true));
                return false;
            }
            if (strlen($paymentMethod->PN_Ref) < 1 && strlen($paymentMethod->InfoKey) < 1) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "Failed");
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                logMessage("ProcessPayment 3 =>: " . print_r(getConfig(CUSTOM_CFG_general_cc_error_id, true)));
                return FALSE;
            }
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Passed Sanity Checks Continuing to process transaction for:" . $transactionId, "");
            if ($transactionId)
                if ($this->CI->model('custom/transaction_model')->startProcessingTransaction($transactionId, $transType) !== true) {
                    helplog(__FILE__, __FUNCTION__ . __LINE__, "", ": Did not pass processing");
                    $this->endUserError[] = "Transaction already in progress.";
                    logMessage("ProcessPayment 4 =>: " . print_r(": Did not pass processing", true));
                    return false;
                }
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": Getting Transaction" . $transactionId, "");
            $trans = $this->CI->model('custom/transaction_model')->get_transaction($transactionId);
            if (!$trans instanceof RNCPHP\financial\transactions) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "Non-valid transaction", "");
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($trans, true), "");
                logMessage("ProcessPayment 5 =>: " . print_r("Non-valid transaction", true));
                return false;
            }
            helplog(__FILE__, __FUNCTION__ . __LINE__, ": Getting Contact", "");
            $contact = $this->CI->model('contact')->get()->result;
            if (is_null($contact) && !is_null($this->CI->session->getSessionData('contact_id'))) {
                $contact = RNCPHP\Contact::fetch(intval($this->CI->session->getSessionData('contact_id')));
            }
            if (!$contact->ID || $contact->ID < 1) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, ": Failed to get contact", "",$contact->ID);
                $this->endUserError[] = "Unable to access donor information.";
                logMessage("ProcessPayment 5 =>: " . print_r("Failed to get contact", true));
                return false;
            }

            helplog(__FILE__, __FUNCTION__ . __LINE__, " :Retreived Contact " . $contact->ID, "",$contact->ID);
            logMessage("Transaction Type : " . $transType);

            helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($paymentMethod->PaymentMethodType->ID, true), "",$contact->ID);
            //need to choose submission values based on type of payment method.  2=EFT 1=CC
            if ($paymentMethod->PaymentMethodType->ID == 1) {
                if ($isguest && strlen($paymentMethod->InfoKey) > 1 && !$savedcard) {
                    if($cvv!=''){
                     $extdata="<CVPresence>Submitted Illegible</CVPresence>";

                    }else{
                        $extdata=''; 
                    }
                    if($ipaddress!="") $extdata .="<API_IP>" . $ipaddress . "</API_IP>";
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
                        'TransType' => $transType,
                        'PNRef' => '',//$paymentMethod->PN_Ref'',
                        'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                        'MagData' => '',
                        'ExtData' => '',
                        'CardNum' => $ccard,
                        'ExpDate' => $expdate,
                        'CVNum' => $cvv,//'',
                        'InvNum' => $transactionId,
                        'NameOnCard' => '',
                        'Zip' => '',
                        'Street' => '',
                        'ExtData' => $extdata
                    );
                } else if (strlen($paymentMethod->PN_Ref) > 1 && strlen($paymentMethod->InfoKey) < 1 && !$isguest && $savedcard) {
                    if($cvv!=''){
                     $extdata="<CVPresence>Submitted Illegible</CVPresence>";

                    }else{
                        $extdata=''; 
                    }
                    if($ipaddress!="") $extdata .="<API_IP>" . $ipaddress . "</API_IP>";
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
                        'TransType' => $transType,
                        'PNRef' => $paymentMethod->PN_Ref,
                        'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                        'MagData' => '',
                        'ExtData' => '',
                        'CardNum' => $ccard,
                        'ExpDate' => $expdate,
                        'CVNum' => $cvv,//'',
                        'InvNum' => $transactionId,
                        'NameOnCard' => '',
                        'Zip' => '',
                        'Street' => '',
                        'ExtData' => $extdata
                    );
                }else if (strlen($paymentMethod->InfoKey) > 1 && strlen($paymentMethod->PN_Ref) < 1 && !$isguest && $savedcard) {
                    if($cvv!=''){
                        $extdata="<CVPresence>Submitted Illegible</CVPresence>";
   
                       }else{
                           $extdata=''; 
                       }
                       if($ipaddress!="") $extdata .="<API_IP>" . $ipaddress . "</API_IP>";
                       $submissionVals = array(
                           'Amount' => $amount,
                           'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                           'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
                           'TransType' => $transType,
                           'PNRef' => '',//$paymentMethod->PN_Ref'',
                           'op' => "ArgoFire/transact.asmx/ProcessCreditCard",
                           'MagData' => '',
                           'ExtData' => '',
                           'CardNum' => '',
                           'ExpDate' => '',
                           'CVNum' => $cvv,//'',
                           'InvNum' => $transactionId,
                           'NameOnCard' => '',
                           'Zip' => '',
                           'Street' => '',
                           'ExtData' => $extdata.'<CC_Info_Key>' . $paymentMethod->InfoKey . '</CC_Info_Key>'
                       );

                }
                else if (strlen($paymentMethod->InfoKey) > 1 && strlen($paymentMethod->PN_Ref) < 1 && !$isguest && !$savedcard) {
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
                        'CcInfoKey' => $paymentMethod->InfoKey,
                        'op' => "admin/ws/recurring.asmx/ProcessCreditCard",
                        'Vendor' => getConfig(CUSTOM_CFG_frontstream_vendor),
                        'ExtData' => '',
                        'InvNum' => $transactionId
                    );
                }
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($paymentMethod, true), "",$contact->ID);
            } else {
                if (strlen($paymentMethod->InfoKey) < 1 && strlen($paymentMethod->PN_Ref) > 1) {
                    $extdata = '<InvNum>' . $transactionId . '</InvNum><PNRef>' . $paymentMethod->PN_Ref . '</PNRef><Check_Info_Key>' . $paymentMethod->InfoKey . '</Check_Info_Key>';
                    if($ipaddress!="") $extdata .="<API_IP>" . $ipaddress . "</API_IP>";
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
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
                        'ExtData' => $extdata
                    );
                } else if (strlen($paymentMethod->InfoKey) > 1 && strlen($paymentMethod->PN_Ref) < 1) {
                    $extdata = "";
                    if($ipaddress!="") $extdata .="<API_IP>" . $ipaddress . "</API_IP>";
                    $submissionVals = array(
                        'Amount' => $amount,
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
                        'op' => "admin/ws/recurring.asmx/ProcessCheck",
                        'Vendor' => getConfig(CUSTOM_CFG_frontstream_vendor),
                        'CheckInfoKey' => $paymentMethod->InfoKey,
                        'InvNum' => $transactionId,
                        'ExtData' => ''
                    );
                }
            }
            helplog(__FILE__, __FUNCTION__ . __LINE__, "FS Post Vals" . print_r($submissionVals, true), "",$contact->ID);

            $returnVal = $this->runTransaction($submissionVals, $trans);
            if (count($this->errorMessage) > 0) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($this->errorMessage, true), "",$contact->ID);
            }

            //don't set trans to complete here.  it doesn't have pledges or a donation associated to it yet and we
            //fire the CPM when transaciton is complete.

            if ($returnVal['isSuccess'] === true) {
                //$this -> CI -> model('custom/transaction_model') -> updateTransStatus($transactionId, TRANSACTION_SALE_SUCCESS_STATUS_ID, $paymentMethod -> ID);
                helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($returnVal, true), "",$contact->ID);
                return $returnVal;
            } else {
                $this->CI->model('custom/transaction_model')->updateTransStatus($transactionId, DEFAULT_TRANSACTION_STATUS_ID);
                return false;
            }
        } catch (\Exception $ex) {
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
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
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
                        'Password' => getConfig(CUSTOM_CFG_FS_PW_CP),
                        'UserName' => getConfig(CUSTOM_CFG_FS_UN_CP),
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
        } catch (\Exception $e) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "", $e->getMessage());
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
        //using id's due to https://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912
        $host = getConfig(CUSTOM_CFG_frontstream_endpoint);
        logMessage("Front Stream Request Endpoint: " . $host);
        if (!$this->verifyMinTransReqs($postVals, $host, $user, $pass)) {
            $this->endUserError[] = "Unable to run payment";
            logMessage("RunTransaction => Unable to run payment");
            return false;
        }

        $host .= "/" . $postVals['op'];

        $mybuilder = array();
        foreach ($postVals as $key => $value) {
            $mybuilder[] = $key . '=' . $value;
        }
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Endpoint: " . $host, "");
        helplog(__FILE__, __FUNCTION__ . __LINE__, "Front Stream Request : " . print_r($mybuilder, true), "");
        $result = $this->runCurl($host, $mybuilder);
        if ($result == false) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Unable to run payment", "");
            $this->endUserError[] = "Unable to run payment";
            return false;
        }

        $parsedResponse = $this->parseFrontstreamResp($result, $trans);
        if ($parsedResponse['isSuccess']) {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Parsed Response" . print_r($parsedResponse, true), "");
            return $parsedResponse;
        } else {
            helplog(__FILE__, __FUNCTION__ . __LINE__, "Parsed Response" . print_r($parsedResponse, true), "");
            $this->endUserError[] = "The payment was declined: ";
            //$this->endUserError[] = $parsedResponse['message'] . "  " . $parsedResponse['responseMsg'];
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
            logMessage("Front Stream Curl Response : " . print_r($result, true));
            helplog(__FILE__, __FUNCTION__ . __LINE__,  "runCurl - result:", "");
            helplog(__FILE__, __FUNCTION__ . __LINE__, $result, "");


            if (curl_errno($ch) > 0) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - Curl Error");
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", curl_error($ch));
                $this->errorMessage[] = curl_error($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                curl_close($ch);
                return false;
            } else if (strpos($result, "HTTP Error") !== false) {
                helplog(__FILE__, __FUNCTION__ . __LINE__, "", "runCurl - http error");
                curl_close($ch);
                $this->endUserError[] = getConfig(CUSTOM_CFG_general_cc_error_id);
                return false;
            }
        } catch (\Exception $e) {
            curl_close($ch);
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
        //using id's due to https://communities.rightnow.com/posts/3a27a1b48d?commentId=33912#33912

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
        $frontStreamResponse['code'] = $values[$indices['CODE'][0]]['value'];
        $frontStreamResponse['error'] = $values[$indices['ERROR'][0]]['value'];


        if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['error'] == "APPROVED") { //cc - with ccinfokey
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['message'] == "Pending") { // Added this check for by passing the Check transactions with Pending status
            logMessage("Pending Transaction >> Success");
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0 && $frontStreamResponse['responseMsg'] == "Approved") { //exception with pnref
            $frontStreamResponse['isSuccess'] = true;
        } else if ($frontStreamResponse['resultCode'] == 0) { //assuming all is well here
            $frontStreamResponse['isSuccess'] = true;
        } else {
            $frontStreamResponse['isSuccess'] = false;
        }

        helplog(__FILE__, __FUNCTION__ . __LINE__, print_r($frontStreamResponse, true), "");

        if ($trans->ID > 0)
            $this->CI->model('custom/transaction_model')->addNoteToTrans($trans, print_r($result, TRUE));
        $frontStreamResponse['rawXml'] = $result;
        $frontStreamResponse['parsedXml'] = $values;
        $this->parsedFrontstreamResp = $frontStreamResponse;

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
