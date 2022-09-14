<rn:meta title="#rn:msg:CHANGE_YOUR_PASSWORD_CMD#" template="mobile.php" login_required="true"/>

<section id="rn_PageTitle" class="rn_Account">
    <h1>#rn:msg:CHANGE_YOUR_PASSWORD_CMD#</h1>
</section>
<section id="rn_PageContent" class="rn_Account">
    <div class="rn_Padding">
        <div id="rn_ErrorLocation"></div>
        <form id="rn_ChangePassword" onsubmit="return false;">
            <rn:widget path="input/FormInput" name="contacts.password" required="false" label_input="#rn:msg:OLD_PASSWORD_LBL#" initial_focus="true" />
            <rn:widget path="input/FormInput" name="contacts.password_new" required="false" label_input="#rn:msg:ENTER_NEW_PASSWD_LBL#" />
            <rn:widget path="input/FormInput" name="contacts.password_verify" required="false" label_input="#rn:msg:CONFIRM_NEW_PASSWD_LBL#" />
            <rn:widget path="input/FormSubmit" on_success_url="/app/utils/submit/password_changed" error_location="rn_ErrorLocation"/>
        </form>
    </div>
</section>
