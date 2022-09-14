<rn:meta title="Make a Donation" template="standard.php" clickstream="home" login_required="false" />
<?php
$f_id = getUrlParm('f_id');
$CI    		= &get_instance();
$items = $CI->model('custom/items')->getSingleDonationItem($f_id);
$CI->session->setSessionData(array("DonateDesc" => $items[0]->Title));
?>
<!-- <div class="rn_HeaderContainer">
	<rn:condition config_check="CUSTOM_CFG_SHOW_ALERT == true">
		<div id="rn_Alert" class="rn_Alert rn_AlertBox rn_ErrorAlert">#rn:msg:CUSTOM_MSG_ALERT#</div>
	</rn:condition>
</div> -->
<div class="row">
	<!-- <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Make a Donation" banner_img_path="/euf/assets/images/banners/donate.jpg" /> -->
	<div class="column">
		<h1>GIVE IN <br>SUPPORT <br>OF A <br>DREAM</h1>
	</div>
	<div class="column">
		<figure class="giftImg">
			<img src=<?php echo $items[0]->PhotoURL; ?>>
		</figure>
		<!-- Commented below line as part of issue fix Issue#110-->
		<!-- <h2>YOUR <span id="giftAmount">$50</span> GIFT WILL HELP</h2> -->

		<span id="donate_campaign_desc" class="donate_campaign_desc">
			<p><?php echo $items[0]->Description; ?></p>
		</span>
	</div>
	<div class="column">
		<div class="rn_AfricaNewLifeLayoutLeftColumn">
			<rn:widget path="custom/payment/SingleDonation" />
		</div>
		<div class="rn_AfricaNewLifeLayoutRightColumn">
			<rn:widget path="custom/sponsorship/DonatePayment" />
		</div>
	</div>
</div>

<style>
	* {
		box-sizing: border-box;
	}

	/* Create three equal columns that floats next to each other */
	.column {
		float: left;
		width: 33.33%;
		padding: 10px;
		height: 300px;
		/* Should be removed. Only for demonstration */
	}

	/* Clear floats after the columns */
	.row:after {
		content: "";
		display: table;
		clear: both;
	}

	ul.square {
		list-style-type: square;
	}
</style>