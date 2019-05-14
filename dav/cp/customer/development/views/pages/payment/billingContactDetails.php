<?
$CI = get_instance();
$existingPaymentMethods = $CI -> model('custom/paymentMethod_model') -> getCurrentPaymentMethodsObjs();
?>
<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="responsive.php" clickstream="payment" login_required="true"/>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<div class="esg_centerContainer">
		<h2>Transaction Summary</h2>
		<rn:widget path="custom/eventus/checkoutSummary" />
		<div class="rn_BillingDonorInfoContainer">
			<form id="rn_CreateAccount1001" onsubmit="return false;">
				<div id="rn_ErrorLocation"></div>
				<h2>Donor Billing Information</h2>
				<fieldset>
					<legend>
						#rn:msg:CONTACT_INFO_LBL#
					</legend>
					<rn:widget path="input/ContactNameInput" required="true"/>
					<rn:widget path="input/FormInput" name="contacts.street"  required="true" />
					<rn:widget path="input/FormInput" name="contacts.city" required="true"  />
					<rn:widget path="input/FormInput" name="contacts.country_id" />
					<rn:widget path="input/FormInput" name="contacts.prov_id" label_input="State Or Province" />
					<rn:widget path="input/FormInput" name="contacts.postal_code" required="true" label_input="Postal Code" />
					<rn:widget path="input/FormInput" name="contacts.ph_home" />
				</fieldset>
				 <button onclick="window.history.back()" >
	                    Back
	                </button>
				<div class="esg_checkoutButton">		
					<rn:widget path="input/FormSubmit" label_button="Continue" on_success_url="/app/payment/checkout" error_location="rn_ErrorLocation"/>
				</div>
			</form>
		</div>
	</div>
</div>