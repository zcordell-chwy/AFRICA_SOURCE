<?php

namespace Custom\Models;

use stdClass;
use RightNow\Utils\Framework,
    RightNow\Utils\Url,
    RightNow\Libraries\AbuseDetection,
    RightNow\Connect\v1_4 as RNCPHP;

class hooks_model extends  \RightNow\Models\Base
{
    const ACCOUNT_OVERVIEW_PAGE = "app/account/overview";

    function __construct()
    {
        parent::__construct();
        //This model would be loaded by using $this->load->model('custom/Sample_model');
    }

    function pre_report_get(&$hookData)
    {
        logMessage(__FUNCTION__);
        logMessage($hookData);
        $report_id = $hookData['data']['reportId'];
        //101825 - Advocates (Profile)
        if ($report_id == 101825)
            if (Framework::isLoggedIn()) {
                $contactID = $this->CI->session->getProfileData('contactID');

                $contact_filter = new stdClass();
                $contact_filter->filters = new stdClass();
                $contact_filter->filters->fltr_id = 1;
                $contact_filter->filters->oper_id = 1;
                $contact_filter->filters->report_id = $report_id;
                $contact_filter->filters->rnSearchType = 'filter';
                $contact_filter->filters->data[0] = $contactID;
                $contact_filter->type = 'ContactID';

                $hookData['data']['filters']['ContactID'] = $contact_filter;
                logMessage($hookData['data']['filters']);
            }
        // [ContactID] => stdClass Object
        // (
        //     [filters] => stdClass Object
        //         (
        //             [fltr_id] => 1
        //             [oper_id] => 1
        //             [report_id] => xxxxxx
        //             [rnSearchType] => filter
        //             [data] => Array
        //                 (
        //                     [0] => xxxxx
        //                 )

        //         )

        //     [type] => ContactID
        // )
    }

    function prePageRenderModel(&$hookData){
        $url = Url::getOriginalUrl();

        $CI = & get_instance();
        
        // Check if the page contains only attachments or url.
        // if(strpos($url,self::ACCOUNT_OVERVIEW_PAGE) !== FALSE && strpos($url,'c_id') === FALSE){
        //     $url = $url . '/c_id/'. $CI->session->getProfileData('contactID');
        //     header("Location: ".$url);
        // }
    }
}
