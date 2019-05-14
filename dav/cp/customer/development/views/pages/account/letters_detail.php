<rn:meta title="#rn:php:SEO::getDynamicTitle('incident', getUrlParm('i_id'))#" template="responsive.php" login_required="true" clickstream="incident_view"/>

<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<rn:widget path="custom/aesthetic/AccountSubNav" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
    
    <div id="rn_PageContent" class="rn_QuestionDetail">
        <div class="rn_Padding">
            

            <h2 class="rn_HeadingBar">Letter Contents</h2>
            
                <rn:widget path="output/DataDisplay" name="incidents.thread" label=""/>


            <h2 class="rn_HeadingBar">Attachments and Response</h2>
            
                <rn:widget path="output/DataDisplay" name="incidents.fattach" label="#rn:msg:FILE_ATTACHMENTS_LBL#"/>

  
            <div id="rn_DetailTools">
                <rn:widget path="utils/PrintPageLink" />
            </div>
        </div>
    </div>
</div>