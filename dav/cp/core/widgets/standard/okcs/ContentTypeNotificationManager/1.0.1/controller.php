<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;


class ContentTypeNotificationManager extends \RightNow\Libraries\Widget\Base {
    private $contentTypeApiVersion = 'v1';
    private $productCategoryApiVersion = 'v1';

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $this->data['js']['productCategoryApiVersion'] = $this->productCategoryApiVersion;
        $this->data['js']['contentTypes'] = $this->getContentTypes();
        $subscriptionList = $this->CI->model('Okcs')->getContentTypeSubscriptionList();
        $list = array();
        if ($subscriptionList !== null && $subscriptionList->error === null && count($subscriptionList->items) > 0) {
            foreach ($subscriptionList->items as $document) {
                if($document->subscriptionType === 'SUBSCRIPTIONTYPE_CHANNEL') {
                        $contentTypeId = $document->name;
                        $dateAdded = $this->CI->model('Okcs')->processIMDate($document->dateAdded);

                        $item = array(
                            'name'        => $document->name,
                            'startDate'           => $dateAdded,
                            'subscriptionID'    => $document->recordId
                        );
                        array_push($list, $item);
                }
            }
            $this->data['js']['hasMore'] = $subscriptionList->hasMore;
            $this->data['subscriptionList'] = $list;
        }
        else if (count($subscriptionList->items) === 0 && $this->data['attrs']['hide_when_no_results']) {
            $this->classList->add('rn_Hidden');
        }
    }

    /**
    * This method returns a list of content-types
    * @return Array|null List of the content-types
    */
    function getContentTypes(){
        $allContentTypes = $this->CI->model('Okcs')->getChannels($this->contentTypeApiVersion);
        $contentType = array();
        if ($allContentTypes->error !== null) {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($allContentTypes->error));
            return null;
        }
        if ($allContentTypes->items !== null) {
            foreach ($allContentTypes->items as $item) {
                array_push($contentType, $item);
            }
        }
        return $contentType;
    }
}
