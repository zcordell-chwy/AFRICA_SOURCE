<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class SearchRating extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $searchQuery = Url::getParameter('kw');
        $searchData = $this->CI->model('Okcs')->getSearchResultData($filters = array('query' => $searchQuery));
        $searchFlag = count($searchData->result['searchResults']['results']->results) > 0;
        $this->data['js'] = array(
            'priorTransactionID' => $this->CI->model('Okcs')->getPriorTransactionID(),
            'okcsSearchSession' => $this->CI->model('Okcs')->getSession(),
            'searchFlag' => $searchFlag
        );
        if($this->data['attrs']['source_id']) {
            $search = \RightNow\Libraries\Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js']['filter'] = $search->getFilters();
            $this->data['js']['sources'] = $search->getSources();
        }
    }
}
