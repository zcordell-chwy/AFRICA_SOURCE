<rn:meta title="#rn:msg:ACCOUNT_ASSISTANCE_LBL#" template="mobile.php" login_required="false" />

<section id="rn_PageTitle" class="rn_AccountAssistance">
    <h1>#rn:msg:ACCOUNT_ASSISTANCE_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_AccountAssistance">
    <div class="rn_Padding" >
        <rn:widget path="login/EmailCredentials2" credential_type="username" label_heading="#rn:msg:REQUEST_YOUR_USERNAME_LBL#" label_description="#rn:msg:EMAIL_ADDR_ENTER_SYS_WE_LL_SEND_MSG#" label_button="#rn:msg:EMAIL_MY_USERNAME_LBL#" label_input="#rn:msg:EMAIL_ADDR_LBL#" initial_focus="true"/>
        <br/><br/>
        <rn:widget path="login/EmailCredentials2"/>
    </div>
</section>
