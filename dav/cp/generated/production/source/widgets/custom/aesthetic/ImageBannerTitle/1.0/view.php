<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
	<!--<div class="rn_BannerImageContainer">
		<img class="rn_BannerImage" src="<?= $this->data['display_attrs']['banner_img_path'] ?>" />
	</div>
	<div class="rn_BannerTextContainer">
		<div class="rn_BannerTextInnerContainer">
			<div class="rn_BannerText">
				<?= $this->data['display_attrs']['banner_title'] ?>
			</div>
		</div>
	</div>-->
	<div class="ResponsiveRow">
	    <div class="ResponsiveCol-12 no-padding">
	        <img class="stretch-parent" src="<?= $this->data['display_attrs']['banner_img_path'] ?>" />
	        <div class="overlay-parent">
	            <table class="stretch-parent">
	                <tr>
	                    <td class="v-align-left">
	                        <h1 class="banner-title-text"><?= $this->data['display_attrs']['banner_title'] ?></h1>
	                    </td>
	                </tr>
	            </table>
	        </div>
	    </div>
	</div>
</div>