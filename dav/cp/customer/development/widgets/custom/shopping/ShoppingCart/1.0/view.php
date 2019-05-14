<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
	<!-- Shopping Cart Header -->
	<div class="rn_ShoppingCartHeaderContainer">
		<div class="rn_ShoppingCartHeader">
			<span><?= $this->data['attrs']['header_text'] ?></span>
		</div>
	</div>
	<!-- Shopping Cart Line Items -->
	<div class="rn_ShoppingCartLineItemContainer">
		<!-- Line items are dynamically populated -->
		<div class="rn_ShoppingCartLoadingIndicatorContainer">
			<div class="rn_ShoppingCartLoadingIndicator">
				<img class="rn_ShoppingCartLoadingIndicatorImg" src="/euf/assets/images/loading.gif" />
				<div class="rn_ShoppingCartLoadingIndicatorMsgContainer">
					<?= $this->data['attrs']['loading_msg'] ?>
				</div>
			</div>
		</div>
	</div>
	<!-- Shopping Cart Total -->
	<div class="rn_ShoppingCartTotalContainer">
		<div class="rn_Total">
			<div class="rn_TotalAmountContainer">
				<span class="rn_TotalAmountLabel">Total (0 items):</span>
				<span class="rn_TotalAmount">&#36;0.00</span>
			</div>
		</div>
	</div>
	<!-- Shopping Cart Checkout Button -->
	<div class="rn_ShoppingCartCheckoutButtonContainer">
		<button class="rn_ShoppingCartCheckoutButton"><?= $this->data['attrs']['checkout_button_label'] ?></button>
	</div>
</div>