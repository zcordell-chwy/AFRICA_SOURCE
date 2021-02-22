<?php

use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

define('ALLOW_POST', true);
define('ALLOW_GET', false);
define('ALLOW_PUT', false);
define('ALLOW_PATCH', false);


require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';

try {
    $listener = new managedMissions(file_get_contents('php://input'));
} catch (\Exception $ex) {
    return outputResponse(null, $ex->getMessage());
} catch (RNCPHP\ConnectAPIError $ex) {
    return outputResponse(null, $ex->getMessage());
}
/**
 *
 */
class managedMissions
{

    private $executionSummary;

    public function __construct($rawPost)
    {
        $this->executionSummary[] = "Loaded Constructor";
        $this->executionSummary[] = $rawPost;

        return outputResponse($this->executionSummary, null, $this->logger);
    }


    

    
}
