<?php

//Author: Zach Cordell
//Date: 7/1/18
//Purpose: cron utility will be run every 1 time per day.  In order to remedy issues with webdav directories containing too many child photos we are creating many folders with 
// the name of the first two letters of the photo name md5 hash.  This should evenly distribute the files into many folders.


use RightNow\Connect\v1_3 as RNCPHP;

if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

if (!defined('SCRIPT_PATH')) {
    $scriptPath  = ($debug) ? DOCROOT . '/custom/src' : DOCROOT . '/custom';
    define('SCRIPT_PATH', $scriptPath);
}

define('ALLOW_POST', false);
define('ALLOW_GET', true);
define('ALLOW_PUT', false);
define('ALLOW_PATCH', false);
require_once SCRIPT_PATH . '/utilities/make.me.an.api.php';
$returnArray = array();


/**************CONSTANTS*******************/
//uncomment on deployment
CONST UPLOAD_FILE_LOCATION = "/vhosts/africanewlife/euf/assets/childphotos/upload/";
CONST HASHED_FILE_DIR = "/vhosts/africanewlife/euf/assets/childphotos/hashedChildPhotos/";
/******************************************/

$files = scandir(UPLOAD_FILE_LOCATION);

foreach($files as $file){
    
    if($file == "." || $file == "..")
        continue;
    
    $fullyQualified = HASHED_FILE_DIR.substr(md5(strtoupper($file)), 0,2)."/".strtoupper($file);
    if(!file_exists(dirname($fullyQualified))){
        mkdir(dirname($fullyQualified), 0777, true);
    }
    $returnArray[] =  "Moving ".UPLOAD_FILE_LOCATION.$file." to ".$fullyQualified;
    rename(UPLOAD_FILE_LOCATION.$file, $fullyQualified);
    
    
}

return outputResponse($returnArray, null);