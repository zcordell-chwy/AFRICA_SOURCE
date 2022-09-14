<rn:meta title="Pledge Updated" template="standard.php" login_required="true"clickstream="pledge_confirm"/>

<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<div id="rn_PageTitle" class="rn_AskQuestion">
	    <h1>Pledge Update Confirmation</h1>
	</div>

	<div id="rn_PageContent" class="rn_AskQuestion">
	    <div class="rn_Padding">
	        <p>
	            Your pledge #rn:url_param_value:pledge_id# has been updated.  If you have any questions or concerns please call us at 503-906-1590.
	        </p>
	        <p>
	            <a href="/app/account/overview/c_id/<?=$profile->c_id->value?>"><< Return to account overview.</a>
	        </p>
	    </div>
	</div>
</div>