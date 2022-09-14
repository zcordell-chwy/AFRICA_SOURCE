<rn:meta title="#rn:msg:CHANGE_YOUR_PASSWORD_CMD#" template="standard.php" login_required="true"/>

<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <div id="rn_PageTitle" class="rn_Account">
        <h1>#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</h1>
    </div>
    <div id="rn_PageContent" class="rn_Account">
        <div class="rn_Padding">
            <div id="rn_ErrorLocation"></div>
            <form id="rn_ChangePassword" onsubmit="return false;">
<!--                 <rn:widget path="input/FormInput" name="contacts.password" required="false" label_input="#rn:msg:OLD_PASSWORD_LBL#" initial_focus="true" />
                <rn:widget path="input/FormInput" name="contacts.password_new" required="false" label_input="#rn:msg:ENTER_NEW_PASSWD_LBL#" />
                <rn:widget path="input/FormInput" name="contacts.password_verify" required="false" label_input="#rn:msg:CONFIRM_NEW_PASSWD_LBL#" />
                <rn:widget path="input/FormSubmit" on_success_url="/app/utils/submit/password_changed" error_location="rn_ErrorLocation"/> -->
                <rn:widget path="input/PasswordInput" name="Contact.NewPassword" require_validation="true" require_current_password="true" label_input="#rn:msg:ENTER_NEW_PASSWD_LBL#" label_validation="#rn:msg:CONFIRM_NEW_PASSWD_LBL#" initial_focus="true"/>
                <rn:widget path="input/FormSubmit" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(\RightNow\Utils\Config::getMessage(YOUR_PASSWORD_HAS_BEEN_CHANGED_MSG))#" error_location="rn_ErrorLocation" />
            </form>
        </div>
    </div>
</div>