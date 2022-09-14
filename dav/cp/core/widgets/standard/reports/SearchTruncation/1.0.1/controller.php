<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

class SearchTruncation extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $filters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, $format)->result;
        if (!is_array($results) || !$results['truncated']) {
            $this->classList->add('rn_Hidden');
        }
    }
}
