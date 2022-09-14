<?php
namespace Custom\Widgets\eventus;

class AccountOverviewMultiline extends \RightNow\Widgets\Multiline {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {

        //logMessage("start get data");
        
        $format = array(
            'truncate_size' => $this->data['attrs']['truncate_size'],
            'max_wordbreak_trunc' => $this->data['attrs']['max_wordbreak_trunc'],
            'emphasisHighlight' => $this->data['attrs']['highlight'],
            'dateFormat' => $this->data['attrs']['date_format'],
            'urlParms' => \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']),
        );
        $filters = array('recordKeywordSearch' => true);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);

        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);

        $results = ($this->data['attrs']['alerts_report']) ? $this->aggregateAlerts($format) : $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, $format)->result;

        if ($results['error'] !== null) {
            echo $this->reportError($results['error']);
        }
        $this->data['reportData'] = $results;
        if($this->data['attrs']['hide_when_no_results'] && count($this->data['reportData']['data']) === 0) {
            $this->classList->add('rn_Hidden');
        }
        $this->data['js'] = array(
            'filters' => $filters,
            'format' => $format,
            'r_tok' => $reportToken,
            'error' => $results['error'],
            'reportData' => $results['data']
        );
        $this->data['js']['filters']['page'] = $results['page'];
        
        //logMessage($this->data['reportData']);
        
    }

    function aggregateAlerts($format){

        $aggregatedResults = array();
        
        logMessage(getConfig(CUSTOM_CFG_alert_report));
        $reportIdArray = explode(",",getConfig(CUSTOM_CFG_alert_report));
        
        logMessage($reportIdArray);
        
        foreach($reportIdArray as $reportId){
            logMessage($reportId);
            //reset the filters for each report
            $this->data['attrs']['report_id'] = trim($reportId);
            $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
            \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
            
            $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, $format)->result;
            
            $aggregatedResults = array_merge($aggregatedResults, $results['data']);

        }
        
        //logMessage($aggregatedResults);
        $results['data'] = $aggregatedResults;
        logMessage($results);
        return $results;
    }

    /**
     * Overridable methods from Multiline:
     */
    // function showColumn($value, array $header)
    // function getHeader(array $header)
}