<?

$CI = get_instance();
$existingPaymentMethods = $this->CI->model('custom/paymentMethod_model')->getCurrentPaymentMethodsObjs();

logMessage('Began logging of payment/checkout');
$errorCondition = false;
$this->CI->load->helper('constants');
$c_id = $this->CI->session->getProfileData('contactID');
logMessage('contact id = ' . var_export($c_id, true));
$amt = $this->CI->model('custom/items')->getTotalDueNow($this->CI->session->getSessionData('sessionID'));
logMessage('total = ' . var_export($amt, true));

//$items = $this -> CI -> session -> getSessionData('items');
$items = $this->CI->model('custom/items')->getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');
// logMessage("VIEW");
// logMessage($items);
// logMessage($dbitems);

$transId = $this->CI->session->getSessionData('transId');

$this->CI->session->setSessionData(array('theRealContactID' => $c_id));
$therealcontactID = $this->CI->session->getSessionData('theRealContactID');
//print("contactID: $contactId, the real c_id: ".$therealcontactID);

$itemDescs = array();
if ($items === false) {
    $errorCondition = true;
    logMessage('ERROR on payment page: no items data exists in session');
} elseif (count($items) < 1) {
    //print(getConfig(CUSTOM_CFG_general_cc_error_id));
    //echo "items error";
    $errorCondition = true;
    logMessage('ERROR on payment page: Items < 1');
} else {
    foreach ($items as $item) {
        $itemDescs[] = $item['itemName'];
    }

    if ($c_id !== false) {
        $createTrans = true;
        $transId = $this->CI->session->getSessionData('transId');
        logMessage('transId = ' . var_export($transId, true));
        if (!is_null($transId) && is_numeric($transId) && $transId > 0) {
            if ($this->CI->model('custom/transaction_model')->update_transaction($transId, $c_id, $amt, implode(',', $itemDescs)) === false) {
                $createTrans = true;
            } else {
                $createTrans = false;
                logMessage(__LINE__ . ":update on trans:" . $this->CI->session->getSessionData('sessionID') . " transid:" . $transId);
                $this->CI->model('custom/items')->updateTransOnItems($this->CI->session->getSessionData('sessionID'), $transId);
            }
        } else {
            $createTrans = true;
        }

        if ($createTrans) {
            $t_id = $this->CI->model('custom/transaction_model')->create_transaction($c_id, $amt, implode(',', $itemDescs));
            if ($t_id === false) {
                //echo "transaction error";
                $errorCondition = true;
                logMessage('ERROR creating transaction: transaction error');
            } else {
                $this->CI->session->setSessionData(array('transId' => $t_id));
                //add the transaction id to the cart
                logMessage(__LINE__ . ":update on trans:" . $this->CI->session->getSessionData('sessionID') . " transid:" . $t_id);
                $this->CI->model('custom/items')->updateTransOnItems($this->CI->session->getSessionData('sessionID'), $t_id);
            }
        }
    }
}

?>

