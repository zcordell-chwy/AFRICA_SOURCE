<rn:meta title="#rn:msg:ACCOUNT_ASSISTANCE_LBL#" template="responsive.php" login_required="false" />

<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<div id="rn_PageTitle" class="rn_Account">
	    <h1>#rn:msg:ACCOUNT_ASSISTANCE_LBL#</h1>
	</div>

	<div id="rn_PageContent" class="rn_Account">
	    <div class="rn_Padding" >
	        <rn:widget path="login/EmailCredentials2" credential_type="username" label_heading="#rn:msg:REQUEST_YOUR_USERNAME_LBL#" label_description="#rn:msg:EMAIL_ADDR_ENTER_SYS_SEND_USERNAME_MSG#" label_button="#rn:msg:EMAIL_MY_USERNAME_LBL#" label_input="#rn:msg:EMAIL_ADDR_LBL#" initial_focus="true"/>
	        <br/>
	        <br/>
	        <rn:widget path="login/EmailCredentials2" label_description="#rn:msg:CUSTOM_MSG_CASE_REMINDER#" />
	    </div>
	</div>
</div>