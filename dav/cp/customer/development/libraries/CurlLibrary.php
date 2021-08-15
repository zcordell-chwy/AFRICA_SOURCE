<?php

namespace Custom\Libraries;

class CurlLibrary
{
    function __construct()
    {
    }

    function httpPost($url, $data)
    {
        if (!function_exists("\curl_init")) {
            load_curl();
        }
        $curl = \curl_init($url);

        \curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        \curl_setopt($curl, CURLOPT_POST, true);
        \curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = \curl_exec($curl);
        \curl_close($curl);

        return $response;
    }

    function curlPost($url, $header, $postdata)
    {
        try {
            if (!function_exists("\curl_init")) {
                load_curl();
            }
            $curl_request = curl_init();
            curl_setopt($curl_request, CURLOPT_URL, $url);
            curl_setopt($curl_request, CURLOPT_CONNECTTIMEOUT,   2000);
            curl_setopt($curl_request, CURLOPT_TIMEOUT,          2000);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER,   true);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER,   false);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST,   false);
            curl_setopt($curl_request, CURLOPT_POST,             true);
            curl_setopt($curl_request, CURLOPT_POSTFIELDS,       $postdata);
            curl_setopt($curl_request, CURLOPT_HTTPHEADER,       $header);

            $response = new stdClass();
            if (curl_exec($curl_request) === false) {
                $response->err = 'Curl error: ' . curl_error($curl_request);
            } else {
                //get the response
                $response->result  = curl_exec($curl_request);
            }
            curl_close($curl_request);
        } catch (Exception $e) {
            print_r($e);
        }
        return $response;
    }
}
