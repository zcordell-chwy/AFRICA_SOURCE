<rn:meta title="#rn:msg:SUPPORT_LOGIN_HDG#" template="mobile.php" login_required="false" redirect_if_logged_in="account/questions/list" />

<section id="rn_PageTitle" class="rn_LoginForm">
    <h1>#rn:msg:LOG_IN_UC_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_LoginForm">
    <div id="rn_ThirdPartyLogin" class="rn_Padding">
        <h1>#rn:msg:LOG_REGISTER_SERVICES_CONTINUE_MSG#</h1>
        <rn:widget path="login/OpenLogin" display_in_dialog="true"/> <? /* Attributes Default to Facebook */ ?>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/oauth/authorize/twitter" label_service_button="Twitter" label_process_explanation="#rn:msg:CLICK_BTN_TWITTER_LOG_TWITTER_MSG#" label_login_button="#rn:msg:LOG_IN_USING_TWITTER_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/google" label_service_button="Google" label_process_explanation="#rn:msg:CLICK_BTN_GOOGLE_LOG_GOOGLE_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_GOOGLE_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize/yahoo" label_service_button="Yahoo" label_process_explanation="#rn:msg:CLICK_BTN_YAHOO_LOG_YAHOO_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_YAHOO_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize" label_service_button="AOL" openid="true" preset_openid_url="http://openid.aol.com/[username]" openid_placeholder="[#rn:msg:YOUR_AOL_USERNAME_LBL#]" label_process_explanation="#rn:msg:YOULL_AOL_LOG_AOL_VERIFY_SEND_YOULL_MSG#" label_login_button="#rn:msg:LOG_IN_USING_AOL_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize" label_service_button="MyOpenID" openid="true" preset_openid_url="http://[username].myopenid.com" openid_placeholder="[#rn:msg:YOUR_MYOPENID_USERNAME_LBL#]" label_process_explanation="#rn:msg:YOULL_MYOPENID_LOG_MYOPENID_VERIFY_MSG#" label_login_button="#rn:msg:LOG_IN_USING_MYOPENID_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize" label_service_button="Wordpress" openid="true" preset_openid_url="http://[username].wordpress.com" openid_placeholder="[#rn:msg:YOUR_WORDPRESS_USERNAME_LBL#]" label_process_explanation="#rn:msg:YOULL_LOG_ACCT_WORDPRESS_WINDOW_MSG#" label_login_button="#rn:msg:LOG_IN_USING_WORDPRESS_LBL#" display_in_dialog="true"/>
        <rn:widget path="login/OpenLogin" controller_endpoint="/ci/openlogin/openid/authorize" label_service_button="OpenID" openid="true" openid_placeholder="http://[provider]" label_process_explanation="#rn:msg:YOULL_OPENID_PROVIDER_LOG_PROVIDER_MSG#" label_login_button="#rn:msg:LOG_IN_USING_OPENID_LBL#" display_in_dialog="true"/>
    </div>
    <div id="rn_Login" class="rn_Module">
        <h2>#rn:msg:OR_ELLIPSIS_MSG#</h2>
        <div class="rn_Padding">
            <div>
                <h3>#rn:msg:ACCOUNT_ENTER_USERNAME_PASSWORD_MSG#</h3>
                <rn:widget path="login/LoginForm2" redirect_url="/app/account/questions/list"/>
                <a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:FORGOT_YOUR_USERNAME_OR_PASSWORD_MSG#</a>
            </div>
            <br/><br/>
            <div>
                <h3>#rn:msg:NOT_REGISTERED_YET_MSG#</h3>
                <br/><a href='/app/utils/create_account/redirect/<?=urlencode(getUrlParm('redirect'));?>#rn:session#'>#rn:msg:CREATE_NEW_ACCT_CMD#</a><br/><br/>
            </div>
        </div>
    </div>
</section>
