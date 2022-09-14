


<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home" />
    <? 
    $url = \RightNow\Utils\Url::getOriginalUrl();
    $CI    		= & get_instance();
    
    $event_id = getUrlParm('event');
    // print('<pre>');
    // print_r($event_id);die;
    if($event_id){
        $event = $CI->model('custom/event_model')->getEvent($event_id);
        // print_r($event);die;
        if($event->showDisclaimer == 1)
            $showDisclaimer = $event->showDisclaimer;

    }

    ?>
<div>
    <div class="homepage_left">
        <div class="panel1">
            LET EVERY CHILD DREAM
        </div>
        <? if(strpos($url,"/app/home/event") !== FALSE) : ?>
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
        <? else : ?>
        <div class="panel3">        
            #rn:msg:CUSTOM_MSG_CP_SPONSOR_LIFE_HOME#	
        </div>
        <? endif; ?>        
    </div>

    <div class="homepage_right">
        <? if(strpos($url,"/app/home/event") !== FALSE) : ?>            
            <rn:container report_id="101901">
        <? else : ?>                
            <rn:container report_id="101900">
        <? endif; ?>          
            <div class="rn_Hero">
                <div class="rn_HeroInner">
                    <div class="rn_SearchControls">
                        <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                        <form onsubmit="return false;" class="translucent">

                            <div class="rn_SearchFilters">
                                <rn:widget path="search/FilterDropdown" filter_name="PriorityM" label_any="All" />
                                <rn:widget path="search/FilterDropdown" filter_name="Birth Month" label_any="All"/>
                                <rn:widget path="search/FilterDropdown" filter_name="Birth Year" label_any="All"/>
                                <rn:widget path="search/FilterDropdown" filter_name="Gender" label_any="All"/>
                                <rn:widget path="search/FilterDropdown" filter_name="Community" options_to_ignore="8,9,10,11,12,14,15,20,21" label_any="All"/>
                            </div>
                            <div class="rn_Hidden">
                                <rn:widget path="search/KeywordText" filter_name="event" />
                            </div>
                            <? if(strpos($url,"/app/home/event") !== FALSE) : ?>
                                <rn:widget path="search/SearchButton" report_page_url="/app/home/#rn:url_param:event#/" force_page_flip="true"/>
                            <? else :?>
                                <rn:widget path="search/SearchButton" />
                            <? endif;?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="rn_PageContent rn_Container">
                <h2 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_RESULTS_CMD#</h2>
                <!-- <rn:widget path="reports/ResultInfo" /> -->
                <rn:widget path="custom/sponsorship/UnsponsoredChildMultiLine" />
                <!-- <rn:widget path="reports/MultiLine" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#</span>" /> -->
                <!-- <rn:widget path="reports/Paginator" /> -->
                <rn:widget path="custom/reports/CustomPaginator" /> 
            </div>
        </rn:container>
        <? if(strpos($url,"/app/home/event") !== FALSE) : ?> 
            <? if($showDisclaimer == 1) : ?> 
                <div class="panel4" style="background-color: #fff;max-width: auto;padding: 09px;font-size: 14px;font-style: italic;margin-bottom: 40px;text-align:center;">                    
                    #rn:msg:CUSTOM_MSG_CP_EVENT_DISCLAIMER#	
                </div>
            <? endif; ?>              
        <? endif; ?>              
    </div>
</div>