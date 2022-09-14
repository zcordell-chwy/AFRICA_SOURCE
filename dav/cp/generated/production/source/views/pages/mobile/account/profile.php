<rn:meta title="#rn:msg:ACCOUNT_SETTINGS_LBL#" template="mobile.php" login_required="true" />

<section id="rn_PageTitle" class="rn_Account">
    <h1>#rn:msg:ACCOUNT_SETTINGS_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_Profile">
    <div class="rn_Padding">
        <form id="rn_CreateAccount" onsubmit="return false;">
            <div class="rn_MessageBox rn_InfoMessage rn_Required rn_LargeText">#rn:url_param_value:msg#</div>
            <div id="rn_ErrorLocation"></div>
            <h2>#rn:msg:ACCT_HDG#</h2>
            <rn:widget path="input/FormInput" name="contacts.login" required="true" validate_on_blur="true" initial_focus="true"/>
            <rn:condition external_login_used="false">
                <rn:condition config_check="RNW_UI:EU_CUST_PASSWD_ENABLED == true">
                    <a href="/app/account/change_password#rn:session#">#rn:msg:CHG_YOUR_PASSWORD_CMD#</a><br/><br/>
                </rn:condition>
            </rn:condition>
            <h2>#rn:msg:CONTACT_INFO_LBL#</h2>
            <rn:widget path="input/ContactNameInput" required="true"/>
            <rn:widget path="input/FormInput" name="contacts.email" required="true" validate_on_blur="true"/>
            <rn:widget path="input/FormInput" name="contacts.street" />
            <rn:widget path="input/FormInput" name="contacts.city" />
            <rn:widget path="input/FormInput" name="contacts.country_id" />
            <rn:widget path="input/FormInput" name="contacts.prov_id" />
            <rn:widget path="input/FormInput" name="contacts.postal_code" />
            <rn:widget path="input/FormInput" name="contacts.ph_home" />
            <rn:widget path="input/FormInput" name="contacts.ph_office" />
            <rn:widget path="input/FormInput" name="contacts.ph_mobile" />
            <rn:widget path="input/CustomAllInput" table="contacts" always_show_mask="true" />
            <br/><br/><br/>
            <rn:widget path="input/FormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="/app/utils/submit/profile_updated" error_location="rn_ErrorLocation"/>
        </form>
    </div>
</section>
