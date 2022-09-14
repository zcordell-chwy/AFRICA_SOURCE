<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use \RightNow\Utils\Url,
    \RightNow\Utils\Text,
    \RightNow\Utils\Config,
    \RightNow\Libraries\Search,
    RightNow\Utils\Okcs;

class SearchResult extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        require_once CPCORE . 'Utils/Okcs.php';
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        if ($this->sourceError()) return false;

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $resultSet = $search->addFilters(array(
            'limit' => array('value' => $this->data['attrs']['per_page']),
            'truncate' => array('value' => $this->data['attrs']['truncate_size']),
            'direction' => array('value' => Url::getParameter('dir'), 'key' => 'dir', 'type' => 'direction'),
            'docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
            'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']),
            'querySource' => array('value' => $this->data['attrs']['query_source'])
            ))->executeSearch();
        $this->data['results'] = $resultSet->searchResults['results']->results[0]->resultItems;

        if($this->data['attrs']['doc_id_navigation'] === 'true' && $resultSet->results[0]->docIdSearch) {
            \RightNow\Utils\Framework::setLocationHeader($resultSet->results[0]->redirectUrl);
        }

        if(is_null($this->data['results']))
            $this->data['results'] = array();

        if($resultSet->filters['errors'] !== null) {
            // Display Time out exception for Search Results in production area
            if(IS_OPTIMIZED && preg_match('/HTTP 0/', $resultSet->filters['errors']))
                $this->data['errors'] = $this->data['attrs']['request_time_out_msg'];
            else
                $this->data['errors'] = $this->reportError($resultSet->filters['errors']);
        }
        $historyData = $this->gatherHistoryData($resultSet);
        $this->data['historyData'] = (count($historyData) > 0) ? json_encode($historyData) : null;
        $filter = $search->getFilters();
        $filter['searchCacheId']['value'] = $resultSet->filters['searchCacheId']['value'];
        if (count($this->data['results']) === 0)
            $this->data['results'] = null;

        if(is_null($this->data['attrs']['answer_detail_url']) || empty($this->data['attrs']['answer_detail_url']))
            $answerPageUrl = Config::getConfig(CP_ANSWERS_DETAIL_URL);
        else
            $answerPageUrl = $this->data['attrs']['answer_detail_url'];

        $filter['okcsSearchSession']['value'] = $resultSet->searchState['session'];
        $filter['priorTransactionID']['value'] = $resultSet->searchState['priorTransactionID'];
        $filter['transactionID']['value'] = $resultSet->searchState['transactionID'];
        if ($filter) {
            $this->data['js'] = array(
                'filter'  => $filter,
                'sources' => $search->getSources(),
                'truncateSize' => $this->data['attrs']['truncate_size'],
                'okcsSearchSession' => $resultSet->searchState['session'],
                'transactionID' => $resultSet->searchState['transactionID'],
                'priorTransactionID' => $resultSet->searchState['priorTransactionID'],
                'answerPageUrl' => '/app/' . $answerPageUrl,
                'isValidVersion' => CP_FRAMEWORK_VERSION >= "3.6"
            );
        }

        if($this->data['results'] === null) {
            if($this->data['attrs']['hide_when_no_results'])
                $this->classList->remove('rn_SearchResult');
            else
                $this->classList->add('rn_NoSearchResult');
        }

        $okcs = new \RightNow\Utils\Okcs();
        $this->data['fileDescription'] = $this->data['js']['fileDescription'] = $okcs->getFileDescription();
    }

    /**
     * Checks for a source_id error. Emits an error message if a problem is found.
     * @return boolean True if an error was encountered, False if all is good
     */
    private function sourceError () {
        if (Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }
        return false;
    }

    /**
     * Gathers data needed for history management. Returns a subset of values from
     * search results for this purpose.
     * @param array $results Search results
     * @return array Results array with specific key/value pairs.
     */
    private function gatherHistoryData($results) {
        $resultArray = $results->toArray();
        $keysNeeded = array('filters', 'offset', 'query', 'searchResults', 'size', 'total');
        $historyData = array();
        foreach($keysNeeded as $key) {
            $historyData[$key] = $resultArray[$key];
        }
        return $historyData;
    }
}
