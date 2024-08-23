<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home" />
<?
$url = \RightNow\Utils\Url::getOriginalUrl();
$CI = &get_instance();

$event_id = getUrlParm('event');


if (!$event_id)
    $event_id = getUrlParm('kw');

if ($event_id) {
    $event = $CI->model('custom/event_model')->getEvent($event_id);
    if ($event->showDisclaimer == 1)
        $showDisclaimer = $event->showDisclaimer;
}

?>
<div>
    <div class="homepage_left">
        <div class="panel1">
            LET EVERY CHILD DREAM
        </div>
        <div class="panel2">
            <div class="row" style="text-align:center;">
                <span id="event_name">
                    <? echo $event->DisplayName . '<br>'; ?>
                </span>
            </div>
            <div class="row" style="text-align:center;padding-top:30px;font-weight:normal;">
                <span id="event_desc">
                    <? echo $event->Description; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="homepage_right">
        <rn:container report_id="102117">
            <div class="rn_Hero">
                <div class="rn_HeroInner">
                    <div class="rn_SearchControls">
                        <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                        <form onsubmit="return false;" class="translucent">

                            <div class="rn_SearchFilters">
                                <rn:widget path="search/FilterDropdown" filter_name="PriorityM" label_any="All" />
                                <rn:widget path="search/FilterDropdown" filter_name="Birth Month" label_any="All" />
                                <rn:widget path="search/FilterDropdown" filter_name="Birth Year"  options_to_ignore="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,
                                43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102" label_any="All" />
                                <rn:widget path="search/FilterDropdown" filter_name="Gender" label_any="All" />
                                <rn:widget path="search/FilterDropdown" filter_name="Community" options_to_ignore="8,9,10,11,12,14,15,20,21" label_any="All" />
                        
                            </div>
                            <div class="rn_Hidden">
                                <!-- <rn:widget path="search/KeywordText" filter_name="event" /> -->
                                <!-- <rn:widget path="search/SearchTypeList" filter_list="event" /> -->
                            </div>
                            <? if (strpos($url, "/app/home/event") !== FALSE) : ?>
                                <rn:widget path="search/SearchButton" report_page_url="/app/home/#rn:url_param:event#/" force_page_flip="true" />
                            <? else : ?>
                                <rn:widget path="search/SearchButton" />
                            <? endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="rn_PageContent rn_Container">
                <h2 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_RESULTS_CMD#</h2>
                <!-- <rn:widget path="reports/ResultInfo" /> -->
                <rn:widget path="custom/sponsorship/UnsponsoredChildMultiLine" />
                <!-- <rn:widget path="reports/MultiLine" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#</span>" /> -->
                <rn:widget path="reports/Paginator" />

                <!-- <rn:widget path="custom/reports/CustomPaginator" report_page_url="/app/home/#rn:url_param:event#/" force_page_flip="true" static_filter="/st/8/kw/#rn:url_param_value:event#/" /> -->
            </div>
        </rn:container>
        <? if (strpos($url, "/app/home/event") !== FALSE) : ?>
            <? if ($showDisclaimer == 1) : ?>
                <div class="panel4" style="background-color: #fff;max-width: auto;padding: 09px;font-size: 14px;font-style: italic;margin-bottom: 40px;text-align:center;">
                    #rn:msg:CUSTOM_MSG_CP_EVENT_DISCLAIMER#
                </div>
            <? endif; ?>
        <? endif; ?>
    </div>
</div>