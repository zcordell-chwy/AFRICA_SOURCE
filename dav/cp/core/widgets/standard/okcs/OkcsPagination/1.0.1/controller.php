<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Url;

class OkcsPagination extends \RightNow\Widgets\SourcePagination {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        if (!(\RightNow\Utils\Config::getConfig(OKCS_ENABLED))) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THE_OKCSENABLED_CFG_SET_MUST_BE_MSG));
            return false;
        }
        if($this->data['attrs']['source_id'] === 'OKCSBrowse') {
            $defaultMaxPageSize = 10;
            $search = Search::getInstance($this->data['attrs']['source_id']);
            $pageMore = $this->CI->model('Okcs')->getIMArticles();
            $results->total = $pageMore ? $defaultMaxPageSize : 0;
            $this->data['js'] = $this->populateJsData($results, $search->getFilters());
        }
        else {
            parent::getData();
        }
    }

    /**
    * Method to to populate pagination data
    * @param object $results Search results
    * @param array $filters List of filters
    * @return array Pagination Details
    */
    protected function populateJsData ($results, array $filters) {
        if($this->data['attrs']['source_id'] === 'OKCSSearch') {
            $pageSize = $results->size;
            $pageNumber = Url::getParameter('page') !== null ? Url::getParameter('page') : 0;
            $pageSearch = intval($pageNumber) === 1 && (Url::getParameter('dir') === null || Url::getParameter('dir') === 'backward') ? true : false;
            $currentPage = intval($pageNumber === 0) || $pageSearch ? 0 : intval($filters['page']['value']) + 1;
        }
        else if ($this->data['attrs']['source_id'] === 'OKCSBrowse') {
            $pageSize = Url::getParameter('pageSize') !== null ? Url::getParameter('pageSize') : 0;
            $pageNumber = Url::getParameter('browsePage') !== null ? Url::getParameter('browsePage') : 0;
            $currentPage = intval($pageNumber) + 1;
        }
        return array(
            'size'          => $pageSize,
            'total'         => $results->total,
            'offset'        => $results->offset,
            'currentPage'   => $currentPage,
            'numberOfPages' => 0,
            'sources'       => $response->sources,
            'filter'        => $filters['page'],
            'limit'         => $filters['limit']['value'],
            'okcsAction'    => $this->data['attrs']['source_id'] === 'OKCSBrowse' ? 'browse' : ''
        );
    }
}
