<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">	
	<? 
	$url = \RightNow\Utils\Url::getOriginalUrl();

	?>
	<div class="row">
		<h2 class="center">
		Thank you,
		</h2>
		<h3><span ><label id="custName" class="center"><?php echo $this->data['firstName']; ?></label></span></h3>
		<div class="column">
			<figure class="childImg">
			<? if(strpos($url,"app/payment/success") !== FALSE) : ?>
				<img src="/euf/assets/sponsor/sponsorship_thank_you.png">
			<? elseif(strpos($url,"app/payment/donate_success") !== FALSE) : ?>
				<img src="/euf/assets/sponsor/donate_thank_you_child.png">
			<? endif; ?>
			</figure>
			<div class="description">
			<? if(strpos($url,"app/payment/success") !== FALSE) : ?>
				#rn:msg:CUSTOM_MSG_SPONSOR_SUCCESS#
			<? elseif(strpos($url,"app/payment/donate_success") !== FALSE) : ?>
				#rn:msg:CUSTOM_MSG_CP_DONATE_SUCCESS#
			<? endif; ?>				
				
			</div>
		</div>
		<? if(strpos($url,"app/payment/success") !== FALSE) : ?>
			<div class="column">
				<form action="/home" class="inline">
					<button id="giftPage" type="button">I'd Like to Send a Gift to my Student</button>
				</form>			
				<form action="/home" class="inline">
					<button id="bigImpactPage" type="button">#rn:msg:CUSTOM_MSG_IMPACT_BTN#</button>
				</form>
			</div>
		<? endif; ?>						
	</div>
</div>