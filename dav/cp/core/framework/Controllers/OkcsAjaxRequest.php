<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\ActionCapture,
    RightNow\Utils\Config,
    RightNow\Utils\Okcs,
    RightNow\Utils\Text,
    RightNow\Libraries\AbuseDetection;

/**
* Generic controller endpoint for standard OKCS widgets to make requests to retrieve data. Nearly all of the
* methods in this controller echo out their data in JSON so that it can be received by the calling JavaScript.
*/
final class OkcsAjaxRequest extends Base
{
    public function __construct()
    {
        parent::__construct();
        require_once CPCORE . 'Utils/Okcs.php';
    }

    /**
    * Method to fetch data through OKCS APIs
    * @internal
    */
    public function getOkcsData() {
        $filters = json_decode($this->input->post('filters'), true);
        $postAction = $this->input->post('action');
        if (strlen($this->input->post('doc_id')) !== 0) {
            $this->getIMContent();
        }
        else if ($this->input->post('getMoreAnswerNotif') === 'getMoreAnswerNotif') {
            $this->getMoreAnswerNotif();
        }
        else if (strlen($this->input->post('clickThruLink')) !== 0) {
            $this->clickThru();
        }
        else if($filters['isRecommendations']['value'] !== null) {
            $this->browseRecommendations();
        }
        else if($filters['channelRecordID']['value'] !== null || $filters['currentSelectedID']['value'] !== null || strlen($this->input->post('answerListApiVersion')) !== 0) {
            $this->browseArticles();
        }
        else if (strlen($this->input->post('deflected')) !== 0) {
            $this->getContactDeflectionResponse();
        }
        else if (strlen($this->input->post('categoryId')) !== 0) {
            $this->getChildCategories();
        }
        else if (strlen($this->input->post('getMoreProdCatFlag')) !== 0) {
            $this->getMoreProdCat();
        }
        else if (strlen($this->input->post('surveyRecordID')) !== 0) {
            $this->submitRating();
        }
        else if (strlen($this->input->post('rating')) !== 0) {
            $this->submitSearchRating();
        }
        else if ($postAction === 'Unsubscribe') {
            $this->unsubscribeAnswer();
        }
        else if ($postAction === 'Subscribe') {
            $this->addSubscription();
        }
        else if ($postAction === 'OkcsRecentAnswers') {
            $this->getOkcsRecentAnswers();
        }
        else if ($this->input->post('noOfSuggestions') !== 0) {
            $this->getUpdatedRecentSearches();
        }
    }

    /**
    * Method to add subscription for the IM Answer content.
    */
    public function addSubscription() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $subscriptionData = array(
            'answerID' => $this->input->post('answerID'),
            'documentID' => $this->input->post('docId'),
            'versionID' => $this->input->post('versionID')
        );

        $response = $this->model('Okcs')->addSubscription($subscriptionData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'addSubscription | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if($response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to create recommended content
    */
    public function createRecommendation() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $recommendationData = array(
            'contentTypeRecordId' => $this->input->post('contentTypeRecordId'),
            'contentTypeReferenceKey' => $this->input->post('contentTypeReferenceKey'),
            'contentTypeName' => $this->input->post('contentTypeName'),
            'caseNumber' => $this->input->post('caseNumber'),
            'comments' => $this->input->post('comments'),
            'title' => $this->input->post('title'),
            'priority' => $this->input->post('priority'),
            'contentRecordId' => $this->input->post('contentRecordId'),
            'answerId' => $this->input->post('answerId'),
            'documentId' => $this->input->post('documentId'),
            'isRecommendChange' => $this->input->post('isRecommendChange')
        );

        $response = $this->model('Okcs')->createRecommendation($recommendationData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'createRecommendation | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if($response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
        }
        else {
            $this->_renderJSON(array('success' => Config::getMessage(THANK_YOU_TAKING_MAKE_RECOMMENDATION_LBL)));
        }
    }

