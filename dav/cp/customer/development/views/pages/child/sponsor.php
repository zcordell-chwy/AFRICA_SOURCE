<rn:meta title="Student Info" template="standard.php" login_required="false" clickstream="child_info" />
<? 
	$status = $this->get_instance()->model('custom/sponsorship_model')->isChildRecordLocked(getUrlParm('id'));

	logMessage("ChildID:".getUrlParm('id'));
	logMessage("Status:");
	logMessage($status);
	if($status->isLocked){
		logMessage("is locked");
	}else{
		logMessage("NOT locked");
	}
?>

<div class="rn_Container">
	<div class="rn_PageContent">
		<!-- <div class="rn_HeaderContainer">
			<rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
				<div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
			</rn:condition>
		</div> -->

		<?if($status->isLocked):?>
			#rn:msg:CUSTOM_MSG_STUDENT_RECORD_LOCKED#
		<?endif;?>

		<div id="rn_PageContent" class="rn_ChildInfo">

			<rn:widget path="custom/sponsorship/ChildInfo" community='false' sponsorship='true' />
			<?if(!$status->isLocked):?>
				<rn:widget path="custom/sponsorship/SponsorshipPayment" />
			<?endif;?>
			<!-- payment widget will go here -->
		</div>
	</div>
</div>