<div id="rn_<?= $this->instanceID; ?>" class="<?= $this->classList ?>">
    <?php if (!$errorCondition) : ?>
        <h3 class="rn_CheckoutPageHeader">#rn:msg:CUSTOM_MSG_cp_checkout_page_header#</h3>
        <div class="rn_CheckoutPageHeaderDescription">#rn:msg:CUSTOM_MSG_cp_checkout_page_header_description#</div>
        <div class="esg_centerContainer">
            <div id="PaymentProcessAccordion" style="visibility: hidden;">
                <!-- Begin Donor Billing Information accordion tab -->
                <h3 class="AccordionTabHeader" data-id="donor_billing_info"><span class="AccordionTabStatusIcon"></span>1. Confirm Donor Billing Information</h3>
                <div>
                    <rn:condition logged_in="true">
                        <div class="rn_BillingDonorInfoContainer">
                            <form id="rn_CreateAccount1001" onsubmit="return false;">

                                <fieldset>
                                    <legend>
                                        #rn:msg:CONTACT_INFO_LBL#
                                    </legend>
                                    <rn:widget path="input/ContactNameInput" required="true" />
                                    <rn:widget path="input/FormInput" name="contacts.street" required="true" />
                                    <rn:widget path="input/FormInput" name="contacts.city" required="true" />
                                    <rn:widget path="input/FormInput" name="contacts.country_id" />
                                    <rn:widget path="input/FormInput" name="contacts.prov_id" label_input="State Or Province" />
                                    <rn:widget path="input/FormInput" name="contacts.postal_code" required="true" label_input="Postal Code" />
                                    <rn:widget path="input/FormInput" name="contacts.ph_home" label_input="Phone Number" />
                                </fieldset>
                                <button onclick="window.history.back()" style="display:none;">
                                    Back
                                </button>
                                <div class="esg_checkoutButton" style="display:none;">
                                    <rn:widget path="custom/input/DonorBillingInfoFormSubmit" label_button="Continue" on_success_url="/app/payment/checkout" error_location="rn_ErrorLocation" />
                                </div>
                            </form>
                        </div>
                    </rn:condition>
                    <rn:condition logged_in="false">
                        <div id="rn_PageContent" class="rn_Account rn_LoginForm">
                            <div class="rn_Padding">
                                <div class="rn_Column rn_LeftColumn rn_CreateContactFormOnCheckout">
                                    <h1>Checkout as Guest</h1>
                                    <form id="rn_CreateAccount" onsubmit="return false;">
                                        <div id="rn_ErrorLocation"></div>
                                        <rn:widget path="custom/input/NotifyOnChangeTextInput" name="Contact.Emails.PRIMARY.Address" required="true" validate_on_blur="true" initial_focus="true" label_input="#rn:msg:EMAIL_ADDR_LBL#" />
                                        <rn:condition config_check="intl_nameorder == 1">
                                            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true" />
                                            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true" />
                                            <rn:condition_else />
                                            <rn:widget path="input/FormInput" name="Contact.Name.First" label_input="#rn:msg:FIRST_NAME_LBL#" required="true" />
                                            <rn:widget path="input/FormInput" name="Contact.Name.Last" label_input="#rn:msg:LAST_NAME_LBL#" required="true" />
                                        </rn:condition>

                                        <rn:widget path="input/FormInput" name="Contact.Address.Street" label_input="#rn:msg:STREET_LBL#" />
                                        <rn:widget path="input/FormInput" name="Contact.Address.City" label_input="#rn:msg:CITY_LBL#" />
                                        <rn:widget path="input/FormInput" name="Contact.Address.Country" label_input="#rn:msg:COUNTRY_LBL#" default_value="1" required="true" />
                                        <rn:widget path="input/FormInput" name="Contact.Address.StateOrProvince" label_input="#rn:msg:STATE_PROV_LBL#" />
                                        <rn:widget path="input/FormInput" name="Contact.Address.PostalCode" required="true" label_input="#rn:msg:POSTAL_CODE_LBL#" />
                                        <rn:widget path="input/FormInput" name="Contacts.CustomFields.CO.how_did_you_hear" />
                                        <input type="checkbox" id="subscribeToEmailCheckbox" name="subscribeToEmailCheckbox" value="" checked>
                                        <label id="subscribeToEmailCheckboxLabel" for="subscribeToEmailCheckbox">#rn:msg:CUSTOM_MSG_cp_CheckoutAssistant_email_preferences_checkbox_label#</label>
                                        <div style="display:none;">
                                            <rn:widget path="custom/input/AutoDefaultingLoginInput" name="Contact.Login" required="false" validate_on_blur="true" label_input="#rn:msg:USERNAME_LBL#" />
                                            <rn:condition config_check="EU_CUST_PASSWD_ENABLED == true">
                                                <rn:widget path="custom/input/AutoDefaultingPasswordInput" name="Contact.NewPassword" required="false" require_validation="true" label_input="#rn:msg:PASSWORD_LBL#" label_validation="#rn:msg:VERIFY_PASSWD_LBL#" />
                                            </rn:condition>
                                            <rn:widget path="input/EmailPrefSelectionInput" name="Contact.CustomFields.c.preferences" default_value="14" label_input="Email Prefs" />
                                        </div>
                                        <rn:widget path="input/FormSubmit" label_button="Continue" on_success_url="/app/payment/checkout/skip_confirm_donor/1" error_location="rn_ErrorLocation" />
                                    </form>
                                </div>
                                <div class="rn_Column rn_RightColumn rn_ThirdPartyLogin rn_ExistingDonorLogin">
                                    <h1>Existing Donor Login</h1>
                                    <rn:widget path="login/LoginForm2" label_login_button="Login" redirect_url="/app/payment/checkout/skip_confirm_donor/1" />
                                    <br />
                                    <div style="margin:10px 0;">
                                        #rn:msg:CUSTOM_MSG_cp_checkout_page_login_info_msg#
                                    </div>
                                    <a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:FORGOT_YOUR_USERNAME_OR_PASSWORD_MSG#</a>
                                </div>
                            </div>
                        </div>
                    </rn:condition>
                </div>
                <!-- End Donor Billing Information accordion tab -->
                <!-- Begin Transaction Summary accordion tab -->
                <h3 class="AccordionTabHeader" data-id="trans_sum"><span class="AccordionTabStatusIcon"></span>2. Review Transaction Summary</h3>
                <div>
                    <rn:widget path="custom/eventus/checkoutSummary" />
                </div>
                <!-- End Transaction Summary accordion tab -->
                <!-- Begin Payment Information accordion tab -->
                <h3 class="AccordionTabHeader" data-id="payment_info"><span class="AccordionTabStatusIcon"></span>3. Enter Payment Information</h3>
                <div class="ccProcessContainer">
                    <div id="rn_ErrorLocation"></div>
                    <rn:condition logged_in="true">
                        <rn:widget path="custom/eventus/ccProcess" />
                    </rn:condition>
                </div>
                <!-- End Payment Information accordion tab -->
            </div>
        </div>
    <?php else : ?>
        <div class="CheckoutAssistantErrorMessage">
            There was an error with your payment, please <a href="/app/home">start again</a>.
        </div>
    <?php endif; ?>
</div>