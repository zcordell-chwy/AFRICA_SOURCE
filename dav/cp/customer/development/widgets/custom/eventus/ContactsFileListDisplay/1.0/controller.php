<?php
namespace Custom\Widgets\eventus;

use RightNow\Utils\Url;

class ContactsFileListDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        if(parent::getData() === false)  
            return false;
        
        $contactObj = $this -> CI -> model('Contact') -> get() -> result;
        
        $this->_getValues($contactObj);
        $attachments = $contactObj->FileAttachments;
        if(is_null($attachments)){
            $attachments = array();
        }else{
            // Transform $attachments from a CPHP FileAttachmentCommonArray to a native PHP array so that we can sort it
            $attachmentsNativePHPArray = array();
            $attachmentCnt = count($attachments);
            for($i = 0; $i < $attachmentCnt; $i++){
                $attachmentsNativePHPArray[] = $attachments[$i];
            }
            $attachments = $attachmentsNativePHPArray;
        } 
        
        $CI = get_instance();
        $sessionstring = $CI->session->getSessionData('sessionString');
        
        $allowedFileNames = $result = array_map('trim', explode(',' ,$this->data['attrs']['include_only']));
        $allowedMimeTypes = $result = array_map('trim', explode(',' ,$this->data['attrs']['content_type_allowed']));

        $attachmentUrl = "/ci/fattach/get/%s/%s" . $sessionstring . "/filename/%s";
        
        
        foreach ($attachments as $item){
            
            //redone 7/13/18 as the ci/fattach link stopped working.
            //added account/attachview page to display single files
            if(in_array(trim($item->FileName), $allowedFileNames) || (empty($allowedFileNames[0]) && empty($allowedMimeTypes[0]))  ){ //exact name
                $item->AttachmentUrl = "/app/account/attachview/c_id/".$contactObj->ID."/attach_id/".$item->ID;
                $item->Target = '_blank';
            }else if( in_array(trim($item->ContentType), $allowedMimeTypes) ){
                $item->AttachmentUrl = "/app/account/attachview/c_id/".$contactObj->ID."/attach_id/".$item->ID;
                $item->Target = '_blank';
            }
        }
        
        usort($attachments, array($this, 'sortDescByDateCreated'));

        $this->data['value'] = $attachments;
        
        logMessage($attachments);
        

    }

    function sortDescByDateCreated($attach1, $attach2){
        return $attach2->CreatedTime - $attach1->CreatedTime;
    }
    
    //force lazy loading for debug purposes        
    function _getValues($parent) {
        try {
            // $parent is a non-associative (numerically-indexed) array
            if (is_array($parent)) {

                foreach ($parent as $val) {
                    $this -> _getValues($val);
                }
            }

            // $parent is an associative array or an object
            elseif (is_object($parent)) {

                while (list($key, $val) = each($parent)) {

                    $tmp = $parent -> $key;

                    if ((is_object($parent -> $key)) || (is_array($parent -> $key))) {
                        $this -> _getValues($parent -> $key);
                    }
                }
            }
        } catch (exception $err) {
            // error but continue
        }
    }
}