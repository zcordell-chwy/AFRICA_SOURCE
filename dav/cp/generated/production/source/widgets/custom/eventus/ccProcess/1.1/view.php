<div class="esg_ccProcess">
	<? logMessage("instance of = " . get_class($this->data['trans']));
	if ($this->data['trans'] instanceof \RightNow\Connect\v1_3\financial\transactions) {
		$message = "Starting Process Payment\n";
		$message .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";
		//$message .= "Frontstream Form Data: ".print_r($this -> data['js']['postToFsVals'], true);

		$this->CI->model('custom/transaction_model')->addNoteToTrans($this->data['trans'], $message);
		$this->data['trans']->save();
	?>
		<div class="ccInputArea">
			<h3 style="color: red;">Due to a necessary systems upgrade, you may encounter difficulties with some features of your account. Please be patient with us; we are working on a solution and will be able to assist you in the coming business days! Please contact us at info@africanewlife.org or call 503.906.1590 for assistance.</h3>
			<br />
			<h3 style="color: red;">Adding payment methods is currently disabled.</h3>
			<br />
			<div id="existingPaymentContainer" class="esg_PaymentSummary esg_checkoutSummary">
				<form id='storedMethodForm' onsubmit="return false;">
					<div id="rn_ErrorLocation" class="rn_Hidden"></div>
					<table>
						<tr>
							<th>Payment Type</th>
							<th>Card or Account Number</th>
							<th>Expiration Date</th>
							<th>Select One</th>
						</tr>
						<?
						foreach ($this->data['paymentMethodsArr'] as $pm) {

							//don't print the exp month on checking account
							$expDate = ($pm->expMonth != "") ? $pm->expMonth . '/' . $pm->expYear : "";

							if (count($this->data['paymentMethodsArr']) > 1) {
								print('<tr>
								<td>' . $pm->CardType . '</td>
								<td> ************' . $pm->lastFour . ' </td>
								<td>' . $expDate . '</td>
								<td><input type="radio" value="' . $pm->ID . '" name="paymentMethodId"/></td>
								</tr>');
							} else {
								print('<tr>
								<td>' . $pm->CardType . '</td>
								<td> ************' . $pm->lastFour . ' </td>
								<td>' . $expDate . '</td>
								<td><input type="radio" value="' . $pm->ID . '" name="paymentMethodId" checked="checked" /></td>
								</tr>');
							}
						}
						?>
					</table>
					<?
					foreach ($this->data['js']['postToFsVals'] as $key => $value) {
						print('<input type="hidden" name="' . $key . '" value="' . $value . '" />');
					}
					?>
					<div class="esg_checkoutButton">
						<rn:widget path="custom/eventus/ajaxCustomFormSubmit" confirm_message="You are about to submit a payment for #rn:php:'$'.$this -> CI -> model('custom/items') -> getTotalDueNow($this->CI->session->getSessionData('sessionID')).'.00'#.  By selecting OK you agree to pay this amount." label_button="Pay Now" on_success_url="/app/payment/successCC" error_location="rn_ErrorLocation" />
					</div>
				</form>
			</div>
		</div>
</div>
<? } else { ?>
	<div>
		No Transaction Found
	</div>
<? } ?>