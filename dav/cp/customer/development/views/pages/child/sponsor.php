<rn:meta title="Student Info" template="standard.php" login_required="false" clickstream="child_info" />
<div class="rn_Container">
	<div class="rn_PageContent">
		<!-- <div class="rn_HeaderContainer">
			<rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
				<div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
			</rn:condition>
		</div> -->
		<div id="rn_PageContent" class="rn_ChildInfo">

			<rn:widget path="custom/sponsorship/ChildInfo" community='false' sponsorship='true' />
			<rn:widget path="custom/sponsorship/SponsorshipPayment" />
			<!-- payment widget will go here -->
		</div>
	</div>
</div>