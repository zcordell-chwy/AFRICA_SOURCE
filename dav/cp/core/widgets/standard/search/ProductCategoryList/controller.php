<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

class ProductCategoryList extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['attrs']['data_type'] = strtolower($this->data['attrs']['data_type']);
        $topLevelIDs = $this->data['attrs']['only_display'];
        $this->data['results'] = $this->CI->model('Prodcat')->getHierarchy(
            $this->data['attrs']['data_type'],
            $this->data['attrs']['levels'],
            $this->data['attrs']['maximum_top_levels'],
            $topLevelIDs ? explode(',', $topLevelIDs) : array(),
            $this->data['attrs']['maximum_descendants']
        )->result;

        if(!count($this->data['results'])) {
            return false;
        }

        $resultCount = count($this->data['results']);
        $this->data['results'] = array_chunk($this->data['results'], (int) ceil($resultCount / 3));

        if($this->data['attrs']['add_params_to_url']) {
            $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        }

        $this->data['type'] = ($this->data['attrs']['data_type'] === 'products') ? 'p' : 'c';
        $this->data['itemLink'] = $this->data['attrs']['report_page_url'] . $this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter() . '/' . $this->data['type'] . '/';
    }
}
