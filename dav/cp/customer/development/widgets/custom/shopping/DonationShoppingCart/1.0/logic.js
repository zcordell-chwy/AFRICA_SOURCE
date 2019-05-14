RightNow.namespace('Custom.Widgets.shopping.DonationShoppingCart');
Custom.Widgets.shopping.DonationShoppingCart = Custom.Widgets.shopping.ShoppingCart.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides Custom.Widgets.shopping.ShoppingCart#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();
        },

        /**
         * Renders a shopping cart line item by building the DOM content for the line item and appending it to the shopping cart
         * content container. Overriding parent method so that we can add change handlers to the one-time amount and monthly amount
         * input fields. 
         * @params {object} lineItem the line item to render
         */
        renderLineItem: function(lineItem){
            var lineItemNode = this.parent(lineItem);

            // Set "data-prev" attribute on the one-time amount input, which stores the old one-time amount so that we can
            // calculate a delta value in the 'onDonationAmountChange' routine
            lineItemNode.one("input.rn_LineItemOneTimeAmount").setAttribute("data-prev", lineItem.customData.amountOneTime);
            // Register change event handler for one-time amount input
            lineItemNode.one("input.rn_LineItemOneTimeAmount").on("change", this.onDonationAmountChange, this);
            
            if(lineItem.customData.donationType === "fund"){
                // Set "data-prev" attribute on the monthly amount input, which stores the old monthly amount so that we can
                // calculate a delta value in the 'onDonationAmountChange' routine
                lineItemNode.one("input.rn_LineItemMonthlyAmount").setAttribute("data-prev", lineItem.customData.amountMonthly);
                // Register change event handler for monthly amount input
                lineItemNode.one("input.rn_LineItemMonthlyAmount").on("change", this.onDonationAmountChange, this);
            }
        }
    },

    /**
    * Handles when an amount input changes. Validates data and invokes 'updateLineItemCustomData' to
    * update the customData on the line item where the one-time and monthly amounts are stored.
    * @param {object} evt the DOM event object for the change event
    */
    onDonationAmountChange: function(evt){
        var amountInput = evt.currentTarget,
            oldAmount = parseFloat(amountInput.getAttribute("data-prev")),
            newAmount = parseFloat(amountInput.get("value")),
            lineItemNode = amountInput.ancestor(".rn_LineItem"),
            lineItemID = parseInt(lineItemNode.getAttribute("data-id")),
            amountContainer = amountInput.ancestor(".rn_LineItemAmountContainer"),
            isOneTimeAmount = amountContainer.hasClass("rn_LineItemOneTimeAmountContainer"),
            otherAmount = isOneTimeAmount ? 
                lineItemNode.one("input.rn_LineItemMonthlyAmount").get("value") :
                lineItemNode.one("input.rn_LineItemOneTimeAmount").get("value");
        // Donation amount must be a number and at least one of the donation amounts must be non-zero
        if(
            !isNaN(newAmount) && 
            ((otherAmount == 0 && newAmount > 0) || (otherAmount > 0 && newAmount >= 0))
        ){
            amountInput.set("value", this.formatPrice(newAmount, false));
            var whichAmount = null;
            if(isOneTimeAmount){
                whichAmount = "onetime";
            }else{
                whichAmount = "monthly";
            }
            this.updateDonationAmount(lineItemID, newAmount, oldAmount, whichAmount);
            amountInput.setAttribute("data-prev", newAmount);
        }else{
            // Invalid donation amount, reset to old value
            amountInput.set("value", this.formatPrice(oldAmount, false));
        }
    },

    /**
    * Handles updating the UI and launching an AJAX request to update the session data when either the
    * one-time or monthly donation amount changes for line item.
    * @param {integer} id the ID of the line item in question
    * @param {float} newAmount the new amount (after user change)
    * @param {float} oldAmount the old amount (before user change)
    * @param {string} oneTimeOrMonthly flag indicating whether the amount changing is the one-time or monthly amount
    */
    updateDonationAmount: function(id, newAmount, oldAmount, oneTimeOrMonthly){
        if(oneTimeOrMonthly === "onetime"){
            updatedCustomData = {
                amountOneTime: newAmount
            };
        }else{
            updatedCustomData = {
                amountMonthly: newAmount
            };
        }
        var lineItem = this.getLineItemFromCache(id);
        // Update price in UI
        this.updateLineItemPriceInShoppingCartUI(id, lineItem.merch.price + (newAmount - oldAmount));
        // Update total in UI
        this.updateTotal(newAmount - oldAmount, 0);
        // Call AJAX method to update custom data and price in session
        this.updateLineItemCustomDataInShoppingCartSessionDataAJAX(id, updatedCustomData, 
        function(){
            // On success (updated custom data has been cached)
            // Update line item price in cache to sum of one-time and monthly donation amounts so that total is correct
            lineItem.merch.price = 
                lineItem.customData.amountOneTime +
                lineItem.customData.amountMonthly;
        },
        function(){
            // On failure
            // Reset total
            this.updateTotal(oldAmount - newAmount, 0);
            // Restore old amount
            this.updateLineItemAmountInShoppingCartUI(id, oldAmount, oneTimeOrMonthly);
            // Restore old price
            this.updateLineItemPriceInShoppingCartUI(id, lineItem.merch.price);
        });
    },

    /**
    * Update the donation amount of a line item in the shopping cart UI.
    * @param {integer} id the ID of the line item to update the donation amount of
    * @param {float} updatedAmount the updated donation amount
    * @param {string} oneTimeOrMonthly flag indicating whether the amount being updated is the one-time or monthly amount
    */
    updateLineItemAmountInShoppingCartUI: function(id, updatedAmount, oneTimeOrMonthly){
        var lineItemNode = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id=\"" + id + "\"]"),
            amountInput = null;
        if(oneTimeOrMonthly === "onetime"){
            amountInput = lineItemNode.one("input.rn_LineItemOneTimeAmount");
        }else{
            amountInput = lineItemNode.one("input.rn_LineItemMonthlyAmount");
        }
        amountInput.set("value", this.formatPrice(updatedAmount, false));
        amountInput.setAttribute("data-prev", updatedAmount);
    },

    /**
    * Update the price of a line item in the shopping cart UI.
    * @param {integer} id the ID of the line item to update the price of
    * @param {float} updatedPrice the updated price of the line item
    */
    updateLineItemPriceInShoppingCartUI: function(id, updatedPrice){
        var lineItemNode = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id=\"" + id + "\"]"),
            priceLabel = lineItemNode.one("span.rn_LineItemPrice");
        priceLabel.setHTML(this.formatPrice(updatedPrice));
    }

});