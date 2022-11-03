<?php
namespace Custom\Widgets\eventus;

use RightNow\Utils\Url;

class ContactsFileListDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        initConnectAPI('cp_082022_user', '$qQJ616xWWJ9lXzb$');
        
        if(parent::getData() === false)  
            return false;
        
        $contactObj = $this -> CI -> model('Contact') -> get() -> result;
        
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
        
        $allowedFileNames = $result = array_map('trim', explode(',' ,$this->data['attrs']['include_only']));
        $allowedMimeTypes = $result = array_map('trim', explode(',' ,$this->data['attrs']['content_type_allowed']));
        
        
        foreach ($attachments as $item){
            
            //redone 7/13/18 as the ci/fattach link stopped working.
            //added account/attachview page to display single files
            if(in_array(trim($item->FileName), $allowedFileNames) || (empty($allowedFileNames[0]) && empty($allowedMimeTypes[0]))  ){ //exact name
                $type = ($item->ContentType == 'application/pdf') ? "p" : "h";
                $item->AttachmentUrl = "/app/account/attachview/attach_id/".$item->ID."/ct/$type";
                $item->Target = '_blank';
            }else if( in_array(trim($item->ContentType), $allowedMimeTypes) ){
                $type = ($item->ContentType == 'application/pdf') ? "p" : "h";
                $item->AttachmentUrl = "/app/account/attachview/attach_id/".$item->ID."/ct/$type";
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
    
}