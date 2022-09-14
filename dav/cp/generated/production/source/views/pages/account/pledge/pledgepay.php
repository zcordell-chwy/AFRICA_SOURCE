<rn:meta title="Pledge Detail" template="standard.php" login_required="true" clickstream="pledge_view" />
<div class="rn_HeaderContainer">
	<rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
		<div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
	</rn:condition>
</div>
<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<rn:widget path="custom/eventus/pledgepayment" pledge_id="#rn:php:getUrlParm('pledge_id')#" />
</div>