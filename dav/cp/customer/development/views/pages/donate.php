<rn:meta title="Make a Donation"  template="responsive.php" login_required="false" clickstream="donate"/>

<div id="rn_PageContent" class="rn_DonatePage">
	<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Make a Donation" banner_img_path="/euf/assets/images/banners/donate.jpg" />
	<div class="rn_AfricaNewLifeLayoutLeftColumn">
		<rn:widget path="custom/display/DonationFundPopupGallery" 
			title="#rn:msg:CUSTOM_MSG_cp_donate_page_donation_popup_gallery_title#" 
			description="#rn:msg:CUSTOM_MSG_cp_donate_page_donation_popup_gallery_description#"
			preload_item_popup_data="true"
			columns="3"
			rows="5"
			loading_msg="Loading donation fund gallery..."
			mission_members_photo_url="/euf/assets/images/missionteammembers.JPG"
			mission_members_sort_index="14" />
	</div>
	<div class="rn_AfricaNewLifeLayoutRightColumn">
		<rn:widget path="custom/shopping/DonationShoppingCart" id="donation" />
	</div>
</div>