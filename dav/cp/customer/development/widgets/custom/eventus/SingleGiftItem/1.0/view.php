<div class="rn_GiftPopupGallery rn_ItemPopupGallery" id='giftContainer'>

    <div class="rn_ItemGalleryContentContainer">

        <div class="rn_ItemGalleryPopupDialogContainer">

            <?if(!isset($this->data['gifts'][0])):?>
                <div class='noItemFoud'>
                    No item found.
                </div>
            <?else:?>
            <div class="rn_ItemGalleryItemPopup yui3-panel-content yui3-widget-stdmod" data-id="<?= $this->data['gifts'][0]->ID ?>">

                <div class="rn_GiftPopupGalleryItemPopupContainer">
                    <div class="rn_GiftPopupGalleryItemPopupLeftColumn">
                        <div class="rn_GiftPopupGalleryItemPopupImageContainer">
                            <img class="rn_GiftPopupGalleryItemPopupImage" src="<?= $this->data['gifts'][0]->PhotoURL ?>">
                        </div>
                    </div>
                    <div class="rn_GiftPopupGalleryItemPopupRightColumn">
                        <div class="rn_GiftPopupGalleryItemPopupTitleContainer">
                            <span class="rn_GiftPopupGalleryItemPopupTitle"><?= $this->data['gifts'][0]->Title ?></span>
                        </div>
                        <div class="rn_GiftPopupGalleryItemPopupAmountContainer">
                            <span class="rn_GiftPopupGalleryItemPopupAmount">Price: $<?= $this->data['gifts'][0]->Amount ?></span>
                        </div>
                        <div class="rn_GiftPopupGalleryItemPopupDescriptionContainer">
                            <span class="rn_GiftPopupGalleryItemPopupDescription"><?= $this->data['gifts'][0]->Description ?></span>
                        </div>

                        <div class="rn_GiftPopupGalleryItemPopupAddToCartForm">
                            <? if (!$this->data['attrs']['isLoggedIn']) : ?>
                                <div class="giftAdditionalInfo">
                                    <p><a href="/app/utils/login_form/redirect/<?=$this->data['redirectOnLogin']?>">Log In</a> to order this item for your sponsored student or a student in need</p>
                                </div>
                            <? else : ?>
                                <?/* Child list */?>
                                <fieldset class="rn_GiftPopupGalleryItemPopupSponsoredChildFieldset">
                                    <legend>To which sponsored student would you like to give this gift?</legend>

                                    <?if (count($this->data['eligibleChildren']) > 1):?>
                                        <div>
                                            <input class="rn_GiftPopupGalleryItemPopupSponsoredChildRecipient" type="checkbox" value="all">
                                                <span>All</span>
                                        </div>
                                        <? $columnCount = floor(count($this->data['eligibleChildren']) / 2); ?>
                                        <div class="rn_GiftPopupGalleryItemPopupSponsoredChildFieldsetColumn">
                                        <? for ($i = 0; $i <= $columnCount; $i++):?>
                                                <div>
                                                    <input class="rn_GiftPopupGalleryItemPopupSponsoredChildRecipient" type="checkbox" value="<?=$this->data['eligibleChildren'][$i]['id']?>">
                                                    <span><?=$this->data['eligibleChildren'][$i]['name']?></span>
                                                </div>
                                        <? endfor; ?>
                                        </div>

                                        <div class="rn_GiftPopupGalleryItemPopupSponsoredChildFieldsetColumn">
                                        <? for (; $i < count($this->data['eligibleChildren']); $i++):?>
                                                <div>
                                                    <input class="rn_GiftPopupGalleryItemPopupSponsoredChildRecipient" type="checkbox" value="<?=$this->data['eligibleChildren'][$i]['id']?>">
                                                    <span><?=$this->data['eligibleChildren'][$i]['name']?></span>
                                                </div>
                                        <? endfor; ?>
                                        </div>
                                        
                                    <? else: ?>
                                        <div class="rn_GiftPopupGalleryItemPopupSponsoredChildFieldsetColumn">
                                            <div>
                                                <input class="rn_GiftPopupGalleryItemPopupSponsoredChildRecipient" type="checkbox" value="8793">
                                                <span>Any Student in Need</span>
                                            </div>
                                        </div>
                                    <? endif; ?>

                                    

                                    <div class="rn_GiftPopupGalleryItemPopupSponsoredChildrenFieldsetErrorContainer"></div>
                                </fieldset>
                                <? /******** */?>

                                <div class="rn_GiftPopupGalleryItemPopupInputContainer" data-field="quantity" data-children-count="1">
                                    <span class="rn_GiftPopupGalleryItemPopupAddToCartFormLabel">Quantity:</span>
                                    <input class="rn_GiftPopupGalleryItemPopupQuantity" type="number" value="0">
                                    <div class="rn_GiftPopupGalleryItemPopupInputErrorContainer">
                                    </div>
                                </div>
                                <div class="rn_GiftPopupGalleryItemPopupInputContainer">
                                    <button value="submit">Add to Cart</button>
                                </div>
                            <? endif; ?>
                        </div>
                    </div>
                </div>

            </div>
            <?endif;?>
        </div>
    </div>
</div>