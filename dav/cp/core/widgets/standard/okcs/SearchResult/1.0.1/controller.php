<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;
use \RightNow\Utils\Url,
    \RightNow\Utils\Text,
    \RightNow\Utils\Config,
    \RightNow\Libraries\Search;

class SearchResult extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        require_once CPCORE . 'Utils/Okcs.php';
    }

    function getData() {
        if (!(Config::getConfig(OKCS_ENABLED))) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        
        if ($this->sourceError()) return false;
        
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $resultSet = $search->addFilters(array(
            'limit' => array('value' => $this->data['attrs']['per_page']),
            'truncate' => array('value' => $this->data['attrs']['truncate_size']),
            'direction' => array('value' => Url::getParameter('dir'))
            ))->executeSearch();
        $this->data['results'] = $resultSet->searchResults['results']->results[0]->resultItems;
        $filter = $search->getFilters();
        if (count($this->data['results']) === 0) 
            $this->data['results'] = null;

        if(is_null($this->data['attrs']['answer_detail_url']) || empty($this->data['attrs']['answer_detail_url']))
            $answerPageUrl = Config::getConfig(CP_ANSWERS_DETAIL_URL);
        else
            $answerPageUrl = $this->data['attrs']['answer_detail_url'];

        if ($filter) {
            $this->data['js'] = array(
                'filter'  => $filter,
                'sources' => $search->getSources(),
                'truncateSize' => $this->data['attrs']['truncate_size'],
                'okcsSearchSession' => $resultSet->searchState['session'],
                'transactionID' => $resultSet->searchState['transactionID'],
                'priorTransactionID' => $resultSet->searchState['priorTransactionID'],
                'answerPageUrl' => '/app/' . $answerPageUrl
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
}
