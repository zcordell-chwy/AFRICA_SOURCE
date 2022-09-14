<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

use RightNow\Utils\Config;

class Paginator extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        if (!$this->helper('Social')->validateModerationMaxDateRangeInterval($this->data['attrs']['max_date_range_interval'])) {
            echo $this->reportError(Config::getMessage(MAX_FMT_YEAR_T_S_EX_90_S_5_YEAR_ETC_MSG));
            return false;
        }        
        $filters = $this->CI->model('Report')->cleanFilterValues($filters, $this->helper('Social')->getModerationDateRangeValidationFunctions($this->data['attrs']['max_date_range_interval']));
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, null)->result;
        
        $this->data['js']['startPage'] = 1;
        $this->data['js']['endPage'] = (int)$results['total_pages'];
        $this->data['js']['pageUrl'] = "/app/{$this->CI->page}/page/";
        $this->data['js']['currentPage'] = $results['page'];
    }
}
