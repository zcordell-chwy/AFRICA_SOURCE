<?php

//Author: Zach Cordell
//Date: 10/2020
//Daily Process to update credit card Expiration Date, Last 4.


if (!defined('DOCROOT'))
    define('DOCROOT', get_cfg_var('doc_root'));

require_once (DOCROOT . '/include/config/config.phph');
require_once (DOCROOT . '/include/ConnectPHP/Connect_init.phph');

initConnectAPI();
use RightNow\Connect\v1_3 as RNCPHP;

require_once(DOCROOT . '/include/services/AgentAuthenticator.phph');
AgentAuthenticator::authenticateCredentialsAndProfile('update_card_user', '8pS7E8BXmdNnQ', array(
    "Eventus - Full Access",
    "API_Access"
));

define('PASS', RNCPHP\Configuration::fetch(CUSTOM_CFG_frontstream_pass)->Value);
define('USER', RNCPHP\Configuration::fetch(CUSTOM_CFG_frontstream_user)->Value);
define('MERCH_KEY', RNCPHP\Configuration::fetch(CUSTOM_CFG_merchant_key)->Value);
define('UPDATE_CARD_USER', RNCPHP\Configuration::fetch(CUSTOM_CFG_UPDATE_CARD_USER)->Value);
define('UPDATE_CARD_PASS', RNCPHP\Configuration::fetch(CUSTOM_CFG_UPDATE_CARD_PASS)->Value);
define('UPDATE_CARD_ENDPOINT', RNCPHP\Configuration::fetch(CUSTOM_CFG_UPDATE_CARD_ENDPOINT)->Value);
define('FS_SITE_URL', RNCPHP\Configuration::fetch(CUSTOM_CFG_frontstream_endpoint)->Value);

load_curl();

$cardDetails = getCardUpdates();

if(!empty($cardDetails)){
    updatePayMethods($cardDetails);
}

function updatePayMethods($cardDetails){

    foreach($cardDetails as $card){

        try{

            
            $updateNotes = array();

            //paymethods that have a null autoupdate or not updated today.
            $payMethod = RNCPHP\financial\paymentMethod::first("financial.paymentMethod.PN_Ref = '" . $card->PNREF . "' and (financial.paymentMethod.autoUpdatedDate is NULL OR financial.paymentMethod.autoUpdatedDate < '".date('Y-m-d')."' )");

            if($card->UPDATED == 'true' && 
                $payMethod instanceof RNCPHP\financial\paymentMethod && 
                    intval($card->PNREF) > 0 &&
                        $payMethod->Contact->CustomFields->c->activepledgecount > 0 ){

                if($card->UPDATEDACCOUNTLASTFOUR != $payMethod->lastFour){
                    //do nothing for now
                    // $updateNotes[] = 'Update Last Four:'.$payMethod->lastFour."->".$card->UPDATEDACCOUNTLASTFOUR;
                    // $payMethod->lastFour = $card->UPDATEDACCOUNTLASTFOUR;
                }else{
                    if(substr($card->UPDATEDEXPDATE, 0, 2) != $payMethod->expMonth){
                        $updateNotes[] = 'Update Exp Month:'.$payMethod->expMonth."->".substr($card->UPDATEDEXPDATE, 0, 2);
                        $payMethod->expMonth = substr($card->UPDATEDEXPDATE, 0, 2);
                    }
                    
                    if( "20".substr($card->UPDATEDEXPDATE, 2, 2) != $payMethod->expYear ){
                        $updateNotes[] = 'Update Exp Year:'.$payMethod->expYear."->20".substr($card->UPDATEDEXPDATE, 2, 2);
                        $payMethod->expYear = "20".substr($card->UPDATEDEXPDATE, 2, 2);
                    }
    
                    if(count($updateNotes) > 0){
                        $payMethod->autoUpdatedDate = time();
                        
                        $notes_count = count($payMethod -> Notes);
                        if ($notes_count == 0) {
                            $payMethod -> Notes = new RNCPHP\NoteArray();
                        }
                        $payMethod -> Notes[$notes_count] = new RNCPHP\Note();
                        $payMethod -> Notes[$notes_count] -> Text = print_r($updateNotes,true);
    
                        $payMethod->save();
    
                        //serialize in case something goes awry, we can pick up where we left off.
                        RNCPHP\ConnectAPI::commit();
    
                    }
                }

                
            }
        }catch(Exception $e){
            print_r($e->getMessage());
        }
        
    }
}

