<? /* Originating Release: February 2019 */

namespace RightNow\Models;

use RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Libraries\SearchMappers\OkcsSearchMapper,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Api;

require_once CPCORE . 'Libraries/SearchMappers/OkcsSearchMapper.php';
require_once CORE_FILES . 'compatibility/Internal/OkcsApi.php';

/**
 * Methods for retrieving search
 */
class OkcsSearch extends SearchSourceBase {

    /**
     * Searches OKCSSEARCH.
     */
    private $okcsFileResource;

    public function __construct() {
        parent::__construct();
        $this->okcsApi = new \RightNow\compatibility\Internal\OkcsApi();
    }

    /**
    * Method to fetch the search results object
    * @param array $filters Filter list to fetch search results
    * @return object|null Search result
    */
    function search (array $filters = array()) {
        $contentSearchPerformed = true;
        if (strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'newTab') {
            $searchResults = $this->performSearch($filters);
        }
        else if (strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'clearFacet') {
            $searchResults = $this->performContentSearch($filters);
        }
        else if (strlen($filters['searchType']['value']) !== 0 && $filters['searchType']['value'] === 'FACET') {
            $searchResults = $this->performFacetSearch($filters);
        }
        // Page request if 'page' param is not null
        else if (strlen($filters['direction']['value']) !== 0 && $filters['direction']['value'] !== '0') {
            $searchResults = $this->performPageSearch($filters);
        }
        // Search request if 'query' param is not null
        else if (strlen($filters['query']['value']) !== 0) {
            $searchResults = $this->performContentSearch($filters);
        }
        else if (!is_null($filters['channelRecordID']['value'])) {
            $contentSearchPerformed = true;
            $categories = $this->CI->model('Okcs')->getChannelCategories($filters['channelRecordID']['value'], 'v1')->items;
            $searchResults = array('category' => $categories);
        }
        else {
            $result = new \stdClass();
            $result->results = array();
            $result->facets = array();
            $searchResults = $this->getResponseObject( array(
                                                            'searchState' => array(
                                                                                    'session' => $filters['okcsSearchSession']['value'], 
                                                                                    'transactionID' => $filters['transactionID']['value'], 
                                                                                    'priorTransactionID' => $filters['priorTransactionID']['value']), 
                                                            'searchResults' => array(
                                                                                        'page' => 0, 
                                                                                        'pageMore' => 0, 
                                                                                        'results' => $result, 
                                                                                        'facet' => 0, 
                                                                                        'selectedLocale' => null
                                                                                    )
                                                            ), 
                null);
        }
        $filters['sessionKey'] = $this->CI->model('Okcs')->decodeAndDecryptData($searchResults->result['searchState']['session']);
        if($searchResults->errors[0] !== null) {
            $filters['errors'] = $this->CI->model('Okcs')->formatErrorMessage($searchResults->errors[0]);
        }
        $resultMap = OkcsSearchMapper::toSearchResults($searchResults, $filters);
        if($contentSearchPerformed) {
            $resultAnswers = $resultMap->searchResults['results']->results[0]->resultItems;
            $answerLinks = array();
            if (count($resultAnswers) > 0) {
                foreach ($resultAnswers as $answer) {
                    $urlData = $this->getUrlData($answer);
                    $isPdfHtml = ($answer->fileType !== 'CMS-XML' && !$urlData['isAttachment'] && $answer->type !== 'template');
                    $clickThruData = array('trackedURL' => $answer->clickThroughUrl, 'answerID' => $answer->answerId, 'docID' => $answer->docId);
                    if(Text::stringContains($answer->href, 'answer_data')) {
                        $answerLinks[$answer->answerId] = array(
                            'UrlData' => Text::getSubstringAfter($answer->href, '/answer_data/'),
                            'answerUrl' => $urlData['url'],
                            'clickThruData' => $clickThruData
                        );
                        $answer->href = Text::getSubstringBefore($answer->href, '/answer_data');
                        $answer->dataHref = $urlData['answerUrl'];
                        $answer->href .= '/s/' . $answer->answerId;
                    }
                    else {
                        $answerLinks[$answer->answerId] = array('UrlData' => $answer->href, 'clickThruData' => $clickThruData, 'answerUrl' => $urlData['url']);
                        $answer->dataHref = $urlData['answerUrl'];
                        $answer->href = "/ci/okcsFattach/get/{$answer->answerId}";
                        if($answer->fileType === 'PDF') {
                            $answerLinks[$answer->answerId]['UrlData'] = $urlData['url'];
                        }
                    }
                    if($isPdfHtml) {
                        $answer->href = "/ci/okcsFile/get/{$answer->answerId}";
                        $answerLinks[$answer->answerId]['answerUrl'] = $urlData['answerUrl'];
                    }
                    else if($urlData['isAttachment']) {
                        $answer->href = '/ci/okcsFattach/get/' . $urlData['url'];
                        $answer->href = $answer->fileType === 'HTML' ? $answer->href . $urlData['anchor'] : $answer->href;
                    }
                }
            }
            $answerLinks['user'] = Framework::isLoggedIn() ? $this->CI->model('Contact')->get()->result->Login : 'guest';

            if(count($resultAnswers) > 0) {
                foreach ($resultAnswers as $answer) {
                    if($answer->fileType === 'CMS-XML' || ($answer->type === 'template')) {
                        $answer->href .= '_' . $filters['sessionKey'] . '/prTxnId/' . $resultMap->searchState['priorTransactionID'] .'#__highlight';
                    }
                    else if(!Text::stringContains($answer->href, 'okcsFattach')) {
                        $answer->href .= '/' . $filters['sessionKey'] . '/' . $resultMap->searchState['priorTransactionID'] . '/' . $answer->fileType . '#__highlight';
                    }
                    else if(!Text::stringContains($answer->href, '/s/')) {
                        $answer->href .= '/' . $answer->fileType . '/' . $filters['sessionKey'] . '/' . $resultMap->searchState['priorTransactionID'] . '/' . $answer->answerId . '#__highlight';
                    }
                }
            }
        }
        $searchText = $filters['query']['value'];
        if(preg_match('/' . $filters['docIdRegEx']['value'] . '/', null) === false) {
            Api::phpoutlog("getDocByIdFromSearchOrIm - Error: Invalid docIdRegEx pattern- " . $filters['docIdRegEx']['value']);
        }
        else if(!is_null($filters['docIdRegEx']['value']) && !empty($filters['docIdRegEx']['value']) && preg_match('/' . $filters['docIdRegEx']['value'] . '/', $searchText) === 1 && $searchResults->result['searchResults']['page'] === 0) {
            $isHighlightingEnabled = $searchResults->result['searchResults']['results']->results[0]->resultItems[0]->isHighlightingEnabled;
            $searchResults = $this->CI->model("Okcs")->getDocByIdFromSearchOrIm($searchResults, $searchText);
            $searchResults->result['searchResults']['results']->results[0]->resultItems[0]->isHighlightingEnabled = $isHighlightingEnabled;
        }
        return $this->getResponseObject($resultMap, is_string($searchResults) ? $searchResults : null);
    }