    /**
    * Method to unsubscribe an answer.
    */
    public function unsubscribeAnswer() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $response = $this->model('Okcs')->unsubscribeAnswer($this->input->post('subscriptionID'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'unsubscribeAnswer | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }

        if($response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch content types
    */
    public function getContentType() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }

        $contentTypeList = trim($this->input->post('contentTypeList'));
        $userPreferredList = explode(",", $contentTypeList);
        $allContentTypes = $this->model('Okcs')->getChannels('v1');

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getContentType | OkcsAjaxRequestController');
            $allContentTypes->ajaxTimings = $timingArray;
        }

        if ($allContentTypes->error !== null) {
            $response = $this->getResponseObject($allContentTypes);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
            $this->_renderJSON($response);
            return;
        }

        if(empty($contentTypeList)) {
            $validContentTypes = array();
            foreach ($allContentTypes->items as $item) {
                if($item->allowRecommendations) {
                    array_push($validContentTypes, $item);
                }
            }
            $this->_renderJSON($validContentTypes);
        }
        else if ($allContentTypes->items !== null) {
            $validUserPreferredList = array();
            $invalidChannel = array();
            for($i = 0; $i < count($userPreferredList); $i++) {
                $isValidChannel = false;
                foreach ($allContentTypes->items as $item) {
                    if(strtoupper($item->referenceKey) === strtoupper(trim($userPreferredList[$i]))) {
                        $isValidChannel = true;
                        if($item->allowRecommendations) {
                            array_push($validUserPreferredList, $item);
                        }
                        break;
                    }
                }
                if(!$isValidChannel)
                    array_push($invalidChannel, $userPreferredList[$i]);
            }
            if(count($invalidChannel) == 0) {
                $this->_renderJSON($validUserPreferredList);
            }
            else {
                $this->_renderJSON(array('failure' => sprintf(Config::getMessage(PCT_S_NOT_FND_FOR_THE_CONTENT_TYPE_LBL), implode(", ", $invalidChannel))));
            }
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to get updated recent searches information.
    */
    public function getUpdatedRecentSearches() {
        $response = $this->model('Okcs')->getUpdatedRecentSearches($this->input->post('noOfSuggestions'));
        $this->_renderJSON($response);
    }

    /**
    * Method to sort subscriptions based on the docId
    */
    public function sortNotifications() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $subscriptionList = $this->model('Okcs')->sortNotifications($this->input->post('sortColumn'), $this->input->post('direction'));
        if(IS_DEVELOPMENT){
            $subscriptionList->ajaxTimings = $this->calculateTimeDifference($startTime, 'sortNotifications | OkcsAjaxRequestController');
        }
        if($subscriptionList->errors) {
            $list = $this->getResponseObject($subscriptionList);
            $list['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        else {
            $titleLength = $this->input->post('titleLength') ?: 0;
            $maxRecords = $this->input->post('maxRecords');
            $list = array();
            if ($subscriptionList && !$subscriptionList->error && count($subscriptionList->items) > 0) {
                if($maxRecords > 0) {
                    $subscriptions = array_slice($subscriptionList->items, 0, $maxRecords);
                    foreach ($subscriptions as $document) {
                        $subscriptionID = $document->recordId;
                        $dateAdded = $this->model('Okcs')->processIMDate($document->dateAdded);
                        $document = $document->content;
                        $document->title = Text::escapeHtml($document->title);
                        $item = array(
                            'documentId'        => $document->documentId,
                            'answerId'          => $document->answerId,
                            'title'             => $titleLength === 0 ? $document->title : Text::truncateText($document->title, $titleLength),
                            'expires'           => $dateAdded,
                            'subscriptionID'    => $subscriptionID
                        );
                        array_push($list, $item);
                    }
                }
            }
        }
        $this->_renderJSON($list);
    }

    /**
    * Method to call clickthru OKCS API.
    */
    private function clickThru() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $clickThruLink = $this->model('Okcs')->decodeAndDecryptData($this->input->post('clickThruLink'));
        $clickThroughInput = array(
           'answerId' => $this->getUrlParameter($clickThruLink, 'answerId'),
           'searchSession' => $this->getUrlParameter($clickThruLink, 'searchSession'),
           'prTxnId' => $this->getUrlParameter($clickThruLink, 'priorTransactionId'),
           'txnId' => $this->getUrlParameter($clickThruLink, 'txn') ,
           'ansType' => $this->input->post('answerType'));
        $result = $this->model('Okcs')->getHighlightedHTML($clickThroughInput);

        if(IS_DEVELOPMENT){
            $stopTime = microtime(true);
            $duration = $stopTime - $startTime;
            $timingArray = Okcs::getCachedTimings('timingCacheKey');
            array_push($timingArray, array('key' => 'clickThru | OkcsAjaxRequestController', 'value' => $duration));

            $result['ajaxTimings'] = $timingArray;
        }
        echo json_encode($result);
    }

    /**
    * Method to get Recommendations View
    */
    public function recommendationsView() {
        $recommendationsView = $this->model('Okcs')->getRecommendationsView($this->input->post('recordId'));
        if($recommendationsView->errors) {
            $recommendationsView = $this->getResponseObject($recommendationsView);
            $recommendationsView['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        else {
            $recommendationsView->dateAdded = $this->model('Okcs')->processIMDate($recommendationsView->dateAdded);
        }
        $this->_renderJSON($recommendationsView);
    }

    /**
    * Method to get recently viewed OKCS answers
    */
    public function getOkcsRecentAnswers() {
        $widgetContentCount = $this->input->post('contentCount');
        $response = $this->model('Okcs')->getOkcsRecentAnswers($widgetContentCount);
        $this->_renderJSON($response);
    }

    /**
    * Method to call browseArticles OKCS API.
    */
    private function browseArticles() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $filters = json_decode($this->input->post('filters'), true);
        $contentType = $filters['channelRecordID']['value'] !== null ? $filters['channelRecordID']['value'] : '';
        $currentSelectedID = $filters['currentSelectedID']['value'];
        $productRecordID = $filters['productRecordID']['value'];
        $categoryRecordID = $filters['categoryRecordID']['value'];
        $isProductSelected = $filters['isProductSelected']['value'];
        $isCategorySelected = $filters['isCategorySelected']['value'];
        $categoryFetchFlag = $isProductSelected !== null || $isCategorySelected !== null ? false : true;
        $browsePage = $filters['browsePage']['value'] !== null ? $filters['browsePage']['value'] : 0;
        $pageSize = $filters['pageSize']['value'] !== null ? $filters['pageSize']['value'] : 10;
        $limit = $filters['limit']['value'];
        $columnID = $filters['sortColumn']['value'] !== null ? $filters['sortColumn']['value'] : "publishDate";
        $sortDirection = $filters['sortDirection']['value'] !== null ? $filters['sortDirection']['value'] : "DESC";
        $answerListApiVersion = $this->input->post('answerListApiVersion');
        $productCategoryApiVersion = $this->input->post('productCategoryApiVersion');

        if($productRecordID === null)
            $isProductSelected = null;

        if($categoryRecordID === null)
            $isCategorySelected = null;

        $isSelected = $currentSelectedID === $productRecordID ? $isProductSelected : $isCategorySelected;

        if($isProductSelected)
            $category = $productRecordID;

        if ($isCategorySelected) {
            if($category !== null) {
                $category .= ':' . $categoryRecordID;
            }
            else {
                $category = $categoryRecordID;
            }
        }

        $filter = array(
            'type'             => $filters['type']['value'],
            'status'           => $filters['a_status']['value'],
            'truncate'         => $filters['truncate']['value'],
            'limit'            => $limit,
            'contentType'      => $contentType,
            'category'         => $category,
            'pageNumber'       => $browsePage,
            'pageSize'         => $pageSize,
            'sortColumnId'     => $columnID,
            'sortDirection'    => $sortDirection,
            'categoryRecordID' => $categoryRecordID,
            'productRecordID'  => $productRecordID,
            'answerListApiVersion' => $answerListApiVersion,
            'productCategoryAnsList' => $filters['productCategoryAnsList']['value'],
            'contentTypeAnsList' => $filters['contentTypeAnsList']['value']
        );
        $articleResult = $this->model('Okcs')->getArticlesSortedBy($filter);
        $response = array(
            'error'           => ($articleResult->errors) ? $articleResult->error->errorCode . ': ' .
                                 $articleResult->error->externalMessage : null,
            'articles'        => $articleResult->items,
            'filters'         => '',
            'columnID'        => $columnID,
            'sortDirection'   => $sortDirection,
            'selectedChannel' => $contentType,
            'hasMore'         => $articleResult->hasMore,
            'currentPage'     => $browsePage,
            'isRecommendationAllowed' => $filters['isRecommendationAllowed']['value']
        );

        if (strlen($category) === 0 && strlen($currentSelectedID) === 0 && $categoryFetchFlag){
            $response["category"] = $this->model('Okcs')->getChannelCategories($contentType, $productCategoryApiVersion);
        }
        else {
            $response["categoryRecordID"] = $currentSelectedID;
        }

        if($isSelected)
            $response["isCategorySelected"] = $isSelected;

        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'browseArticles | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
     * Method to call getRecommendationsSortedBy OKCS API.
     * Retrieves recommendations based on the specified filter parameters for a logged-in user.
     */
    private function browseRecommendations() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $filters = json_decode($this->input->post('filters'), true);
        $browsePage = $filters['browsePage']['value'] !== null ? $filters['browsePage']['value'] : 0;
        $pageSize = $filters['pageSize']['value'] !== null ? $filters['pageSize']['value'] : 10;
        $offSet = $filters['pageSize']['value'] * ($filters['browsePage']['value'] - 1);
        $sortColumn = $filters['sortColumn']['value'] !== null ? $filters['sortColumn']['value'] : "dateAdded";
        $sortDirection = $filters['sortDirection']['value'] !== null ? $filters['sortDirection']['value'] : "DESC";
        $manageRecommendationsApiVersion = $this->input->post('manageRecommendationsApiVersion');
        $filter = array(
            'type'             => '',
            'offSet'           => $offSet,
            'pageNumber'       => $browsePage,
            'pageSize'         => $pageSize,
            'sortColumnId'     => $sortColumn,
            'sortDirection'    => $sortDirection,
            'manageRecommendationsApiVersion' => $manageRecommendationsApiVersion
        );
        $recommendationsResult = $this->model('Okcs')->getRecommendationsSortedBy($filter);
        if($recommendationsResult->errors) {
            $response = $this->getResponseObject($recommendationsResult);
        }
        else {
            $response = array(
                'recommendations' => $recommendationsResult->items,
                'filters'         => '',
                'columnID'        => $sortColumn,
                'sortDirection'   => $sortDirection,
                'hasMore'         => $recommendationsResult->hasMore,
                'currentPage'     => $browsePage
            );
        }
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'browseRecommendations | OkcsAjaxRequestController');
            $response['ajaxTimings'] = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to fetch details of an OKCS IM content
    */
    private function getIMContent() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $docID = $this->input->post('doc_id');
        $highlightedLink = $this->model('Okcs')->decodeAndDecryptData($this->input->post('highlightedLink'));
        $searchData = array('answerId' => $this->getUrlParameter($highlightedLink, 'answerId'), 'searchSession' => $this->getUrlParameter($highlightedLink, 'searchSession'), 'prTxnId' => $this->getUrlParameter($highlightedLink, 'priorTransactionId'), 'txnId' => $this->getUrlParameter($highlightedLink, 'txn'));
        $answerType = $this->input->post('answerType');

        //If highlighting is enabled
        if ($answerType === 'CMS-XML') {
            if (strlen($highlightedLink) !== 0) {
                $response = $this->model('Okcs')->getAnswerViewData($docID, null, $searchData, '', 'v1');
            }
            else {
                $response = $this->model('Okcs')->getAnswerViewData($docID);
            }
        }
        else {
            if (strlen($highlightedLink) !== 0) {
                $response = $this->model('Okcs')->processIMContent($docID, 'v1', $searchData, $answerType);
            } 
            else {
                $response = $this->model('Okcs')->processIMContent($docID);
            }
        }

        if ($answerType !== 'HTML' && $response['content'] !== null) {
            $contentTypeSchema = $this->model('Okcs')->getIMContentSchema($response['contentType']->referenceKey, $response['locale']->recordID, 'v1');
            if ($contentTypeSchema->error === null) {
                $okcs = new \RightNow\Utils\Okcs();
                $channelData = $okcs->getAnswerView($response['content'], $contentTypeSchema['contentSchema'], "CHANNEL", $response['resourcePath']);
                $response['content'] = $channelData;
                if($contentTypeSchema['metaSchema'] !== null) {
                    $metaData = $okcs->getAnswerView($response['metaContent'], $contentTypeSchema['metaSchema'], "META", $response['resourcePath']);
                    $response['metaContent'] = $metaData;
                }
            }
            else {
                return false;
            }
        }
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getIMContent | OkcsAjaxRequestController');
        }
        $this->_renderJSON(array(
            'error' => ($response->errors) ? (string) $response->error : null,
            'id' => $docID,
            'contents' => $response,
            'ajaxTimings' => $timingArray
        ));
    }

    /**
    * Method to call getContactDeflectionResponse OKCS API.
    */
    private function getContactDeflectionResponse() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getContactDeflectionResponse($this->input->post('priorTransactionID'), $this->input->post('deflected'), $this->input->post('okcsSearchSession'));
        if(IS_DEVELOPMENT){
            $response->ajaxTimings = $this->calculateTimeDifference($startTime, 'getContactDeflectionResponse | OkcsAjaxRequestController');
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to call getChannelCategories OKCS API to pull more categories for the same channel. This method should not be timed as it will cache results that it gets from API.
    */
    private function getMoreProdCat() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getChannelCategories($this->input->post('contentType'), $this->input->post('productCategoryApiVersion'), $this->input->post('offset'));
        if($response->items !== null)
            $this->_renderJSON($response);
        else
            $this->_renderJSON($this->getResponseObject($response));
    }

    /**
    * Method to call getChildCategories OKCS API to pull children of a parent category.
    */
    private function getChildCategories() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->getChildCategories($this->input->post('categoryId'), $this->input->post('limit'), $this->input->post('offset'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'getChildCategories | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        if($response->items !== null)
            $this->_renderJSON($response);
        else
            $this->_renderJSON($this->getResponseObject($response));
    }

    /**
    * Method to submit Info Manager document rating.
    */
    private function submitRating() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        $ratingData = array(
            'answerID' => $this->input->post('answerID'),
            'surveyRecordID' => $this->input->post('surveyRecordID'),
            'answerRecordID' => $this->input->post('answerRecordID'),
            'contentRecordID' => $this->input->post('contentRecordID'),
            'localeRecordID' => $this->input->post('localeRecordID'),
            'ratingPercentage' => $this->input->post('ratingPercentage'),
            'answerComment' => $this->input->post('answerComment')
        );
        $response = $this->model('Okcs')->submitRating($ratingData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'submitRating | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        if($response->errors) {
            $response = $this->getResponseObject($response);
            $response['result'] = array('failure' => Config::getMessage(ERROR_PLEASE_TRY_AGAIN_LATER_MSG));
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to submit search rating.
    */
    private function submitSearchRating() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        $response = $this->model('Okcs')->submitSearchRating($this->input->post('rating'), $this->input->post('feedback'), $this->input->post('priorTransactionID'), $this->input->post('okcsSearchSession'));
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'submitSearchRating | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        $this->_renderJSON($response);
    }

    /**
    * Method to record search answer clickthru.
    */
    public function recordClickThru() {
        $clickThruLink = $this->input->post('clickThruLink');
        $clickThruLink = Text::getSubstringBefore($clickThruLink, '#');
        if (Text::stringContains($clickThruLink, '/ci/okcsFile')) {
            $clickThruLink = Text::getSubstringAfter($clickThruLink, '/get/');
            $clickThruData = explode('/', $clickThruLink);
            $answerId = $clickThruData[0];
            $searchSession = $clickThruData[1];
            $prTxnId = $clickThruData[2];
            $ansType = $clickThruData[3];
            $txnId = $clickThruData[4];
        } else if (Text::stringContains($clickThruLink, '/ci/okcsFattach')) {
            $clickThruLink = Text::getSubstringAfter($clickThruLink, '/file/');
            $clickThruData = explode('/', $clickThruLink);
            $ansType = $clickThruData[1];
            $searchSession = $clickThruData[2];
            $txnId = $prTxnId = $clickThruData[3];
            $answerId = $clickThruData[4];
        } else {
            $answerData = $this->getUrlParameter($clickThruLink, 's');
            $answerId = Text::getSubstringBefore($answerData, '_');
            $searchSession = Text::getSubstringAfter($answerData, '_');
            $prTxnId = $this->getUrlParameter($clickThruLink, 'prTxnId');
            $txnId = $this->getUrlParameter($clickThruLink, 'txnId');
            $ansType = null;
        }
        $clickThroughInput = array(
           'answerId' => $answerId,
           'searchSession' => $searchSession,
           'prTxnId' => $prTxnId,
           'txnId' => $txnId,
           'ansType' => $ansType
        );
        $result = $this->model('Okcs')->getHighlightedHTML($clickThroughInput);
        $this->_renderJSON($result);
    }

    /**
    * This method returns key value from the Url.
    * Sample url format /key1/value1/key2/value2
    * @param string $url Url
    * @param string $key Url parameter key
    * @return string Url parameter value
    */
    private function getUrlParameter($url, $key) {
        if (preg_match("/\/$key\/([^\/]*)(\/|$)/", $url, $matches)) return $matches[1];
    }

    /**
    * Method to calculate time difference.
    * @param string $startTime StartTime
    * @param string $value TimingArrayKey
    * @return array  timing array
    */
    private function calculateTimeDifference($startTime, $value) {
        $stopTime = microtime(true);
        $duration = $stopTime - $startTime;
        $timingArray = Okcs::getCachedTimings('timingCacheKey');
        if($timingArray === null) {
            Okcs::setTimingToCache('timingCacheKey', array(array('key' => $value, 'value' => $duration)));
            $timingArray = Okcs::getCachedTimings('timingCacheKey');
        }
        else
            array_push($timingArray, array('key' => $value, 'value' => $duration));

        return $timingArray;
    }

    /**
    * Method to get formatted Response Object to display errors in AJAX responses
    * @param object $response Response object from the model layer which needs to be formatted
    * @return array Formatted error response
    */
    private function getResponseObject($response){
        if($response->errors){
            $responseObject = array();
            if($response->ajaxTimings){
                $responseObject['ajaxTimings'] = $response->ajaxTimings;
            }
            $responseObject['isResponseObject'] = true;
            $responseObject['result'] = false;
            $responseObject['errors'] = array();
            foreach($response->errors as $error){
                $externalMessage = $this->model('Okcs')->formatErrorMessage($error);
                $displayToUser = IS_DEVELOPMENT ? true : false;
                array_push($responseObject['errors'], array('externalMessage' => $externalMessage, 'displayToUser' => $displayToUser));
            }
            return $responseObject;
        }
        return $response;
    }

    /**
    * Method to send Email for an OKCS answer
    */
    public function sendOkcsEmailAnswerLink() {
        if(IS_DEVELOPMENT){
            $startTime = microtime(true);
        }
        AbuseDetection::check();
        // Populate all email related labels into an array to be sent to the model layer
        $emailData = array(
            'sendTo' => $this->input->post('to'),
            'name' => $this->input->post('name'),
            'from' => $this->input->post('from'),
            'answerID' => $this->input->post('aId'),
            'title' => $this->input->post('title'),
            'emailHeaderLabel' => $this->input->post('emailHeader'),
            'emailSenderLabel' => $this->input->post('emailSender'),
            'summaryLabel' => $this->input->post('summaryLabel'),
            'answerViewLabel' => $this->input->post('answerViewLabel'),
            'emailAnswerToken' => $this->input->post('emailAnswerToken')
        );
        $response = $this->model('Okcs')->emailOkcsAnswerLink($emailData);
        if(IS_DEVELOPMENT){
            $timingArray = $this->calculateTimeDifference($startTime, 'sendOkcsEmailAnswerLink | OkcsAjaxRequestController');
            $response->ajaxTimings = $timingArray;
        }
        $this->_renderJSON($response);
    }
    
    /**
    * Method to get more Answer Notifications based on offset.
    */
    public function getMoreAnswerNotif() {
        $response = $this->model('Okcs')->getPaginatedSubscriptionList($this->input->post('offset'));
        $this->_renderJSON($response);
    }
}
