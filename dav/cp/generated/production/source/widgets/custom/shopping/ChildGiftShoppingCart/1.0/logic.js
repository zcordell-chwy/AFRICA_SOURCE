RightNow.namespace("Custom.Widgets.shopping.ChildGiftShoppingCart");
Custom.Widgets.shopping.ChildGiftShoppingCart =
  Custom.Widgets.shopping.ShoppingCart.extend({
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
      /**
       * Overrides Custom.Widgets.shopping.ShoppingCart#constructor.
       */
      constructor: function () {
        this.renderedChildGroupContainers = {};
        this.Y.one(
          this.baseSelector + " button.rn_ShoppingCartCheckoutButton"
        ).set("disabled", false);
        // Call into parent's constructor
        this.parent();
      },

      /**
       * Renders a child gift shopping cart line item by building the DOM content for the line item and appending it to the shopping cart
       * content container. Overrides parent method to support grouping items by child recipient.
       * @params {object} lineItem the line item to render
       */
      renderLineItem: function (lineItem) {
        var lineItemHTML = new EJS({
            text: this.getStatic().templates.lineItem,
          }).render({
            id: lineItem.id,
            merch: lineItem.merch,
            quantity: lineItem.quantity,
            customData: lineItem.customData,
            formatPrice: this.formatPrice,
          }),
          lineItemNode = this.Y.Node.create(lineItemHTML),
          shoppingCartLineItemContainer = this.Y.one(
            this.baseSelector + " .rn_ShoppingCartLineItemContainer"
          );

        // Register click event handler for remove link
        lineItemNode.one("a.rn_LineItemRemoveLink").on(
          "click",
          function (evt) {
            var removeLink = evt.currentTarget,
              lineItemNode = removeLink.ancestor(".rn_LineItem"),
              lineItemID = parseInt(lineItemNode.getAttribute("data-id"));
            this.removeLineItem(lineItemID);
          },
          this
        );

        // Set "data-prev" attribute on the quantity input, which stores the old quantity so that we can
        // calculate a delta value to pass to the update quantity routines
        lineItemNode
          .one("input.rn_LineItemQuantity")
          .setAttribute("data-prev", lineItem.quantity);

        // Register change event handler for quantity input
        lineItemNode.one("input.rn_LineItemQuantity").on(
          "change",
          function (evt) {
            var quantityInput = evt.currentTarget,
              oldQty = parseInt(quantityInput.getAttribute("data-prev")),
              newQty = parseInt(quantityInput.get("value")),
              lineItemNode = quantityInput.ancestor(".rn_LineItem"),
              lineItemID = parseInt(lineItemNode.getAttribute("data-id"));
            if (!isNaN(newQty) && newQty >= 1) {
              this.updateLineItemQty(lineItemID, newQty - oldQty);
              quantityInput.setAttribute("data-prev", newQty);
            } else {
              // Quantity must a number and must be 1 or greater, reset to old value
              quantityInput.set("value", oldQty);
            }
          },
          this
        );

        //.on change will fire on lose focus.  we need to disallow clicking of "Checkout" button until after
        // that ajax requests completes to update cart items
        // lineItemNode.one("input.rn_LineItemQuantity").on("keypress", function(evt){
        // this.Y.one(this.baseSelector + " button.rn_ShoppingCartCheckoutButton").set("disabled", true);
        // }, this);

        // Append line item to child line item group container
        var childGroupContainer = null;
        if (
          this.renderedChildGroupContainers[lineItem.customData.childID] ===
          undefined
        ) {
          // Build child line item group container
          childGroupContainer = this.buildChildGroupContainer(
            lineItem.customData
          );
          this.renderedChildGroupContainers[lineItem.customData.childID] =
            childGroupContainer;
          childGroupContainer.setAttribute("data-line-item-count", 0);
        } else {
          childGroupContainer =
            this.renderedChildGroupContainers[lineItem.customData.childID];
        }
        // Append or re-append child group contanier to shopping cart line item container if line
        // item count is zero, which indicates the node has never been appended before or was removed
        // previously due to all of its line items being removed.
        if (
          parseInt(childGroupContainer.getAttribute("data-line-item-count")) ===
          0
        ) {
          shoppingCartLineItemContainer.append(childGroupContainer);
        }
        childGroupContainer
          .one(".rn_ChildLineItemsContainer")
          .append(lineItemNode);
        childGroupContainer.setAttribute(
          "data-line-item-count",
          parseInt(childGroupContainer.getAttribute("data-line-item-count")) + 1
        );

        cospon = false;
        if (this.data.js && this.data.js.cospon) {
          this.Y.all("div.rn_LineItemQuantityContainer").hide();
        }
      },

      /**
       * Removes a line item from the shopping cart UI. Overrides parent method so that we can delete the child line item group if we
       * are removing the last line in the group.
       * @param {integer} id the ID of the line item to remove from the UI
       */
      removeLineItemFromShoppingCartUI: function (id) {
        var lineItemNode = this.Y.one(
            this.baseSelector +
              ' .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id="' +
              id +
              '"]'
          ),
          lineItemGroupNode = lineItemNode.ancestor(".rn_ChildLineItemGroup"),
          groupLineItemCount = parseInt(
            lineItemGroupNode.getAttribute("data-line-item-count")
          );
        lineItemNode.remove(true);
        if (--groupLineItemCount === 0) {
          lineItemGroupNode.remove();
        }
        lineItemGroupNode.setAttribute(
          "data-line-item-count",
          groupLineItemCount
        );
      },
    },

    /**
     * Builds the DOM content for the child line item group and returns it as a YUI Node object.
     * @param {object} lineItemCustomData the line item custom data which contains the child line item group data
     */
    buildChildGroupContainer: function (lineItemCustomData) {
      var childLineItemGroupHTML = new EJS({
          text: this.getStatic().templates.childLineItemGroup,
        }).render({
          data: lineItemCustomData,
        }),
        childLineItemGroupNode = this.Y.Node.create(childLineItemGroupHTML);

      return childLineItemGroupNode;
    },
  });