    /**
     * Searches answers for the search text to display in the new tab.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performSearch (array $filters) {
        $query = trim($filters['query']['value']);
        if(strlen($query) === 0)
            return null;
        $searchFilters = array(
            'kw' => $query,
            'loc' => $filters['locale']['value'],
            'facet' => $filters['facet']['value'],
            'page' => $filters['page']['value'],
            'querySource' => $filters['querySource']['value']
        );
        try {
            $result = $this->CI->model('Okcs')->getSearchResultForNewTab($searchFilters);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers for the search text.
     * @param array $filters Filter values
     * @return string|array|null Error message or results
     */
    private function performContentSearch (array $filters) {
        $query = trim($filters['query']['value']);
        if (!empty($filters['prod']['value']))
            $facets = 'CMS-PRODUCT.' . trim($filters['prod']['value']);

        if (!empty($filters['cat']['value'])) {
            $cat = 'CMS-CATEGORY_REF.' . trim($filters['cat']['value']);
            $facets = is_null($facets) ? $cat : $facets . ',' . $cat;
        }

        if (!empty($filters['facet']['value']))
            $facets = trim($filters['facet']['value']);

        $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
        if(Text::endsWith($searchSession, '_SEARCH'))
            $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');

        $filters = array(
            'query' => $query,
            'locale' => !$filters['loc']['value'] ? $filters['locale']['value'] : $filters['loc']['value'],
            'session' => $searchSession,
            'transactionID' => $filters['transactionID']['value'],
            'collectFacet' => $filters['collectFacet']['value'],
            'priorTransactionID' => $filters['priorTransactionID']['value'],
            'facets' => $facets,
            'querySource' => $filters['querySource']['value']
        );

        try {
            $result = $this->CI->model('Okcs')->getSearchResult($filters);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers for the selected facet.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performFacetSearch (array $filters) {
        try {
            $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
            if(Text::endsWith($searchSession, '_SEARCH'))
                $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
            $facetFilter = array(
                'session' => $searchSession,
                'transactionID' => $filters['transactionID']['value'],
                'priorTransactionID' => $filters['priorTransactionID']['value'],
                'facet' => $filters['facet']['value'],
                'resultLocale' => $filters['loc']['value'],
                'querySource' => $filters['querySource']['value']
            );
            $result = $this->CI->model('Okcs')->getAnswersForSelectedFacet($facetFilter);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * Searches answers for the requested page.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performPageSearch (array $filters) {
        try {
            $searchSession = $this->CI->model('Okcs')->decodeAndDecryptData($filters['okcsSearchSession']['value']);
            if(Text::endsWith($searchSession, '_SEARCH'))
                $searchSession = Text::getSubstringBefore($searchSession, '_SEARCH');
            $pageFilter = array(
                'session' => $searchSession,
                'priorTransactionID' => $filters['priorTransactionID']['value'],
                'page' => intval($filters['page']['value']) - 1,
                'type' => $filters['direction']['value'],
                'resultLocale' => $filters['loc']['value'],
                'querySource' => $filters['querySource']['value']
            );
            $result = $this->CI->model('Okcs')->getSearchPage($pageFilter);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
        return $result;
    }
    
    /**
     * For the given filter type name, returns the
     * values for the filter.
     * @param  string $filterType Filter type
     * @return array Filter values
     */
    function getFilterValuesForFilterType ($filterType) {
        $sortOption = new KnowledgeFoundation\ContentSortOptions();
        $metaData = $sortOption::getMetadata();
        if ($filterType === 'sort') {
            $result = $metaData->SortField->named_values;
        }
        else if ($filterType === 'direction') {
            $result = $metaData->SortOrder->named_values;
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * This method returns an array of url data
     * @param object $result Search result answer
     * @return array Url data
     */
    function getUrlData($result) {
        $anchor = "";
        if ($result->title && $result->title->url !== null) {
            $linkUrl = $result->title->url;
        }
        else if ($result->link) {
            $linkUrl = $result->link;
            $data = $this->getValidatedLinkUrl($linkUrl, true);
            $linkUrl = $data['linkUrl'];
            $anchor = $data['anchor'];
        }
        else if ($result->clickThroughLink && Text::stringContains($result->clickThroughLink, 'turl=')) {
            $linkUrl = Text::getSubstringAfter($result->clickThroughLink, 'turl=');
            $data = $this->getValidatedLinkUrl($linkUrl, false);
            $linkUrl = $data['linkUrl'];
            $anchor = $data['anchor'];
        }
        $highlightUrl = $result->highlightedLink;
        if (Text::stringContains($linkUrl, 'IM:')) {
            $articleData = explode(':', $linkUrl);
            $answerLocale = $articleData[3];
            $answerStatus = $articleData[4];
            $answerID = $articleData[6];
            if(Text::stringContains($linkUrl, ':#') !== false) {
                $answerID = strtoupper($answerStatus) === 'PUBLISHED' ? $answerID : $answerID . "_d";
                $attachment = Text::getSubstringAfter($linkUrl, ':#');
                $answerUrl = "/ci/okcsFattach/getFile/{$answerID}/{$attachment}";
                if(Text::stringContains($highlightUrl, '#xml='))
                    $attachment .= '#xml=' . str_replace('%23', '', Text::getSubstringAfter($highlightUrl, '#xml='));
                $attachmentUrl = $answerID . "/file/" . Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe('ATTACHMENT:'.$attachment));
                return array('isAttachment' => true, 'url' => $attachmentUrl, 'answerUrl' => $answerUrl, 'anchor' => $anchor);
            }
            if(!is_null($answerID))
                $linkUrl = "/a_id/{$answerID}";
            if(!is_null($answerLocale))
                $linkUrl .= "/loc/{$answerLocale}";
            return array('isAttachment' => false, 'url' => $linkUrl, 'answerUrl' => $linkUrl, 'anchor' => $anchor);
        }
        return array('isAttachment' => false, 'url' => $result->href, 'answerUrl' => $linkUrl);
    }

    /**
    * This method returns key value from the Url.
    * Sample url format /key1/value1/key2/value2
    * @param string $url Url
    * @param string $key Url parameter key
    * @return string Url parameter value
    */
    private function getUrlParameter($url, $key) {
        if (preg_match("/&$key=([^&]*)(&|$)/", $url, $matches)) return $matches[1];
    }
    
    /**
    * Method to check if link url consists of # and return the proper url
    * @param string $linkUrl Url
    * @param boolean $isHTML This flag is used to decide whether linkurl should be decode or send directly
    * @return array populated with required header values
    */
    function getValidatedLinkUrl($linkUrl, $isHTML) {
        if(Text::stringContains($linkUrl, '#') && $isHTML){
            $fileName = Text::getSubstringAfter($linkUrl, '#');
            $linkUrl = Text::getSubstringBefore($linkUrl, '#');
            if(Text::stringContains($fileName, '#')){
                $anchor = '#' . Text::getSubstringAfter($fileName, '#');
                $fileName = Text::getSubstringBefore($fileName, '#');
            }
            $linkUrl .= '#'.$fileName;          
        }
        else {
            if(Text::stringContains($linkUrl, '#')) {
                $anchor = Text::getSubstringAfter($linkUrl, '#');
                $linkUrl = Text::getSubstringBefore($linkUrl, '#');
            }
            $linkUrl = urldecode($linkUrl);               
        }
        return array('linkUrl' => $linkUrl, 'anchor' => $anchor); 
    }
}

