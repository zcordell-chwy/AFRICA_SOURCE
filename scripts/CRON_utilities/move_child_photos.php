<?php

//Author: Zach Cordell
//Date: 7/1/18
//Purpose: cron utility will be run every 1 time per day.  In order to remedy issues with webdav directories containing too many child photos we are creating many folders with 
// the name of the first two letters of the photo name md5 hash.  This should evenly distribute the files into many folders.

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
    echo "<br/>moving ".UPLOAD_FILE_LOCATION.$file." to ".$fullyQualified;
    rename(UPLOAD_FILE_LOCATION.$file, $fullyQualified);
    
    
}