<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <?php if (!$this->data['errorCondition']) : ?>
        <div>
            <form id='storedMethodForm' onsubmit="return false;" autocomplete="off">
                <rn:condition logged_in="true">
                    <div class="rn_BillingDonorInfoContainer">
                        <!-- <form id="rn_CreateAccount1001" onsubmit="return false;"> -->
                        <div id="rn_ErrorLocation" class="rn_Hidden"></div>
                        <div>
                            <rn:widget path="custom/payment/PaymentProcess" on_success_url="/app/payment/checkout" error_location="rn_ErrorLocation" name="Incident.Subject" />
                        </div>
                        <div class="rn_Hidden">
                            <fieldset>
                                <legend>
                                    #rn:msg:CONTACT_INFO_LBL#
                                </legend>
                                <rn:widget path="input/ContactNameInput" required="true" />
                                <rn:widget path="input/FormInput" name="Contact.Address.Street" required="false" label_input="#rn:msg:CUSTOM_MSG_STREET_LBL#" />
                                <rn:widget path="input/FormInput" name="Contact.Address.City" required="false" />
                                <rn:widget path="input/FormInput" name="Contact.Address.Country" />
                                <rn:widget path="input/FormInput" name="Contact.Address.PostalCode" required="true" label_input="Postal Code" />
                                <rn:widget path="input/FormInput" name="Contact.Phones.HOME.Number" label_input="Phone Number" />
                                <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#" />
                            </fieldset>
                        </div>
                        <div>
                            <div id="rn_Challenge"></div>
                        </div>
                        <div>
                            <!-- <rn:widget path="custom/eventus/ajaxFormSubmit" confirm_message="You are about to submit a payment for #rn:php:'$'.$this->data['amount'].'.00'#.  By selecting OK you agree to pay this amount." label_button="Start My Sponsorship" on_success_url="/app/payment/success" error_location="rn_ErrorLocation" /> -->
                            <rn:widget path="input/SponsorshipSubmit" confirm_message="You are about to submit a payment for #rn:php:'$'.$this->data['amount'].'.00'#.  By selecting OK you agree to pay this amount." label_button="Start My Sponsorship" on_success_url="/app/payment/success" error_location="rn_ErrorLocation" challenge_location="rn_Challenge" challenge_required="true" />
                        </div>
                        <!-- </form> -->
                    </div>
                </rn:condition>
                <rn:condition logged_in="false">
                    <div id="rn_PageContent" class="rn_Account rn_LoginForm">
                        <div class="rn_Padding">
                            <div>
                                <rn:widget path="custom/payment/PaymentProcess" on_success_url="/app/payment/checkout" error_location="rn_ErrorLocation" name="Incident.Subject" />
                            </div>
                            <div class="rn_Column rn_CreateContactFormOnCheckout">
                                <h1>Checkout as Guest</h1>
                                <!-- <form id="rn_CreateAccount" onsubmit="return false;"> -->
                                <div id="rn_ErrorLocation"></div>
                                <rn:condition config_check="intl_nameorder == 1">
                                    <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true" />
                                    <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true" />
                                    <rn:condition_else />
                                    <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true" />
                                    <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true" />
                                </rn:condition>

                                <rn:widget path="input/FormInput" name="Contact.Address.Street" label_input="#rn:msg:CUSTOM_MSG_STREET_LBL#" />
                                <rn:widget path="input/FormInput" name="Contact.Address.City" label_input="#rn:msg:CITY_LBL#" />
                                <rn:widget path="input/FormInput" name="Contact.Address.Country" label_input="#rn:msg:COUNTRY_LBL#" default_value="1" required="true" />
                                <rn:widget path="input/FormInput" name="Contact.Address.StateOrProvince" label_input="#rn:msg:STATE_PROV_LBL#" />
                                <rn:widget path="input/FormInput" name="Contact.Address.PostalCode" required="true" label_input="#rn:msg:POSTAL_CODE_LBL#" />
                                <rn:widget path="input/FormInput" name="Contact.Phones.HOME.Number" label_input="Phone Number" />
                                <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="true" label_input="#rn:msg:EMAIL_ADDR_LBL#" />
                                <rn:widget path="input/FormInput" required="true" name="Contact.NewPassword" require_validation="false" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" />
                                <rn:widget path="input/FormInput" required="false" name="Contact.CustomFields.CO.how_did_you_hear" label_input="How did you hear about us?" />
                                <input type="checkbox" id="subscribeToEmailCheckbox" name="subscribeToEmailCheckbox" value="" checked>
                                <label id="subscribeToEmailCheckboxLabel" for="subscribeToEmailCheckbox">#rn:msg:CUSTOM_MSG_cp_CheckoutAssistant_email_preferences_checkbox_label#</label>
                                <div style="display:none;">
                                    <rn:widget path="custom/input/AutoDefaultingLoginInput" name="Contact.Login" required="false" validate_on_blur="true" label_input="#rn:msg:USERNAME_LBL#" />
                                    <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                                        <!-- <rn:widget path="custom/input/AutoDefaultingPasswordInput" name="Contact.NewPassword" required="false" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" /> -->
                                    </rn:condition>
                                    <rn:widget path="input/EmailPrefSelectionInput" name="Contact.CustomFields.c.preferences" default_value="14" label_input="Email Prefs" />
                                </div>
                                <div>
                                    <div id="rn_Challenge"></div>
                                </div>
                                <div>
                                    <rn:widget path="input/SponsorshipSubmit" confirm_message="You are about to submit a payment for #rn:php:'$'.$this->data['amount'].'.00'#.  By selecting OK you agree to pay this amount." label_button="Start My Sponsorship" on_success_url="/app/payment/success" error_location="rn_ErrorLocation" challenge_location="rn_Challenge" challenge_required="true" />
                                </div>
                                <!-- </form> -->
                            </div>
                        </div>
                    </div>
                </rn:condition>
                <!-- <div class="t_n_c">
        <p><input type="checkbox" id="terms" style="display:inline-block; margin-top:15px;"> #rn:msg:CUSTOM_MSG_TERMS_AND_CONDITION#</br>
        </div> -->
            </form>
        </div>

        <!-- Payment / Transaction Error Condition.. -->
    <?php else : ?>
        <div class="CheckoutAssistantErrorMessage">
            There was an error with your payment, please <a href="/app/home">start again</a>.
        </div>
    <?php endif; ?>
    <div>
        <span><b>Need Help or Prefer to Give by Phone?</b></span>
        <p>Call <a>866-979-0393</a></p>
        <p>Email <a>info@africanewlife.org</a></p>
    </div>
    <div>
        <span><b>Our Information</b></span>
        <p>#rn:msg:CUSTOM_MSG_CP_SPONSOR_OUR_INFO#</p>
    </div>
</div>