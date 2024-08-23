<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
    <?
    $this->CI = get_instance();
    $profile = $this->CI->session->getProfile();

    if ($this->CI->session->getProfileData('contactID') > 0 && $this->CI->session->getProfileData('contactID') == $profile->c_id->value) { ?>

        <?
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

        $contactObj = $this -> CI -> model('Contact') -> get() -> result;
        $user= \RightNow\Utils\Config::getConfig(CUSTOM_CFG_CP_PHP_API_USER);
        $pwd =\RightNow\Utils\Config::getConfig(CUSTOM_CFG_CP_PHP_API_USER_PWD);

        $endpoint = sprintf("https://africanewlife.custhelp.com/services/rest/connect/v1.4/contacts/%s/fileAttachments/%s/data", $contactObj->ID, getUrlParm('attach_id'));

        $basicauth=base64_encode($user.":".$pwd );
        $headerArr = array(
            'OSvC-CREST-Application-Context:Contact',
            'Authorization: Basic '.$basicauth
        );

        $result = runCurl($endpoint, 'GET', null, $headerArr, false, false, 20, false, true);
        $resultObj = json_decode($result);

        ob_end_clean();

        if(getUrlParm('ct') == 'p'){
            header('Content-Type: application/pdf');
            header(sprintf('Content-Disposition: inline; filename="%s.pdf"', $contactObj->ID . '_' . getUrlParm('attach_id')));
        }
        
        echo base64_decode($resultObj->data);
        exit;
        ?>
    <? } else {
        // header('Location: /app/account/communications/c_id/'.$profile->c_id->value);
    } ?>

</div>