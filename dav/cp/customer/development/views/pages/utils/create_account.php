<rn:meta title="#rn:msg:CREATE_NEW_ACCT_HDG#" template="responsive.php" login_required="false" redirect_if_logged_in="account/overview" />

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <div id="rn_PageTitle" class="rn_Account">
        <h1>#rn:msg:CREATE_AN_ACCOUNT_CMD#</h1>
    </div>
    <div id="rn_PageContent" class="rn_CreateAccount">
        <div class="rn_Padding">
            <form id="rn_CreateAccount" onsubmit="return false;">
                <div id="rn_ErrorLocation"></div>
                <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#"/>
                <rn:widget path="input/FormInput" name="Contact.Login" required="true" validate_on_blur="true" label_input="#rn:msg:USERNAME_LBL#"/>
                <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                    <rn:widget path="input/FormInput" name="Contact.NewPassword" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#"/>
                </rn:condition>
                <rn:widget path="input/ContactNameInput" required="false"/>
                <rn:widget path="input/FormInput" name="contacts.ph_home" label_input="Best Phone Number"/>
                <rn:widget path="input/FormInput" name="Contacts.CustomFields.CO.how_did_you_hear" />
                <rn:widget path="input/FormInput" name="contacts.c$contacttype" required="false"/>
                <rn:widget path="input/FormInput" name="contact.c$anonymous" display_as_checkbox="true" always_show_hint="true" hint="#rn:msg:CUSTOM_MSG_ANONYMOUS_LABEL#" label_input=" "/>
                
                <? if (getUrlParm('rdirect') == "payment"){ ?>
                    <!--in checkout logic-->
                    <rn:widget path="input/FormSubmit" label_button="#rn:msg:CREATE_ACCT_CMD#" on_success_url="/app/payment/checkout" error_location="rn_ErrorLocation"/>
                <?}else{?>
                    <!--not in checkout logic-->
                    <rn:widget path="input/FormSubmit" label_button="#rn:msg:CREATE_ACCT_CMD#" on_success_url="/app/account/overview" error_location="rn_ErrorLocation"/>
                <?}?>
            </form>
      </div>  
    </div>
</div>