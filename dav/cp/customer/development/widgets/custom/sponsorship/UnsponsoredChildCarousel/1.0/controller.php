<?php

namespace Custom\Widgets\sponsorship;

use RightNow\Connect\v1_4 as RNCPHP;

class UnsponsoredChildCarousel extends \RightNow\Libraries\Widget\Base
{
    public $reportID;

    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'refresh_data' => array(
                'method'      => 'handle_refresh_data',
                'clickstream' => 'custom_action',
            ),
        ));

        $this->CI->load->helper('constants');
        $this->reportID = 101808;
    }
    function getData()
    {
        $data = parent::getData();
        
        return $data;
    }
    /**
     * Handles the refresh_data AJAX request
     * @param array $params Get / Post parameters
     */
    function handle_refresh_data($params)
    {
        // Perform AJAX-handling here...
        // echo response
        $data = [];

        $filters = new RNCPHP\AnalyticsReportSearchFilterArray;
        $ar = RNCPHP\AnalyticsReport::fetch($this->reportID);
        $arr = $ar->run(intval($params['start']), $filters, intval($params['limit']));

        for ($ii = $arr->count(); $ii--;) {
            $row = $arr->next();
            $imageLocation = $hasImage = null;
            if ($imageLocation = $this->CI->model('custom/sponsorship_model')->getChildImg($row['Child Ref'])) {
                $hasImage = true;
            } 
            else {
                $imageLocation = CHILD_IMAGE_URL_DIR . "/" . CHILD_NO_IMAGE_FILENAME;
                $hasImage = false;
            }
            $row['image'] = $imageLocation;
            $data[] = $row;
        }
        $this->echoJSON(json_encode($data));
    }
}
