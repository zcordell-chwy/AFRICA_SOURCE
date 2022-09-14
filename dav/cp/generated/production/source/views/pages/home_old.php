<rn:meta title="Sponsor A Student" template="standard.php" login_required="false" clickstream="home" />
<style>
	body {
		background: url(/euf/assets/images/backgrounds/landscape-donation.jpg) no-repeat center center fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		background-size: cover;
	}

	.grid {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr 1fr;
		grid-gap: 3ch;
		grid-template-rows: auto;
		grid-template-areas:
			"splash children children children"
			"description children children children"
			". children children children"
			"moreInfo moreInfo moreInfo moreInfo";
	}

	.splash {
		grid-area: splash;
	}

	.description {
		grid-area: description;
	}

	.children {
		grid-area: children;
	}

	.filters>* {
		display: inline-block;
	}

	.moreInfo {
		grid-area: moreInfo;
	}
</style>

<div id="rn_PageContent" class="rn_Home">

	<div class="grid">
		<aside class="splash">
			<h1>Let<br>Every<br>Child<br>Dream</h1>
		</aside>
		<aside class="description">
			<h3>Placeholder</h3>
			<p>Description</p>
		</aside>
		<section class="children">
			<rn:container report_id="101808">
				<div class="filters">
					<rn:widget path="search/FilterDropdown" filter_name="Gender" />
					<rn:widget path="search/FilterDropdown" filter_name="BirthMonth" />
					<rn:widget path="search/FilterDropdown" filter_name="BirthYear" />
					<rn:widget path="search/FilterDropdown" filter_name="Community" />
					<rn:widget path="search/SearchButton2" />
				</div>
				<div class="gallery">
					<rn:widget path="custom/sponsorship/UnsponsoredChildMultiLine" />
					<!-- <rn:widget path="custom/sponsorship/UnsponsoredChildCarousel" /> -->
				</div>
				<div class="paginator">
					<rn:widget path="reports/Paginator2" />
				</div>
			</rn:container>
		</section>
		<article class="moreInfo">
			<h1>Placeholder</h1>
		</article>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		document.cookie = "payment_message1=;path=/";
		document.cookie = "payment_message=;path=/";
	});
</script>

<!-- <script src="//action.dstillery.com/orbserv/nsjs?adv=cl1026194&ns=3607&nc=ANL_AppHome&ncv=37&dstOrderId=[OrderId]&dstOrderAmount=[OrderAmount]" type="text/javascript"></script> -->