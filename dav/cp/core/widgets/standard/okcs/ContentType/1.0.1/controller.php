<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Config,
    RightNow\Utils\Url;

class ContentType extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!Config::getConfig(OKCS_ENABLED)) {
            echo $this->reportError(Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }

        if($this->data['attrs']['source_id']) {
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js'] = array('sources' => $search->getSources());
        }

        $this->data['contentTypes'] = $this->getContentTypes();
        $channelRecordID = Url::getParameter('channelRecordID');
        if($channelRecordID !== null) {
            $this->data['defaultContentType'] = $channelRecordID;
        }
        else if($contentType = \RightNow\Utils\Url::getParameter('ct')) {
            $this->data['defaultContentType'] = $contentType;
        }
        else {
            $this->data['defaultContentType'] = trim($this->data['attrs']['default_content_type']);
        }
        if ($this->data['contentTypes'] !== null) {
            foreach ($this->data['contentTypes'] as $item) {
                if ($item->name === null) {
                    echo $this->reportError(Config::getMessage(RES_OBJECT_PROPERTY_NAME_IS_NOT_MSG));
                    return false;
                }
                if ($item->recordID === null) {
                    echo $this->reportError(Config::getMessage(RES_OBJECT_PROPERTY_RECORDID_IS_NOT_MSG));
                    return false;
                }
                if (!$defaultContentTypeExists)
                    $defaultContentTypeExists = (strtoupper($item->referenceKey) === strtoupper($this->data['defaultContentType']));
            }
        }
        if(!$defaultContentTypeExists && count($this->data['contentTypes']) > 0)
            $this->data['defaultContentType'] = $this->data['contentTypes'][0]->referenceKey;
        $this->CI->model('Okcs')->setDefaultChannel($this->data['defaultContentType']);
    }

    /**
    * This method returns a list of content-types
    * @return Array|null List of the content-types
    */
    function getContentTypes(){
        $displayOnlyContentTypes = explode(",", trim($this->data['attrs']['content_type_list']));
        $allContentTypes = $this->CI->model('Okcs')->getChannels();

        if ($allContentTypes->error !== null) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($allContentTypes->error));
            return null;
        }

        if(empty($this->data['attrs']['content_type_list'])) {
            return $allContentTypes->results;
        }
        if ($allContentTypes->results !== null) {
            $contentType = array();
            $invalidChannel = array();
            for($i = 0; $i < count($displayOnlyContentTypes); $i++) {
                $isValidChannel = false;
                foreach ($allContentTypes->results as $item) {
                    if(strtoupper($item->referenceKey) === strtoupper(trim($displayOnlyContentTypes[$i]))) {
                        $isValidChannel = true;
                        array_push($contentType, $item);
                        break;
                    }
                }
                if(!$isValidChannel)
                    array_push($invalidChannel, $displayOnlyContentTypes[$i]);
            }
            if(count($invalidChannel) == 0)
                return $contentType;
            else
                echo $this->reportError(Config::ASTRgetMessage("'" . implode(", ", $invalidChannel). "'" . " not found for the 'content_type_list' attribute."));
        }
    }
}