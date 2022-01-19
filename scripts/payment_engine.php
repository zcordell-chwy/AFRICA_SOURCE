<?php

/**
 * Inbound wrapper API for fs payment
 * @version 1.0
 */
define('DEBUG_MODE', true);

define('FS_SALE_TYPE', 'Sale');
define('FS_EFT_SALE_TYPE', 'RepeatSale');
define('FS_REFUND_TYPE', 'Return');
define('FS_REVERSAL_TYPE', 'Reversal');
define('FS_EFT_REVERSAL_TYPE', 'Void');
define('FS_AUTH_TYPE', 'Auth');

define('CUSTOM_CFG_frontstream_cc_url', '/smartpayments/transact.asmx/ProcessCreditCard');
define('CUSTOM_CFG_frontstream_check_url', '/smartpayments/transact.asmx/ProcessCheck');

$DEVMODE = false;

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath = (isset($debug) && $debug === true) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

define('ALLOW_POST', true);
define('ALLOW_GET', false);

require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';
require_once SCRIPT_PATH . '/utilities/network_utilities_2.php';

try {
    $engine = new PaymentEngine();
    $engine->parseAndPassMessage(file_get_contents('php://input'));
} catch (\Exception $ex) {
    PaymentEngine::logMessage($ex->getMessage());
    return outputResponse(null, $ex->getMessage());
} catch (RNCPHP\ConnectAPIError $ex) {
    PaymentEngine::logMessage($ex->getMessage());
    return outputResponse(null, $ex->getMessage());
}

class PaymentEngine
{
    const LOG_DIR = '/tmp/paymentapi/';
    const LOG_FILE_BASE_NAME = 'PaymentAPI_';

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public static function logMessage($msg, $more = null)
    {
        if (!DEBUG_MODE) {
            return;
        }

        try {
            // Put into unique file per day
            $fileName = self::LOG_DIR . self::LOG_FILE_BASE_NAME . date('Y-m-d') . '.log';
            $timestamp = date('H:i:s') . ': ';
            if (!empty($more)) {
                $msg .= print_r($more, true);
            }

            if (!is_dir(self::LOG_DIR)) {
                $oldumask = umask(0);
                mkdir(self::LOG_DIR, 0775, true);
                umask($oldumask);
            }


            $result = file_put_contents($fileName, $timestamp . $msg . "\n\n", FILE_APPEND);


            if ($result === false) throw new \Exception("Failed to write to log file. File name = $fileName. Timestamp = $timestamp. Msg = $msg.");
        } catch (\Exception $ex) {

            throw new \Exception("Failed to write to log file. File name = $fileName. Timestamp = $timestamp. Msg = $msg.");
        }
    }

    /**
     * parses a message, processes and returns results
     */
    public function parseAndPassMessage($postData)
    {
        self::logMessage(' Starting ' . __FUNCTION__ . '@' . __CLASS__ . '(Line: ' . __LINE__ . ')');
        self::logMessage('Raw POST: ', $postData);

        $inputData = json_decode($postData);
        self::logMessage('Dec POST: ', $inputData);
        if ((!isset($inputData->data)) || (strlen($inputData->data) < 1 && count($inputData->data) < 1)) {
            return outputResponse(null, 'Missing required field: data', 404);
        }

        $action = $inputData->action;
        self::logMessage('action: ', $action);
        if (empty($reqJson)) {
            $reqJson = $inputData->data ? $inputData->data : null;
        }

        switch ($action) {
            case 'PAYMENT':
                return $this->processPayment($reqJson);
                break;

            case 'TOKEN':
                return $this->generateInfoKey($reqJson);
                break;

            default:
                self::logMessage('Invalid value for field: action');
                return outputResponse(null, 'Invalid value for field: action', 405);
                break;
        }
    }

