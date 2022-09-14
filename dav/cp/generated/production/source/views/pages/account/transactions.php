<? $this->load->helper('field'); ?>
<rn:meta title="Payments" template="standard.php" login_required="true" />
<!-- <div class="rn_HeaderContainer">
    <rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
        <div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
    </rn:condition>
</div> -->
<div id="alertContainer" class="rn_Hidden"></div>
<!--Pivot team to remove inline style-->
<div id="rn_PageContent" class="rn_AccountOverviewPage" style="background-color:white;text-align:center;padding:1em;">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Payment Methods" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />
    <br />
    <div class=" rn_AfricaNewLifeLayoutSingleColumn">
        <?
        if (getUrlParm('p_id') > 0) {
            $infoMessage = "Please enter a new Payment Method for this pledge, <b>" . getPledgeDescr(getUrlParm('p_id')) . " </b>(" . getUrlParm('p_id') . ") or select from your existing options below. ";
        } elseif (getUrlParm('action') == "deleteConfirm") {
            $infoMessage = getMessage(CUSTOM_MSG_DELETE_PAYMETHOD);
        } elseif (getUrlParm('action') == "updateConfirm") {
            $infoMessage = getMessage(CUSTOM_MSG_UPDATE_PAYMETHOD);
        } ?>

        <? if ($infoMessage != "") { ?>
            <div class="pledgeInfo">
                <?= $infoMessage ?>
            </div>
        <? } ?>

        <?
        // if (getUrlParm('c_id') > 0  && getUrlParm('c_id') == $profile->c_id->value){
        ?>
        <div class="rn_PayMethodsList">
            <rn:widget path="custom/eventus/PayMethodsMultiline" report_id="100777" label_caption="" static_filter="c_id=#rn:profile:contactID#"/>
        </div>
        <?
        //}else{   
        // header('Location: /app/account/transactions/c_id/'.$profile->c_id->value);
        //}
        ?>

    </div>
</div>