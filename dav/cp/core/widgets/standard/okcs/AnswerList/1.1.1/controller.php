<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Url;

class AnswerList extends \RightNow\Widgets\SourceResultListing {
    private $answerListApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!(\RightNow\Utils\Config::getConfig(OKCS_ENABLED))) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        $type = $this->data['attrs']['type'];
        $pageSize = $this->data['attrs']['per_page'];
        $limit = $this->data['attrs']['limit'];
        $truncateSize = $this->data['attrs']['truncate_size'];
        $contentTypeParameterValue = Url::getParameter('ct') ?: $this->data['attrs']['content_type'];
        $categoryParameterValue = Url::getParameter('cg');
        $channelRecordID = Url::getParameter('channelRecordID');
        $pageNumber = Url::getParameter('browsePage');
        $sortColumnID = Url::getParameter('sortColumn');
        $sortDirection = Url::getParameter('sortDirection');
        $defaultChannel = ($channelRecordID !== null) ? $channelRecordID : $this->CI->model('Okcs')->getDefaultChannel();
        $contentType = empty($contentTypeParameterValue) ? $defaultChannel : $contentTypeParameterValue;
        $productCategory = $this->getProductCategory();      
        
        $filter = $this->getFilters();

        $articles = $this->CI->model('Okcs')->getArticlesSortedBy($filter);

        if ($articles !== null) {
            if ($articles->error === null) {
                $this->data['articles'] = $articles->results;
                $viewType = $this->data['attrs']['view_type'];
                if (empty($viewType)) {
                    $viewType = (strtolower($type) === 'browse' ? 'table' : 'list');
                }
                $isTable = $viewType === 'table' ? true : false;
                $list = array();
                if ($isTable) {
                    $this->data['fields'] = $this->getTableFields();
                    if (!empty($this->data['fields'])) {
                        foreach ($articles->results as $document) {
                            foreach ($this->data['fields'] as $field) {
                                if (!property_exists($document, $field['name']) && $document->$field['name'] === null) {
                                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(RES_OBJECT_PROPERTY_PCT_S_IS_NOT_MSG), $field['name']));
                                    return false;
                                }
                            }
                            $item = array(
                                'contentType'       => $document->contentType->referenceKey,
                                'locale'            => $document->locale->recordID,
                                'priority'          => $document->priority,
                                'createDate'        => $document->createDate,
                                'dateAdded'         => $document->dateAdded,
                                'dateModified'      => $document->dateModified,
                                'displayStartDate'  => $document->displayStartDate,
                                'displayEndDate'    => $document->displayEndDate,
                                'owner'             => $document->owner->name,
                                'lastModifier'      => $document->lastModifier->name,
                                'creator'           => $document->creator->name,
                                'published'         => $document->published,
                                'publishDate'       => $document->publishDate,
                                'checkedOut'        => $document->checkedOut,
                                'publishedVersion'  => $document->publishedVersion,
                                'recordID'          => $document->recordID,
                                'versionID'         => $document->versionID,
                                'documentID'        => $document->documentID,
                                'title'             => $document->title,
                                'version'           => $document->version,
                                'answerID'          => $document->answerID,
                                'encryptedTitle'    => $document->encryptedTitle
                            );
                            array_push($list, $item);
                        }
                        $this->data['articles'] = $list;
                    }
                    else {
                        echo $this->reportError(\RightNow\Utils\Config::getMessage(MISSING_TITLE_IN_DISPFLDS_ADD_LBL));
                        return false;
                    }
                }

                Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filter);
                $this->data['js'] = array(
                    'articles'  => $this->data['articles'],
                    'filters'   => $filter,
                    'answerUrl' => $this->data['attrs']['answer_detail_url'],
                    'viewType'  => $viewType,
                    'doNotSortList' => array('Owner', 'Published', 'Version'),
                    'showHeaders' => $this->data['attrs']['show_headers']
                );
                
                if ($isTable) {
                    $this->data['js']['sortColumn'] = $sortColumnID !== null ? $sortColumnID : "publishDate";
                    $this->data['js']['sortDirection'] = $sortDirection !== null ? $sortDirection : "DESC";
                    $this->data['js']['headers'] = $this->data['fields'];
                    $this->data['js']['dataTypes'] = array('date' => VDT_DATE, 'datetime' => VDT_DATETIME, 'number' => VDT_INT);
                }
                $this->data['js']['answerListApiVersion'] = $this->answerListApiVersion;
            }
            else {
                echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($articles->error));
                return false;
            }
        }
    }

    /**
     * Method to get filter parameters from url.
     * @return string product/category record id
     */
    private function getProductCategory() {
        $productRecordID = Url::getParameter('productRecordID');
        $isProductSelected = Url::getParameter('isProductSelected');
        $categoryRecordID = Url::getParameter('categoryRecordID');
        $isCategorySelected = Url::getParameter('isCategorySelected');
        if ($isProductSelected || $isCategorySelected) {
            if ($isProductSelected) {
                $productCategory = $productRecordID;
            }
            if ($isCategorySelected) {
                if($productCategory !== null) {
                    $productCategory = ':' . $categoryRecordID;
                }
                else {
                    $productCategory = $categoryRecordID;
                }
            }
        } 
        else {
             $productCategory = $categoryParameterValue ?: $this->data['attrs']['product_category'];
        }
        return $productCategory;
    }
    
    /**
    * Lists the fields of the table based on the value of the attribute 'display_fields' and extracts the label to use for each field.
    * @return array list of display fields
    */
    private function getTableFields() {
        $fields = array();
        $index = 0;
        $isTitle = false;
        foreach (explode("|", $this->data['attrs']['display_fields']) as $field) {
            if (!$isTitle && $field === 'title')
                $isTitle = true;

            switch ($field) {
                case "createDate":
                    $labelAttribute = $this->data['attrs']['label_create_date'];
                    break;
                case "dateModified":
                    $labelAttribute = $this->data['attrs']['label_date_modified'];
                    break;
                case "publishDate":
                    $labelAttribute = $this->data['attrs']['label_publish_date'];
                    break;
                case "documentID":
                    $labelAttribute = $this->data['attrs']['label_document_id'];
                    break;
                case "answerID":
                    $labelAttribute = $this->data['attrs']['label_answer_id'];
                    break;
                default:
                    $labelAttribute = $this->data['attrs']['label_' . strtolower($field)];
            }

            $item = array(
                'name' => $field,
                'label' => $labelAttribute ?: $field,
                'columnID' => $index
            );
            $index++;
            array_push($fields, $item);
        }
        if(!$isTitle)
            return array();
        
        return $fields;
    }

    /**
     * Method to get filters array.
     * @return array list of filters
     */
    private function getFilters() {
        $contentTypeParameterValue = Url::getParameter('ct') ?: $this->data['attrs']['content_type'];
        $channelRecordID = Url::getParameter('channelRecordID');
        $defaultChannel = ($channelRecordID !== null) ? $channelRecordID : $this->CI->model('Okcs')->getDefaultChannel();
        return array(
            'type' => $this->data['attrs']['type'],
            'limit' => $this->data['attrs']['limit'],
            'contentType' => empty($contentTypeParameterValue) ? $defaultChannel : $contentTypeParameterValue,
            'category' => $this->getProductCategory(),
            'pageNumber' => Url::getParameter('browsePage') !== null ? Url::getParameter('browsePage') : 0,
            'pageSize' => $this->data['attrs']['per_page'],
            'truncate' => $this->data['attrs']['truncate_size'],
            'sortColumnId' => Url::getParameter('sortColumn'),
            'sortDirection' => Url::getParameter('sortDirection'),
            'status' => $this->data['attrs']['show_draft'],
            'answerListApiVersion' => $this->answerListApiVersion
        );
    }
}