<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>" style="background: white;">
	<h2>
		<p>YOUR TAX-DEDUCTIBLE GIFT</p>
	</h2>
	<div class="tabs" id="newDonatePayment">
		<input type="radio" id="monthly_radio" name="paymentmode" class="monthly" checked>
		<label for="monthly_radio" class="monthly radio tablinks">Give Monthly</label>
		<input type="radio" id="onetime_radio" name="paymentmode" class="onetime">
		<label for="onetime_radio" class="onetime radio tablinks">Give Once</label>
	</div>
	<div class="donation_amt" id="donationAmount">
		<div id="giftamount">
			<p>Total Monthly Gift Amount</p>
			<? if ($this->data['DefaultMonthlyAmount']) : ?>
				<span id="monthly">&#36;<?= $this->data['DefaultMonthlyAmount']; ?></span>
			<? else : ?>
				<span id="monthly">&#36;50</span>
			<? endif; ?>
		</div>
		<div id="giftOneTime" class="rn_Hidden">
			<p>One Time Gift Amount</p>
			<? if ($this->data['DefaultOneTimeAmount']) : ?>
				<span id="onetime">&#36;<?= $this->data['DefaultOneTimeAmount']; ?></span>
			<? else : ?>
				<span id="onetime">&#36;50</span>
			<? endif; ?>
		</div>
		<select name="amount" id="amount" class="rn_Hidden">
			<option value="25">$25</option>
			<option value="50" select>$50</option>
			<option value="100">$100</option>
			<option value="other">OTHER</option>
		</select>

		<div id="amount_slider">

			<label for="amount_25" class="radio-inline">
				<input type="radio" id="amount_25" name="fav_language" value="25">
				&#36;25</label>

			<label for="amount_50" class="radio-inline">
				<input type="radio" id="amount_50" name="fav_language" value="50">
				&#36;50</label>

			<label for="amount_100" class="radio-inline">
				<input type="radio" id="amount_100" name="fav_language" value="100">
				&#36;100</label>
			<!-- 		<div class="range-wrap">
				<div class="range-value" id="rangeV"></div>
				<input class="slider_range" id="range" type="range" value="50" min="25" max="100" step="25">
			</div> -->
			<label for="amount_other" class="radio-inline">
				<input type="radio" id="amount_other" name="fav_language" value="Other">
				OTHER</label>
		</div>
		<input type="number" min="0" step="1" id="other_amount" name="other_amount" class="others rn_Hidden" pattern="[1-9][0-9]*" onfocus="this.previousValue = this.value" onkeydown="this.previousValue = this.value" oninput="validity.valid || (value = this.previousValue)">
	</div>
</div>
<script>
	// const
	//   range = document.getElementById('range'),
	//   rangeV = document.getElementById('rangeV'),
	//   setValue = ()=>{
	//     const
	//       newValue = Number( (range.value - range.min) * 100 / (range.max - range.min) ),
	//       newPosition = 10 - (newValue * 0.2);
	//     rangeV.innerHTML = `<span>${range.value}</span>`;
	//     rangeV.style.left = `calc(${newValue}% + (${newPosition}px))`;
	//   };
	// document.addEventListener("DOMContentLoaded", setValue);
	// range.addEventListener('input', setValue);	
</script>