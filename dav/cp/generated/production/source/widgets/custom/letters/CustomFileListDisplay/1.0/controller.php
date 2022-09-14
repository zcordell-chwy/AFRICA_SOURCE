<?php
namespace Custom\Widgets\letters;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Connect\v1_3 as RNCPHP;
    
class CustomFileListDisplay extends \RightNow\Widgets\FileListDisplay {
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    function getData() {
        return parent::getData();
    }    
    
    /**
     * Overridable methods from FileListDisplay:*/
    function getAttachments($input, array $commonAttachments){

        $incObj =  RNCPHP\Incident::fetch(intval(Url::getParameter('i_id')));

        $showOldestPdfOnly = false;

        if(!isset($incObj->CustomFields->c->response_rcvd_date)){
            $showOldestPdfOnly = true;
        }

        $regexArr = array();
        if (strlen($this->data['attrs']['regex_files_to_ignore']) > 0) {
            try{
                $regexArr = explode("||", $this->data['attrs']['regex_files_to_ignore']);
            }catch(Exception $e){
                logMessage($e->getMessage());
            }
        }
                

        $attachments = array();
        if (!count($input) && !$commonAttachments) {
            return $attachments;
        }

        if (!\RightNow\Utils\Connect::isFileAttachmentType($input)){
            echo $this->reportError(Config::getMessage(FILELISTDISPLAY_DISP_FILE_ATTACH_MSG));
            return $attachments;
        }

        // convert connect object to array and merge in common attachments if present
        $input = (array) $input;
        if ($commonAttachments) {
            $input = array_merge(array_values($input), array_values($commonAttachments));
        }

        $openInNewWindow = trim(Config::getConfig(EU_FA_NEW_WIN_TYPES));
        $attachmentUrl = '/ci/fattach/get/%s/%s' . Url::sessionParameter() . '/filename/%s';
        $showCreatedTime = Text::beginsWith($this->data['attrs']['name'], 'Incident.');
        foreach ($input as $item) {
            
            if ($item->Private) {
                continue;
            }

            $skipFile = false;
            if(count($regexArr) > 0){
                foreach($regexArr as $regexString){
                    if(preg_match($regexString, $item->FileName)){
                        $skipFile = true;
                    }
                }

                if($skipFile === true){
                    continue;
                }
            }

            $item->Target = ($openInNewWindow && preg_match("/{$openInNewWindow}/i", $item->ContentType)) ? '_blank' : '_self';
            $item->Icon = \RightNow\Utils\Framework::getIcon($item->FileName);
            $item->ReadableSize = Text::getReadableFileSize($item->Size);
            $item->AttachmentUrl = sprintf($attachmentUrl, $item->ID, $showCreatedTime ? $item->CreatedTime : 0, urlencode($item->FileName));
            $item->ThumbnailUrl = $item->ThumbnailScreenReaderText = null;
            if($this->data['attrs']['display_thumbnail'] && Text::beginsWith($item->ContentType, 'image')) {
                $fileExtension = pathinfo($item->AttachmentUrl, PATHINFO_EXTENSION);
                $fileExtension = Text::escapeHtml($fileExtension);
                $item->ThumbnailScreenReaderText = sprintf(Config::getMessage(FILE_TYPE_PCT_S_LBL), $fileExtension);
                // ThumbnailUrl may change in the future, but for now is the same as $item->AttachmentUrl
                $item->ThumbnailUrl = $item->AttachmentUrl;
            }
            $attachments[] = $item;
        }

        if($showOldestPdfOnly == true){
            return array($attachments[0]);
        }

        return $attachments;
    }
}