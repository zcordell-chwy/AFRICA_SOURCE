<?php
namespace Custom\Models;

require_once (get_cfg_var('doc_root') . '/ConnectPHP/Connect_init.php');
use RightNow\Connect\v1_3 as RNCP;

class event_model extends \RightNow\Models\Base {
    protected static $CLASS_SCOPE = 'Custom/Models/event_model';

    function __construct() {
        parent::__construct();
        initConnectAPI('api_access', 'Password1');
        $this -> CI -> load -> helper('constants');
        $this->CI->load->library('logging');

        $this->CLASS_LOG_LEVEL = \Custom\Libraries\Logging::$LOG_LEVEL_FATAL_ERROR;
    }

    /**
     * Function to retrieve a Sponsorship.Event record by ID.
     * @param integer $id the ID of the Sponsorship.Event record to retrieve.
     * @return object the Sponsorship.Event record if found, otherwise null
     */
    public function getEvent($id){
        $this->CI->logging->logFunctionCall(self::$CLASS_SCOPE, 'getEvent', 
            array('$id' => $id), $this->CI->logging->LOG_LEVEL_DEBUG_FULL, $this->CLASS_LOG_LEVEL);

        $event = null;

        try{
            $event = RNCP\sponsorship\Event::fetch($id);
            $this->CI->logging->logVar('$event', $event, true, $this->CI->logging->LOG_LEVEL_DEBUG_FULL, $this->CLASS_LOG_LEVEL);
        }catch(\Exception $e){
            $this->CI->logging->logErr($e, self::$CLASS_SCOPE, 'getEvent', $this->CI->logging->LOG_LEVEL_FATAL_ERROR, $this->CLASS_LOG_LEVEL);
            throw $e;
        }

        $this->CI->logging->logFunctionReturn(self::$CLASS_SCOPE, 'getEvent', $event, '$event', $this->CI->logging->LOG_LEVEL_DEBUG_FULL, $this->CLASS_LOG_LEVEL);
        return $event;
    }
}