<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home" />
<div>
    <div class="homepage_left">
        <div class="panel1">
            LET EVERY CHILD DREAM
        </div>
        <div class="panel2">
            EVENT SPECIFIC MESSAGE WILL GO HERE
        </div>
        <div class="panel3">
            SPONSORSHIP THROUGH AFRICA NEW LIFE IS UNIQUE
        </div>
    </div>

    <div class="homepage_right">
        <rn:container report_id="101830">
            <div class="rn_Hero">
                <div class="rn_HeroInner">
                    <div class="rn_SearchControls">
                        <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                        <form onsubmit="return false;" class="translucent">
                            <!-- <div class="rn_SearchInput">
                        <rn:widget path="search/KeywordText" label_text="#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#" 
   label_placeholder="#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#" initial_focus="true" />
                    </div> -->

                         <!--    <div class="rn_SearchFilters">
                                <rn:widget path="search/FilterDropdown" filter_name="$test" />
                                <rn:widget path="search/FilterDropdown" filter_name="BirthMonth" />
                                <rn:widget path="search/FilterDropdown" filter_name="BirthYear" />
                                <rn:widget path="search/FilterDropdown" filter_name="Gender" /> --> 
                                <!-- <rn:widget path="search/FilterDropdown" filter_name="$agegroup" /> -->
                              <!--  <rn:widget path="search/FilterDropdown" filter_name="Community" options_to_ignore="193,194,195,196,197,198,199,200,205,206," />
                            </div> -->
                            <div class="rn_Hidden">
                                <rn:widget path="search/FilterDropdown" filter_name="event" />
                            </div>
                            <rn:widget path="search/SearchButton" />
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
            </div>
        </rn:container>
    </div>
</div>