    public function processPayment($reqJson)
    {
        self::logMessage(' Starting ' . __FUNCTION__ . '@' . __CLASS__ . '(Line: ' . __LINE__ . ')');

        if (!empty($reqJson->DEVMODE)) {
            $DEVMODE = $reqJson->DEVMODE;
        }

        $paymentMethod = $reqJson->paymentMethod;
        if (empty($paymentMethod)) {
            self::logMessage('Payment method not found');
            return outputResponse(null, 'Payment method not found', 405);
        }

        $pmType = $paymentMethod->pmType;
        if (empty($pmType)) {
            self::logMessage('Payment method type not found');
            return outputResponse(null, 'Payment method type not found', 405);
        }

        $transType = $reqJson->transType;
        self::logMessage('transType: ', $transType);
        $fsReqData = $this->getFSPostArray($pmType);
        if ($pmType == 'EFT') {

            switch ($transType) {
                case FS_SALE_TYPE:
                    $contact = $reqJson->contact;

                    $fsReqData['Amount'] = $reqJson->amount;
                    $fsReqData['NameOnCheck'] = $contact->firstName . ' ' . $contact->lastName;
                    $fsReqData['InvNum'] = $reqJson->transID;
                    $fsReqData['Zip'] = $contact->postalCode;
                    $fsReqData['Street'] = $contact->street;
                    $fsReqData['TransitNum'] = $paymentMethod->routingNum;
                    $fsReqData['AccountNum'] = base64_decode($paymentMethod->acctNum);
                    $fsReqData['CheckType'] = $reqJson->cardType;
                    break;

                case FS_EFT_SALE_TYPE:
                    $fsReqData['Amount'] = $reqJson->amount;
                    $fsReqData['InvNum'] = $reqJson->transID;
                    $fsReqData['ExtData'] = (!empty($paymentMethod->infoKey)) ? '<Check_Info_Key>' . $paymentMethod->infoKey . '</Check_Info_Key>' : '<PNRef>' . $paymentMethod->pnRef . '</PNRef>';
                    break;

                case FS_REFUND_TYPE:
                    $fsReqData['Amount'] = $reqJson->amount;
                    $fsReqData['InvNum'] = $reqJson->transID;
                    // No Infokey on Refund/Reversal
                    $fsReqData['ExtData'] = '<PNRef>' . $paymentMethod->pnRef . '</PNRef>';
                    break;

                case FS_EFT_REVERSAL_TYPE:
                    $fsReqData['InvNum'] = $reqJson->transID;
                    // No Infokey on Refund/Reversal
                    $fsReqData['ExtData'] = '<PNRef>' . $paymentMethod->pnRef . '</PNRef>';
                    break;

                default:
                    return outputResponse(null, 'Transaction type not supported', 405);
                    break;
            }

            $fsReqData['op'] = CUSTOM_CFG_frontstream_check_url;
            $fsReqData['TransType'] = $transType;
        } else if ($pmType == 'Credit Card') {

            switch ($transType) {
                case FS_SALE_TYPE:
                    // * same transtype for first and repeat sale
                    $fsReqData['Amount'] = $reqJson->amount;
                    $fsReqData['InvNum'] = $reqJson->transID;

                    if (!empty($paymentMethod->ccNum)) {
                        // * it's a new Sale
                        $contact = $reqJson->contact;
                        $fsReqData['NameOnCard'] = $contact->firstName . ' ' . $contact->lastName;
                        $fsReqData['Zip'] = $contact->postalCode;
                        $fsReqData['Street'] = $contact->street;
                        $fsReqData['CardNum'] = base64_decode($paymentMethod->ccNum);
                        $fsReqData['ExpDate'] = $paymentMethod->expMonth . substr($paymentMethod->expYear, 2, 2);
                        $fsReqData['CVNum'] = $DEVMODE ? '' : $paymentMethod->cvc;
                    } else {
                        // * it's a repeat sale
                        if (!empty($paymentMethod->infoKey)) {
                            $fsReqData['ExtData'] = '<CC_Info_Key>' . $paymentMethod->infoKey . '</CC_Info_Key>';
                        } else {
                            $fsReqData['PNRef'] = $paymentMethod->pnRef;
                        }
                    }
                    break;

                case FS_REVERSAL_TYPE:
                    $fsReqData['InvNum'] = $reqJson->transID;
                    // No Infokey on Refund/Reversal
                    // if (!empty($paymentMethod->infoKey)) {
                    //     $fsReqData['ExtData'] = '<CC_Info_Key>' . $paymentMethod->infoKey . '</CC_Info_Key>';
                    // } else {
                    $fsReqData['PNRef'] = $paymentMethod->pnRef;
                    // }
                    break;

                case FS_REFUND_TYPE:
                    $fsReqData['Amount'] = $reqJson->amount;
                    $fsReqData['InvNum'] = $reqJson->transID;
                    // No Infokey on Refund/Reversal
                    // if (!empty($paymentMethod->infoKey)) {
                    //     $fsReqData['ExtData'] = '<CC_Info_Key>' . $paymentMethod->infoKey . '</CC_Info_Key>';
                    // } else {
                    $fsReqData['PNRef'] = $paymentMethod->pnRef;
                    // }
                    break;

                default:
                    return outputResponse(null, 'Transaction type not supported', 405);
                    break;
            }

            $fsReqData['op'] = CUSTOM_CFG_frontstream_cc_url;
            $fsReqData['TransType'] = $transType;
        } else {
            self::logMessage('Invalid value for field: pmType');
            return outputResponse(null, 'Invalid value for field: pmType', 405);
        }

        if (empty($fsReqData)) {
            return outputResponse(null, 'Something went wrong while making the payment request', 405);
        }
        self::logMessage('fsReqData: ', $fsReqData);

        return $this->runTransaction($fsReqData);
    }