function getCardUpdates(){
    $params[] = 'username='.USER;
    $params[] = 'password='.PASS;
    $params[] = 'merchantKey='.MERCH_KEY;
    $params[] = 'beginDt='.urlencode(date('m/d/y', strtotime('7 days ago')));
    $params[] = 'endDt='.urlencode(date('m/d/y', strtotime('today')));
    $params[] = 'extData=';
    $endpoint = FS_SITE_URL.UPDATE_CARD_ENDPOINT.implode('&', $params);

    $cardUpdatesResponse = runCurl($endpoint, 'GET', null, null);

    $cardUpdatesResponse = str_replace("&lt;", "<", $cardUpdatesResponse);
    $cardUpdatesResponse = str_replace("&gt;", ">", $cardUpdatesResponse);

    return (!empty($cardUpdatesResponse)) ? xml_to_object($cardUpdatesResponse) : false;
}


function runCurl($endpoint, $requestType = "POST", $postArray, $headers, $returnHeaders = false, $receiveHeaders = false, $timeout = 20, $hidePostVals = false, $storeCookies = false) {

    $responseObj = array();

    if (!function_exists("\curl_init")) {
        \load_curl();
    }
    $curl = curl_init();

    $options = array(
        CURLOPT_URL => $endpoint,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET"
    );

    curl_setopt_array($curl, $options);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postArray);
    $responseObj['body'] = curl_exec($curl);
    $responseObj['headers'] = curl_getinfo($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);

    if ($httpCode != 200 && $httpCode != 201) {
        print_r("INVALID HTTPCODE WHILE CALLING %s : %s\r\n", $endpoint, print_r($httpCode, true));
        print_r("ERROR MESSAGE FOR API CALL : %s\r\n", print_r($err, true));
        return false;
    }

    return ($returnHeaders) ? $responseObj : $responseObj['body'];
}

function xml_to_object($xml) {

    $parser = xml_parser_create();
    //xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    //xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $xml, $tags);
    xml_parser_free($parser);
  
    $elements = array();  // the currently filling [child] XmlElement array
    $stack = array();

    
    foreach ($tags as $tag) {
      $index = count($elements);
      if ($tag['type'] == "complete" || $tag['type'] == "open") {
        $elements[$index] = new stdClass();
        $elements[$index]->name = $tag['tag'];
        $elements[$index]->attributes = $tag['attributes'];
        $elements[$index]->content = $tag['value'];
        if ($tag['type'] == "open") {  // push
          $elements[$index]->children = array();
          $stack[count($stack)] = &$elements;
          $elements = &$elements[$index]->children;
        }
      }
      if ($tag['type'] == "close") {  // pop
        $elements = &$stack[count($stack) - 1];
        unset($stack[count($stack) - 1]);
      }
    }

    $returnData = array();
    foreach($elements[0]->children[0]->children as $tableData){
        
        $cardData = new stdClass();
        foreach($tableData->children as $dataPoints){
            $cardData->{$dataPoints->name} = $dataPoints->content;
        }
        
        $returnData[] = $cardData;
    }

    return $returnData;  // the single top-level element
}

//forces lazy loading of array or object
function _getValues($parent) {
    try {
        // $parent is a non-associative (numerically-indexed) array
        if (is_array($parent)) {

            foreach ($parent as $val) {
                _getValues($val);
            }
        }

        // $parent is an associative array or an object
        elseif (is_object($parent)) {

            while (list($key, $val) = each($parent)) {

                $tmp = $parent->$key;

                if ((is_object($parent->$key)) || (is_array($parent->$key))) {
                    _getValues($parent->$key);
                }
            }
        }
    } catch (exception $err) {
        // error but continue
    }
}

?>
