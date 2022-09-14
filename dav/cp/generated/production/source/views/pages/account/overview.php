<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" />
<!--Pivot team to remove inline style-->
<div id="rn_PageContent" class="rn_AccountOverviewPage" style="background-color:white;text-align:center;padding:1em;">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />

    <div class="rn_Overview rn_AfricaNewLifeLayoutSingleColumn ">
        <?
        // if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){
        ?>
        <!-- <div class="announcementSection">
                <div class="topContent">Announcements</div>
                <div class="bottomContent">
                    <rn:container report_id="100776">
                            <rn:widget path="custom/eventus/AccountOverviewMultiline"/>
                            <rn:widget path="reports/Paginator"/>
                    </rn:container>
                </div>
            </div> -->

        <!-- <div class="alertsSection">
                <div class="topContent">Alerts</div>
                <div class="bottomContent">
                    <rn:container report_id="100776">
                            <rn:widget path="custom/eventus/AccountOverviewMultiline" alerts_report='true'/>
                    </rn:container>
                </div>
            </div> -->

        <div class="transactionsSection">
            <div class="topContent"></div>
            <div class="bottomContent">
                <!-- Report: 101903-->
                <rn:container report_id="101903">
                    <rn:widget path="reports/Grid" static_filter="Contact=#rn:profile:contactID#"/>
                    <rn:widget path="reports/Paginator" />
                </rn:container>
            </div>
        </div>
 
        <?
        // }else{   
        //     //header('Location: /app/account/overview/c_id/'.$profile->c_id->value);
        // }
        ?>

    </div>
</div>