    public function generateInfoKey($reqJson)
    {
        self::logMessage(' Starting ' . __FUNCTION__ . '@' . __CLASS__ . '(Line: ' . __LINE__ . ')');

        $paymentMethod = $reqJson->paymentMethod;
        if (empty($paymentMethod)) {
            self::logMessage('Payment method not found');
            return outputResponse(null, 'Payment method not found', 405);
        }

        $pmType = $paymentMethod->pmType;
        if (empty($pmType)) {
            self::logMessage('Payment method type not found');
            return outputResponse(null, 'Payment method type not found', 405);
        }

        $transType = $reqJson->transType;
        if ($pmType == 'EFT') {

            // todo add token logic here
            return outputResponse(true, null, 200);
        } else if ($pmType == 'Credit Card') {

            // todo add token logic here
            return outputResponse(true, null, 200);
        } else {
            self::logMessage('Invalid value for field: pmType');
            return outputResponse(null, 'Invalid value for field: pmType', 405);
        }
    }

    private function getFSPostArray($pmType)
    {
        if ($pmType == 'Credit Card') {
            return [
                'op' => '',
                'UserName' => '',
                'Password' => '',
                'Amount' => '',
                'CardNum' => '',
                'ExpDate' => '',
                'CVNum' => '',
                'NameOnCard' => '',
                'TransType' => '',
                'MagData' => '',
                'InvNum' => '',
                'PNRef' => '',
                'Zip' => '',
                'Street' => '',
                'ExtData' => ''
            ];
        } else if ($pmType == 'EFT') {
            return [
                'op' => '',
                'UserName' => '',
                'Password' => '',
                'Amount' => '',
                'CheckNum' => '',
                'TransitNum' => '',
                'AccountNum' => '',
                'MICR' => '',
                'NameOnCheck' => '',
                'DL' => '',
                'SS' => '',
                'DOB' => '',
                'StateCode' => '',
                'CheckType' => '',
                'ExtData' => ''
            ];
        } else {
            return [];
        }
    }

    function runTransaction(array $postVals)
    {
        self::logMessage(' Starting ' . __FUNCTION__ . '@' . __CLASS__ . '(Line: ' . __LINE__ . ')');

        $url = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_endpoint')->Value . $postVals['op'];
        $postVals['UserName'] = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_user')->Value;
        $postVals['Password'] = RNCPHP\Configuration::fetch('CUSTOM_CFG_frontstream_pass')->Value;

        $postData = array();
        foreach ($postVals as $key => $value) {
            $postData[] = $key . '=' . $value;
        }
        self::logMessage('postData: ', $postData);

        $postStr = implode("&", $postData);
        self::logMessage('postStr: ', $postStr);

        $response = network_utilities\runCurl($url, 'POST', $postStr);
        self::logMessage('response: ', $response);

        if (!empty($response)) {

            if ($response['success']) {
                $returnArr = $this->parseFrontStreamRespOneTime($response['body']);
                self::logMessage('returnArr: ', $returnArr);

                return outputResponse($returnArr, null, $response['status']);
            } else {
                return outputResponse(null, $response['error'], $response['status']);
            }
        } else {
            return outputResponse(null, 'Something went wrong while making the payment request', 500);
        }
    }

    function parseFrontStreamRespOneTime($result)
    {
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

        return $frontStreamResponse;
    }
}
