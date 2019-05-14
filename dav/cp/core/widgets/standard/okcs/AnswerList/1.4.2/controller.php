<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use RightNow\Utils\Url,
    RightNow\Libraries\Search,
    RightNow\Utils\Okcs;

class AnswerList extends \RightNow\Widgets\SourceResultListing {
    private $answerListApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        if($this->data['attrs']['source_id']) {
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $sources = $search->getSources();
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
                $this->data['articles'] = $articles->items;
                $viewType = $this->data['attrs']['view_type'];
                if (empty($viewType)) {
                    $viewType = (strtolower($type) === 'browse' ? 'table' : 'list');
                }
                $isTable = $viewType === 'table' ? true : false;
                $list = array();
                if ($isTable) {
                    $this->data['fields'] = $this->getTableFields();
                    if (!empty($this->data['fields'])) {
                        foreach ($articles->items as $document) {
                            foreach ($this->data['fields'] as $field) {
                                if (!property_exists($document, $field['name']) && $document->$field['name'] === null) {
                                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(RES_OBJECT_PROPERTY_PCT_S_IS_NOT_MSG), $field['name']));
                                    return false;
                                }
                            }
                            $item = array(
                                'contentType'       => $document->contentType->referenceKey,
                                'locale'            => $document->locale->recordId,
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
                                'recordID'          => $document->recordId,
                                'versionID'         => $document->versionId,
                                'documentId'        => $document->documentId,
                                'title'             => $document->title,
                                'version'           => $document->version,
                                'answerId'          => $document->answerId,
                                'encryptedUrl'      => $document->encryptedUrl
                            );
                            array_push($list, $item);
                        }
                        $this->data['articles'] = $this->data['attrs']['internal_pagination'] ? array_slice($list, 0, $this->data['attrs']['per_page']) : $list;
                    }
                    else {
                        echo $this->reportError(\RightNow\Utils\Config::getMessage(MISSING_TITLE_IN_DISPFLDS_ADD_LBL));
                        return false;
                    }
                }
                else {
                    if($this->data['attrs']['internal_pagination']) {
                        $this->data['articles'] = array_slice($this->data['articles'], 0, $this->data['attrs']['per_page']);
                    }
                    else {
                        $this->data['articles'] = $this->data['articles'];
                    }
                }

                Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filter);
                $this->data['js'] = array(
                    'articles'  => $this->data['articles'],
                    'filters'   => $filter,
                    'sources' => $sources,
                    'answerUrl' => $this->data['attrs']['answer_detail_url'],
                    'viewType'  => $viewType,
                    'doNotSortList' => array('owner', 'published', 'version'),
                    'showHeaders' => $this->data['attrs']['show_headers'],
                    'pageArticles' => $articles
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
                case "documentId":
                    $labelAttribute = $this->data['attrs']['label_document_id'];
                    break;
                case "answerId":
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
            'pageNumber' => Url::getParameter('browsePage') !== null ? Url::getParameter('browsePage') : 1,
            'pageSize' => $this->data['attrs']['internal_pagination'] ? 200 : $this->data['attrs']['per_page'],
            'truncate' => $this->data['attrs']['truncate_size'],
            'sortColumnId' => Url::getParameter('sortColumn'),
            'sortDirection' => Url::getParameter('sortDirection'),
            'status' => $this->data['attrs']['show_draft'],
            'answerListApiVersion' => $this->answerListApiVersion
        );
    }
}
