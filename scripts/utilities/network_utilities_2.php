<?php

namespace network_utilities {

    /**
     * Generic method to make curl requests
     * @param string $endpoint
     * @param type $requestType
     * @param type $postArray
     * @param type $headers header data to send
     * @param type $returnHeaders whether to return response headers with function return response
     * @param type $receiveHeaders - whether to get the headers back from API call in API response
     * @param type $file tells if we are uploading file, set special headers
     * @param type $timeout sets the timeout
     * @return mixed: response if success, false otherwise
     */

    function runCurl($endpoint, $requestType = "POST", $postArray = '', $headers = null, $returnHeaders = false, $receiveHeaders = false, $file = false, $timeout = 20)
    {
        $responseObj = array();
        try {

            if (!function_exists("\curl_init")) {
                \load_curl();
            }
            $curl = curl_init();

            $options = array(
                CURLOPT_URL => str_replace(' ', '%20', $endpoint),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FAILONERROR => false,
                CURLOPT_MAXREDIRS => 4,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HEADER => $receiveHeaders, //enable/disable headers
                CURLOPT_CUSTOMREQUEST => $requestType,
            );

            if ($headers) {
                $curlHeaders = [];
                foreach ($headers as $key => $value) {
                    $curlHeaders[] = $key . ':' . $value;
                }
                $options[CURLOPT_HTTPHEADER] = $curlHeaders;
            }

            // use this when sending file as @file
            if ($file) {
                $options[CURLOPT_SAFE_UPLOAD] = false;
            }

            // print_r($options);
            curl_setopt_array($curl, $options);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postArray);
            $responseObj['body'] = curl_exec($curl);
            if (!empty($returnHeaders)) {
                $responseObj['headers'] = curl_getinfo($curl);
            }
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            curl_close($curl);

            // $decodedResponseObj = json_decode($responseObj['body']);

            $responseObj['status'] = $httpCode;
            $responseObj['success'] = true;

            if ($httpCode > 299 || $httpCode < 99) {

                $responseObj['error'] = $err;
                $responseObj['success'] = false;
            }
        } catch (\Exception $e) {
            $responseObj['error'] = $e->getMessage();
            $responseObj['success'] = false;
        }

        return $responseObj;
    }
}
