<rn:meta title="#rn:msg:FINISH_ACCOUNT_CREATION_CMD#" template="standard.php" login_required="false" />
<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<!-- This page is navigated to by following an email link when:
	(A) An account is automatically created and an email is sent [techmail/chat]
	(B) An existing user doesn't have a login and attempts to recover it-->
	<div id="rn_PageTitle" class="rn_Account">
	    <h1>#rn:msg:FINISH_ACCOUNT_CREATION_CMD#</h1>
	</div>
	<div id="rn_PageContent">
	    <div class="rn_Padding">
	        <rn:widget path="login/ResetPassword2" label_heading="#rn:msg:CREATE_A_USERNAME_AND_PASSWORD_CMD#" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(getMessage(SUCC_ACTIVATED_ACCT_PLS_COMP_MSG))#" />
	    </div>
	</div>
</div>