<rn:meta title="#rn:msg:SUPPORT_HISTORY_LBL#" template="standard.php" clickstream="incident_list" login_required="true" />
<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <rn:container report_id="196">
    <div id="rn_PageTitle" class="rn_QuestionList">
        <div id="rn_SearchControls">
                <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form onsubmit="return false;">
                    <div class="rn_SearchInput">
                        <rn:widget path="search/AdvancedSearchDialog" show_confirm_button_in_dialog="true"/>
                        <rn:widget path="search/KeywordText2" label_text="#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#" initial_focus="true"/>
                        
                    </div>
                    <rn:widget path="search/SearchButton2"/>
            </form>
            <rn:widget path="search/DisplaySearchFilters"/>
        </div>
    </div>
    <div id="rn_PageContent" class="rn_QuestionList">
        <div class="rn_Padding">
            <h2 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_RESULTS_CMD#</h2>
            <rn:widget path="reports/ResultInfo2" add_params_to_url="p,c"/>
            <rn:widget path="reports/Grid2" label_caption="#rn:msg:SUPPORT_HISTORY_LBL#"/>
            <rn:widget path="reports/Paginator"/>
        </div>
    </div>

    <div id="rn_PageContent" class="rn_QuestionList">
        <div class="rn_Padding">

                <div class="rn_Notifs">
                    <rn:widget path="reports/Grid2" report_id="200" per_page="4" label_caption="#rn:msg:YOUR_RECENT_ANSWER_NOTIFICATIONS_LBL#"/>
                    <a href="/app/account/notif/list#rn:session#">#rn:msg:PRODUCT_CATEGORY_ANS_NOTIFICATIONS_LBL#</a>
                </div>
        </div>
    </div>


    </rn:container>
</div>