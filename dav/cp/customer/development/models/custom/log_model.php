<?php

namespace Custom\Models;

use \RightNow\Connect\v1_3 as RNCPHP;

class log_model extends \RightNow\Models\Base
{
    function __construct()
    {
        parent::__construct();
        $this->CI->load->helper('constants');
    }


    function log($filename, $functionname, $contactID, $sessionID, $lineNum, $message, $logType = null)
    {
        logMessage($functionname . " in " . $filename . " Contact:" . $contactID);
        $log = new RNCPHP\Log\Log();
        $log->FileName = $filename;
        if (strlen($functionname)) $log->FunctionName = $functionname;
        if ($contactID > 0)     $log->Contact = $contactID;
        if ($sessionID)         $log->sessionID = $sessionID;
        if ($lineNum > 0)       $log->LineNum = $lineNum;
        if (strlen($message) > 0)   $log->Message = $message;
        if (strlen($logType) > 0)     $log->LogType = $logType;
        $log->save();
    }
}
