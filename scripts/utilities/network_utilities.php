<?php

namespace network_utilities {

    use \RightNow\Connect\v1_3 as RNCPHP;

    /**
     * Generic method to make curl requests
     * @param type $endpoint
     * @param type $requestType
     * @param type $postArray
     * @param type $headers header data to send
     * @param type $returnHeaders whether to return response headers with function return response
     * @param type $receiveHeaders - whether to get the headers back from API call in API response
     * @param type $hidePostVal - will force hide the post vals,  used when post contains passwords or PII
     * @param boolean $returnErrors - whether to return the error when something unexpected occurs or return false.
     * @return mixed: response if success, false otherwise
     */

    function runCurl($endpoint, $requestType = "POST", $postArray, $headers, $returnHeaders = false, $receiveHeaders = false, $timeout = 20, $storeCookies = false, $returnErrors = false)
    {

        $responseObj = array();

        if (!function_exists("\curl_init")) {
            \load_curl();
        }
        $curl = curl_init();

        $options = array(
            CURLOPT_URL => $endpoint,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PORT => 443,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HEADER => $receiveHeaders, //enable/disable headers
            CURLOPT_CUSTOMREQUEST => $requestType,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true
        );

        if ($storeCookies) {
            $options['CURLOPT_COOKIEFILE'] = "/tmp/cookieFile";
            $options['CURLOPT_COOKIEJAR'] = "/tmp/cookieFile";
        }

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postArray);
        $responseObj['body'] = curl_exec($curl);
        $responseObj['headers'] = curl_getinfo($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        if ($httpCode > 299 || $httpCode < 99) {

            $logText = sprintf("INVALID HTTPCODE WHILE CALLING %s : %s\r\n", $endpoint, print_r($httpCode, true));
            $logText .= sprintf("ERROR MESSAGE FOR API CALL : %s\r\n", print_r($err, true));
            $logText .= sprintf("SERVER RESPONSE : %s\r\n", print_r($responseObj['body'], true));

            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $logText .= "\n Source IP:" . $_SERVER['HTTP_CLIENT_IP'];
            }
            //whether ip is from proxy
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $logText .= "\n Source IP:" . $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            //whether ip is from remote address
            else {
                $logText .= "\n Source IP:" . $_SERVER['REMOTE_ADDR'];
            }

            if (!$returnErrors) {
                return false;
            }

        }

        return ($returnHeaders) ? $responseObj : $responseObj['body'];
    }

}
