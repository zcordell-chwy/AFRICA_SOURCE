<?php

namespace Custom\Models;

use RightNow\Connect\v1_3 as RNCPHP;
use RightNow\Utils\Framework;

require_once(get_cfg_var('doc_root') . '/include/ConnectPHP/Connect_init.phph');
initConnectAPI();

/**
 * This model would be loaded by using $this->load->model('custom/transaction_model');
 */
class user_tracking extends \RightNow\Models\Base
{
    function __construct()
    {
        parent::__construct();
        $this->CI->load->helper('constants');
        $this->CI->load->helper('log');
    }
    /**
     * Handles the creation of user entry in log table
     * captures the request uri, IP & form submit time stamp
     */
    function createUserLogEntry()
    {
        try {
            // helplog(__FILE__, __FUNCTION__ . __LINE__, "User IP & Request URI / URL Details : " . $_SERVER['REMOTE_ADDR'] . " & " . $_SERVER['REQUEST_URI'], "");

            $userLogEntry = new RNCPHP\Log\user_tracking();
            $userLogEntry->user_ip = $_SERVER['REMOTE_ADDR'];
            $userLogEntry->request_url = $_SERVER['REQUEST_URI'];
            $userLogEntry->form_submit_time = strtotime("now");
            $c_id = $this->CI->session->getSessionData('contactID');
            if ($c_id != null) {
                $userLogEntry->contact_id = intval($c_id);
            }

            //Save Object & Commit
            $userLogEntry->save(RNCPHP\RNObject::SuppressAll);
            logMessage($userLogEntr_ > ID);
            RNCPHP\ConnectAPI::commit();
        } catch (\Exception $e) {
            logMessage($e);
        }
    }
    function checkIsUserQualified()
    {
        try {
            $count = 0;
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $query = "select count() as count from Log.user_tracking where user_ip='" . $ip_address . "' AND createdtime > date_add( sysdate(), -1, 'hour', 0 )";
            $res = RNCPHP\ROQL::query($query)->next();
            if ($res)
                while ($userTracking = $res->next()) {
                    $count =  $userTracking['count'];
                }
            logMessage('Count : ' . $count);

            if ($count < RNCPHP\Configuration::fetch('CUSTOM_CFG_CP_MAX_TRANSACTION_PER_HOUR')->Value) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            logMessage($e);
        }
    }
}
