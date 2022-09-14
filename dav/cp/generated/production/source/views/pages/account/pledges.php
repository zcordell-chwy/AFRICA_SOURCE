<? $this->load->helper('field'); ?>
<rn:meta title="Pledges" template="standard.php" login_required="true" />
<!-- <div class="rn_HeaderContainer">
    <rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
        <div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
    </rn:condition>
</div> -->
<!--Pivot team to remove inline style-->
<div id="rn_PageContent" class="rn_AccountOverviewPage" style="background-color:white;text-align:center;padding:1em;">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Pledges" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />
    <div class=" rn_AfricaNewLifeLayoutSingleColumn">
        <? if (getUrlParm('p_id') > 0 && getUrlParm('action') == "updateConfirm") { ?>
            <div class="pledgeInfo">
                You have successfully upated the Payment Method for pledge <b><?= getPledgeDescr(getUrlParm('p_id')) . " </b>(" . getUrlParm('p_id') . ")" ?>. Please note, if you would like this new payment method <br /> added to any addtional pledges, click the "Change" link for each of those pledges below, and select your new payment method.
            </div>
        <? } ?>
        <?
        // if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){
        ?>
        <div class="rn_PledgeList">
            <rn:widget path="custom/eventus/AccountMultiline" report_id="100778" label_caption="" static_filter="c_id=#rn:profile:contactID#"/>
        </div>
        <?
        // }else{   
        //     header('Location: /app/account/pledges/c_id/'.$profile->c_id->value);
        // }
        ?>

    </div>
</div>