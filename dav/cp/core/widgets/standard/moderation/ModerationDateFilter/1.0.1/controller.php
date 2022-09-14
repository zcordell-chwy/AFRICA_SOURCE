<?php /* Originating Release: February 2019 */


namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class ModerationDateFilter extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $filterMetaData = $this->CI->model("Report")->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['report_filter_name']);
        if ($filterMetaData->errors) {
            echo $this->reportError($filterMetaData->error);
            return false;
        }
        if ($this->data['attrs']['date_filter_options'] === array('none')) {
            return false;
        }

        $optionLabels = $this->helper('Social')->formatListAttribute($this->data['attrs']['date_filter_option_labels']);
        $enabledOptions = array();
        foreach ($this->data['attrs']['date_filter_options'] as $dateFilterOption) {
            if (!empty($dateFilterOption)) {
                $enabledOptions[$dateFilterOption] = $optionLabels[$dateFilterOption];
            }
        }

        $urlDate = trim(Url::getParameter($this->data['attrs']['report_filter_name']));
        if ($optionLabels[$urlDate]) {
            $enabledOptions[$urlDate] = $optionLabels[$urlDate];
            $dateValue = $urlDate;
        }
        else if (!empty($urlDate) && $dateRange = Text::validateDateRange($urlDate, $this->getDefaultDateFormat(), "|", false, $this->data['attrs']['max_date_range_interval'])) {
            $enabledOptions["custom"] = $optionLabels["custom"];
            $dateValue = $dateRange;
        }
        else {
            $defaultDateValue = $this->getDefaultFilterValue($filterMetaData, $optionLabels);
            if($optionLabels[$defaultDateValue]){
                $enabledOptions[$defaultDateValue] = $optionLabels[$defaultDateValue];
            } else {
                $enabledOptions["custom"] = $optionLabels["custom"];
            }
        }
        if (empty($enabledOptions)) {
            return false;
        }
        $this->data['js'] = array('options' => $enabledOptions,
            'filter_id' => $filterMetaData->result['fltr_id'],
            'oper_id' => $filterMetaData->result['oper_id'],
            'urlDateValue' => $dateValue,
            'default_value' => $defaultDateValue,
            'date_format' => $this->getDefaultDateFormat()
        );
    }

    /**
     * Gets the default date filter value from the report
     *
     * @param \RightNow\Libraries\ResponseObject $filterMetaData The report filter metadata
     * @param array $optionLabels Array of allowed date options
     * @return string Default date value
     */
    protected function getDefaultFilterValue(\RightNow\Libraries\ResponseObject $filterMetaData, array $optionLabels) {
        $parseOptions = array("allowedOptions" => $optionLabels, "dateFormat" => $this->getDefaultDateFormat());
        return $this->helper('Social')->parseReportDefaultFilterValue($filterMetaData->result["default_value"], $filterMetaData->result["data_type"], $parseOptions);
    }

    /**
     * Gets the default date format
     * @return string date format
     */
    protected function getDefaultDateFormat() {
        return 'm/d/Y';
    }

}
