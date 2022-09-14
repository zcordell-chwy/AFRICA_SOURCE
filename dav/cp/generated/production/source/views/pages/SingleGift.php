<rn:meta title="Gift for Child"  template="standard.php" login_required="false" clickstream="give_gift"/>
<div id="rn_PageContent" class="rn_GiveGiftPage">
	<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:CUSTOM_MSG_cp_standard_template_gift_for_child_nav_link_label#" banner_img_path="/euf/assets/images/banners/gift.jpg" />
	
	<?if( date("U",strtotime(getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_BEGIN))) < time() && date("U",strtotime(getConfig(CUSTOM_CFG_HOLIDAY_GIFT_LIMIT_END))) > time() ){?>
	   <div class="holidayAnnouncement">#rn:msg:CUSTOM_MSG_HOLIDAY_GIFT_MESSAGE#</div>	
	<?}?>
    
    <div class="rn_AfricaNewLifeLayoutLeftColumn">
		<rn:widget path="custom/eventus/SingleGiftItem"/>
	</div>
	<div class="rn_AfricaNewLifeLayoutRightColumn">
		<rn:widget path="custom/shopping/ChildGiftShoppingCart" id="gift" />
	</div>
</div>