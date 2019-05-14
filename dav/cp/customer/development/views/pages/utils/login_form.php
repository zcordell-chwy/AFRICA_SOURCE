<rn:meta title="#rn:msg:SUPPORT_LOGIN_HDG#" template="responsive.php" login_required="false" />

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <div id="rn_PageTitle" class="rn_Account">
        <h1>#rn:msg:LOG_IN_UC_LBL#</h1>
    </div>

    <div id="rn_PageContent" class="rn_Account rn_LoginForm">
        <div class="rn_Padding">
            <div class="rn_Column rn_LeftColumn rn_ThirdPartyLogin">
                #rn:msg:NOT_REGISTERED_YET_MSG#
                <a href="/app/utils/create_account/redirect/<?=urlencode(getUrlParm('redirect'));?>#rn:session#">#rn:msg:SIGN_UP_LBL#</a>
                <br/><br/>
                <rn:widget path="custom/eventus/LoginFormCustom" redirect_url="/app/home"/">
                <br/><br/>
                <a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:FORGOT_YOUR_USERNAME_OR_PASSWORD_MSG#</a>
            </div>
        </div>
    </div>
</div>