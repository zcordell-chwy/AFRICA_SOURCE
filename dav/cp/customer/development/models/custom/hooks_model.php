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
        //logMessage(__FUNCTION__);
        //logMessage($hookData);
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
                //logMessage($hookData['data']['filters']);
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

    function post_report_get_data(&$hookData)
    {
        //logMessage($hookData['data']['filters']);
        $report_id = $hookData['data']['reportId'];
        if ($report_id == 101901) {
            $url = \RightNow\Utils\Url::getOriginalUrl();
            if ($hookData['data']['filters']['event']) {
                \RightNow\Utils\Url::addParameter($url, 'event', 691);
            }
        }
    }

    function prePageRenderModel(&$hookData)
    {
        $url = Url::getOriginalUrl();
        $request_uri = $_SERVER['REQUEST_URI'];
        $event_link = "/app/home/event/";

        if (substr($request_uri, 0, strlen($event_link)) === $event_link) {
            $event_id = URL::getParameter('event');
            Framework::setLocationHeader('/app/event_home/st/8/kw/' . $event_id.'/event/'.$event_id);
        }
        logMessage($url);
        logMessage($hookData);
    }
}
