<?php /* Originating Release: February 2019 */

namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Libraries\Search,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class Facet extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $filters = array('truncate' => array('value' => $this->data['attrs']['truncate_size']),
                        'docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
                        'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']));
        if (!is_null(Url::getParameter('searchType')))
            $filters['searchType'] = array('value' => Url::getParameter('searchType'));
        $facets = $search->addFilters($filters)->executeSearch()->searchResults['results']->facets;
        $filter = $search->getFilters();

        if ($facets !== null) {
            foreach ($facets as $facetItem) {
                foreach($facetItem as $key => $value){
                    if(!$this->getErrorDetails($key, $value)) {
                        return false;
                    }
                }
            }
        }
        if(isset($this->data['attrs']['top_facet_list']) && !empty($this->data['attrs']['top_facet_list']) ){
            $facets = $this->facetsOrderbyList($facets);
        }
        $this->data['facets'] = $facets;
        if ($filter) {
            $this->data['js'] = array(
                'filter'  => $filter,
                'sources' => $search->getSources(),
                'facets'  => json_encode($facets)
            );
        }
        if ($this->data['attrs']['hide_when_no_results'] && !$this->data['results']->total) {
            $this->classList->add('rn_Hidden');
        }
    }

    /**
    * Renders current facet.
    * @param object $currentFacet Current facet
    * @param boolean $hasChildren True if current facet has children False if no children for current facet
    * @param boolean $closeList True if you want to close a list item created earlier False if creating a new list item
    */
    function processChildren($currentFacet, $hasChildren, $closeList) {
        echo $this->render('facetLink',
            array(
                'facetID' => $currentFacet->id,
                'description' => $currentFacet->desc,
                'facetClass' => $currentFacet->inEffect ? 'rn_FacetLink rn_ActiveFacet' : 'rn_FacetLink',
                'hasChildren' => $hasChildren,
                'closeList' => $closeList
            )
        );
    }

    /**
    * Checks children of the current facet recursively. Process current facet if no children found.
    * @param object $facet Current facet
    * @param object $parentLi Parent list node
    * @param int $maxSubFacetSize Number of facets to be displayed
    */
    function findChildren($facet, $parentLi, $maxSubFacetSize) {
        $length = count($facet->children);
        $displayFacetLength = $length;
        if ($maxSubFacetSize !== null && $maxSubFacetSize > 0 && $length > $maxSubFacetSize) {
            $displayFacetLength = $maxSubFacetSize;
        }
        for ($i = 0; $i < $displayFacetLength; ++$i) {
            $currentFacet = $facet->children[$i];
            if ($currentFacet !== null) {
                if (count($currentFacet->children) !== 0) {
                    $this->processChildren($currentFacet, true, false);
                    echo $this->render('facetIndent',
                        array(
                            'facetID' => $currentFacet->id,
                            'startListIndent' => true
                        )
                    );

                    $this->findChildren($currentFacet, $parentLi, $maxSubFacetSize);
                    echo $this->render('facetIndent',
                        array(
                            'startListIndent' => false
                        )
                    );
                    $this->processChildren($currentFacet, true, true);
                }
                else {
                    $this->processChildren($currentFacet, false, true);
                }
            }
        }
        if ($maxSubFacetSize != null && $maxSubFacetSize > 0 && $length > $maxSubFacetSize)
            echo $this->render('morelink', array('facetID' => $facet->id, 'description' => $currentFacet->desc));
    }

    /**
     * Checks for a source_id error. Emits an error message if a problem is found.
     * @return boolean True if an error was encountered False if all is good
    */
    private function sourceError () {
        if (\RightNow\Utils\Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }
        return false;
    }

    /**
    * Logs the error for each facet item.
    * @param string $facetItem Current facet property key
    * @param string $facetProperty Current facet property value
    * @return boolean False if an error was encountered True if all is good
    */
    private function getErrorDetails($facetItem, $facetProperty){
        if (is_null($facetProperty)){
            echo $this->reportError(sprintf(Config::getMessage(RESULT_OBJECT_PROPERTY_S_NOT_AVAILABLE_LBL), $facetItem));
            return false;
        }
        return true;
    }
    
    /**
    * Sorting the order of the list based on top_facet_list attribute.
    * @param array $facets Facets in default order
    * @return array after sorting the order of the list based on top_facet_list attribute
    */
    private function facetsOrderbyList($facets){
        $tempFacets = $facetOrder = $facetOrderDesc = array();
        $notFoundList = $facets;
        $descItemsCount = substr_count($this->data['attrs']['top_facet_list'], ',');
        if(($descItemsCount === 0) ){
            $facetOrder = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrder as $facetOrderItemKey) {
                foreach($facets as $facetItem){
                    if($facetItem->id === trim($facetOrderItemKey)){
                        $tempFacets[] = $facetItem;
                    }
                }
            }
        }
        if($descItemsCount > 0 && ($descItemsCount - substr_count($this->data['attrs']['top_facet_list'], '|') === 1)) {
            $facetOrderPairs = explode('|', $this->data['attrs']['top_facet_list']);
            foreach($facetOrderPairs as $facetItem)
            {
                $facetItemPair = explode(',', $facetItem);
                $facetOrder[$facetItemPair[0]] = $facetItemPair[1];
            }
            foreach($facetOrder as $facetOrderItemKey => $facetOrderItemValue) {
                foreach ($facets as $facetItem) {
                    if($facetItem->id === trim($facetOrderItemKey)) {
                        $facetItem->desc = trim($facetOrderItemValue);
                        $tempFacets[]  = $facetItem;
                    }
                }
            }
        }
        return $tempFacets;
    }
}
