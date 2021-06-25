<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use RightNow\Connect\v1_3 as RNCPHP;

/**
 * This helper can be loaded a few different ways depending on where it's being called:
 *
 * From a widget or model: $this->CI->load->helper('sample')
 *
 * From a custom controller: $this->load->helper('sample')
 *
 * Once loaded you can call this function by simply using helperFunction()
 */
function helplog($filename, $functionname, $message, $errorMessage)
{
    
    try{
        $log = new RNCPHP\ErrorLogs\Log();
        $log->File = $filename;
        $log->Function = $functionname;
    
    if ($message) if (strlen($message) > 0) $log->PostedMessage = substr($message, 0, 3995);
    if ($errorMessage) if (strlen($errorMessage) > 0) $log->Error = substr($errorMessage, 0, 3995);
    $log->save();
    }
    catch (Exception $e) {
        logMessage($e);
    }

}
