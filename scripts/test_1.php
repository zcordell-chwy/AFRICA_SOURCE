<?php

ini_set('display_errors', 'Off');
error_reporting(0);

$ip_dbreq = true;
require_once('include/init.phph');

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_3 as RNCPHP;
initConnectAPI('cron_042022_user', 'x&w4iA712');

try{
    $roql = "select financial.transactions from financial.transactions t where t.CreatedTime >= '2023-01-01 00:00:00' AND t.currentStatus.ID = 3";

    $transactions = RNCPHP\ROQL::queryObject($roql) -> next();
    $contacts = array();
    $contacts0_1000 = array();
    $contacts1001_4999 = array();
    $contacts5000_24999 = array();
    $contacts25000_49999 = array();
    $contacts50000_99999 = array();
    $contacts100000 = array();

    while ($trans = $transactions -> next()) {

        //echo "total Charge:".$trans->totalCharge_n." contact:".$trans->contact->ID."<br/>";
        if(!in_array($trans->contact->ID, $contacts)){
            $contacts[] = $trans->contact->ID;
        }

        if(!in_array($trans->contact->ID,$contacts0_1000) && $trans->totalCharge_n <= 1000){
            $contacts0_1000[] = $trans->contact->ID;
            continue;
        }
        if(!in_array($trans->contact->ID,$contacts1001_4999) && $trans->totalCharge_n <= 4999){
            $contacts1001_4999[] = $trans->contact->ID;
            continue;
        }
        if(!in_array($trans->contact->ID,$contacts5000_24999) && $trans->totalCharge_n <= 24999){
            $contacts5000_24999[] = $trans->contact->ID;
            continue;
        }
        if(!in_array($trans->contact->ID,$contacts25000_49999) && $trans->totalCharge_n <= 49999){
            $contacts25000_49999[] = $trans->contact->ID;
            continue;
        }
        if(!in_array($trans->contact->ID,$contacts50000_99999) && $trans->totalCharge_n <= 99999){
            $contacts50000_99999[] = $trans->contact->ID;
            continue;
        }
        if(!in_array($trans->contact->ID,$contacts100000) && $trans->totalCharge_n >= 100000){
            $contacts100000[] = $trans->contact->ID;
            continue;
        }

    }

    echo "contacts:".count($contacts)."</br>";
    echo "contacts1000:".count($contacts0_1000)."</br>";
    echo "contacts4999:".count($contacts1001_4999)."</br>";
    echo "contacts24999:".count($contacts5000_24999)."</br>";
    echo "contacts49999:".count($contacts25000_49999)."</br>";
    echo "contacts99999:".count($contacts50000_99999)."</br>";
    echo "contacts100000:".count($contacts100000)."</br>";
} catch(RNCPHP\ConnectAPIError $e) {
    print_r($e->getMessage());
}catch(\Exception $e){
    print_r($e->getMessage());
}


?>