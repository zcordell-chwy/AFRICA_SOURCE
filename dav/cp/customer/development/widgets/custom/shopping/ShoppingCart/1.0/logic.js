RightNow.namespace('Custom.Widgets.shopping.ShoppingCart');
Custom.Widgets.shopping.ShoppingCart = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function(){
        // Workaround for issue with browsers caching pages wtih ShoppingCart widget present. Need to always retrieve line items
        // via AJAX instead of prepopulating from widget controller. When prepopulating from widget controller, if the browser serves
        // the cached page, line items displayed to user can be out of sync with what is actually in the cart.
       this.getLineItemsAJAX();
    },

    /**
     * Auxiliary function containing initialization code for the widget contingent on the getLineItemsAJAX resolving, and as such, 
     * will be invoked by the getLineItemsAJAXCallback function.
     */
    initializeWidget: function(){
        this.lineItemTempIDSequence = 1;
        this.lineItemIndex = {};

        // Register event handler for checkout button click
        this.Y.one(this.baseSelector + " button.rn_ShoppingCartCheckoutButton").on("click", this.initiateCheckout, this);

        // Register event handler for 'evt_addLineItemToShoppingCartRequest' event, which is the interface to adding line items 
        // to the shopping cart
        RightNow.Event.on("evt_addLineItemsToShoppingCartRequest", this.handleAddLinesItemRequest, this);

        this.buildLineItemIndex();
        this.renderLineItemsOnPageLoad();
        this.buildTotal();
        this.renderTotal();
    },

    /**
    * Build a line item index to facilitate quickly retrieving line items from the line item cache by ID.
    */
    buildLineItemIndex: function(){
        this.Y.each(this.data.js.lineItems, function(lineItem){
            this.lineItemIndex[lineItem.id] = lineItem;
        }, this);
    },

    /**
    * Handles the request to add one or more new line items to the shopping cart.
    * @param {object} eventObj the event object which should have the following format:
    *
    * eventObj = {
    *     shoppingCartID: <shopping cart id>,
    *     lineItems: [
    *         {
    *             merch: {
    *                 id: <merchandise id>,
    *                 title: <merchandise title>,
    *                 price: <merchandise price>
    *             },
    *             quantity: <item quantity>,
    *             customData: { <any additional data for this line item> }
    *         },
    *         ...
    *     ]
    * };
    */
    handleAddLinesItemRequest: function(event, eventData){
        if(eventData[0].shoppingCartID === this.data.attrs.id){
            // Request is targetted at this shopping cart, process it
            this.addLineItems(eventData[0].lineItems);
        }
    },

    /**
    * Helper routine to render the line items from the line item cache (which will initially have the line items from session data)
    * on page load.
    */
    renderLineItemsOnPageLoad: function(){
        var shoppingCartLineItemContainer = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer");

        // Clear shopping cart container
        shoppingCartLineItemContainer.setHTML("");

        this.Y.each(this.data.js.lineItems, this.renderLineItem, this);
    },

    /**
     * Renders a shopping cart line item by building the DOM content for the line item and appending it to the shopping cart
     * content container. Returns the line item YUI Node object to facilitate child classes doing custom event handling.
     * @params {object} lineItem the line item to render
     */
    renderLineItem: function(lineItem){
        var lineItemHTML = new EJS({text: this.getStatic().templates.lineItem}).render({
                id: lineItem.id,
                merch: lineItem.merch,
                quantity: lineItem.quantity,
                customData: lineItem.customData,
                formatPrice: this.formatPrice
            }),
            lineItemNode = this.Y.Node.create(lineItemHTML),
            shoppingCartLineItemContainer = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer");

        // Register click event handler for remove link
        lineItemNode.one("a.rn_LineItemRemoveLink").on("click", function(evt){
            var removeLink = evt.currentTarget,
                lineItemNode = removeLink.ancestor(".rn_LineItem"),
                lineItemID = parseInt(lineItemNode.getAttribute("data-id"));
            this.removeLineItem(lineItemID);
        }, this);

        // Set "data-prev" attribute on the quantity input, which stores the old quantity so that we can
        // calculate a delta value to pass to the update quantity routines
        lineItemNode.one("input.rn_LineItemQuantity").setAttribute("data-prev", lineItem.quantity);

        // Register change event handler for quantity input
        lineItemNode.one("input.rn_LineItemQuantity").on("change", function(evt){
            var quantityInput = evt.currentTarget,
                oldQty = parseInt(quantityInput.getAttribute("data-prev")),
                newQty = parseInt(quantityInput.get("value")),
                lineItemNode = quantityInput.ancestor(".rn_LineItem"),
                lineItemID = parseInt(lineItemNode.getAttribute("data-id"));
            if(!isNaN(newQty) && newQty >= 1){
                this.updateLineItemQty(lineItemID, newQty - oldQty);
                quantityInput.setAttribute("data-prev", newQty);
            }else{
                // Quantity must a number and must be 1 or greater, reset to old value
                quantityInput.set("value", oldQty);
            }
        }, this);

        shoppingCartLineItemContainer.append(lineItemNode);

        return lineItemNode;
    },

    /**
    * Finds the total amount of the line items by processing each line item in the line item cache and summing the 
    * price * quantity. This routine should only need to be invoked once, on page load. Further adjustments to the 
    * total should use the 'updateTotal' method for efficiency.
    * Note: the total object built by this routine is strictly for the purposes of displaying an amount to the UI. 
    * The actual payment total will be calcualted by tabulating the items in session data at the payment stage.
    * This also makes the widget secure as no price value coming the DOM will be used as the actual price in
    * calculating the total. The actual price will be retrieved from the database at checkout.
    */
    buildTotal: function(){
        this.total = {
            totalAmount: 0,
            totalItems: 0
        };

        this.Y.each(this.data.js.lineItems, function(lineItem){
            this.total.totalAmount += lineItem.merch.price * lineItem.quantity;
            this.total.totalItems += lineItem.quantity;
        }, this);
    },

    /**
    * Updates the total given delta price and delta quantity values and re-renders it.
    * @param {float} deltaPrice the positive/negative change in price
    * @param {integer} deltaQty the positive/negative change in quantity
    */
    updateTotal: function(deltaPrice, deltaQty){
        this.total.totalAmount += deltaPrice;
        this.total.totalItems += deltaQty;

        this.renderTotal();
    },

    /**
    * Renders the total by building the DOM content for the total and appending it to the total container.
    */
    renderTotal: function(){
        var totalHTML = new EJS({text: this.getStatic().templates.total}).render({
                totalAmount: this.total.totalAmount,
                totalItems: this.total.totalItems,
                formatPrice: this.formatPrice
            }),
            totalNode = this.Y.Node.create(totalHTML),
            totalContainer = this.Y.one(this.baseSelector + " .rn_ShoppingCartTotalContainer");

        totalContainer.setHTML("");
        totalContainer.append(totalNode);
    },

    /**
    * Formats a price floating point value into a dollar amount string.
    * @param {float} price the price to format
    * @param {boolean} prependDollarSign pass as true to prepend dollar sign, false to omit it (default is true)
    */
    formatPrice: function(price, prependDollarSign){
        prependDollarSign = prependDollarSign != null ? prependDollarSign : true;
        if(price % 1 === 0){
            return prependDollarSign ? "$" + price + ".00" : price + ".00";
        }else{
            return prependDollarSign ? "$" + price.toFixed(2) : price.toFixed(2);
        }
    },

    /**
    * Attempts to add one or more line items both to the shopping cart UI and the server-side session data.
    * @param {array} lineItems the array of line items to add to the shopping cart
    */
    addLineItems: function(lineItems){
        // Opportunistically add line items to UI and update total. If the callback for 'addLineItemsToShoppingCartSessionDataAJAX' indicates
        // that the request was unsuccessful, it'll remove the line items from the UI, revert total, and present the user with
        // an error dialog indicating what happened. If the request was successful, it'll add the line items to the line
        // item cache.

        // Give the line items a temporary ID so that we have a way of retrieving them from the UI in the event that the request
        // to add them to session data fails ad we have to remove them
        this.Y.each(lineItems, function(lineItem){
            lineItem.id = "temp" + this.lineItemTempIDSequence++;
            this.addLineItemToShoppingCartUI(lineItem);
            this.updateTotal(lineItem.merch.price * lineItem.quantity, lineItem.quantity);
        }, this);
        this.addLineItemsToShoppingCartSessionDataAJAX(lineItems);
    },

    /**
    * Attempts to remove a line item both from the shopping cart UI and the server-side session data.
    * @param {integer} id the ID of the line item to remove from the shopping cart
    */
    removeLineItem: function(id){
        // Opportunistically remove line item from UI and update total. If the callback for 'removeLineItemFromShoppingCartSessionDataAJAX' indicates
        // that the request was unsuccessful, it'll re-render the line item from the line item cache, revert total, and present the user with
        // an error dialog indicating what happened. If the request was successful, it'll remove the line item from the line item
        // cache.
        this.removeLineItemFromShoppingCartUI(id);
        var lineItem = this.getLineItemFromCache(id);
        this.updateTotal(lineItem.merch.price * -lineItem.quantity, -lineItem.quantity);
        this.removeLineItemFromShoppingCartSessionDataAJAX(id);
    },

    /**
    * Attempts to update the quantity of a line item both in the shopping cart UI and the server-side session data.
    * @param {integer} id the ID of the line item to update the quantity of
    * @param {integer} qtyDelta the positive/negative change in quantity of the line item
    */
    updateLineItemQty: function(id, qtyDelta){
        // Since quantity is driven by a number input, it is automatically opportunistically updated in the UI. However, we still need
        // to opportunisitcally update the total. If the call back for 'updateLineItemQtyInShoppingCartSessionDataAJAX'
        // indicates the request was unsuccessful, it'll revert the quantity change to the UI, revert total, and present the user
        // with an error dialog indicating what happened. If the request was successful, it will update the quantity in the line 
        // item cache with the quantity delta value.
        var lineItem = this.getLineItemFromCache(id);
        this.updateTotal(lineItem.merch.price * qtyDelta, qtyDelta);
        this.updateLineItemQtyInShoppingCartSessionDataAJAX(id, qtyDelta);
    },

    /**
    * Initiate a checkout of the items in the shopping cart session data.
    */
    initiateCheckout: function(){
        // Disable checkout button while doing checkout
        this.Y.one(this.baseSelector + " button.rn_ShoppingCartCheckoutButton").set("disabled", true);

        this.initiateCheckoutAJAX();
    },

    /**
    * Redirects to the specified URL as part of the checkout process.
    * @param {string} url the URL to redirect to, as a CP partial page URL (ex: '/app/home')
    */
    doCheckoutRedirect: function(url){
        window.location = url;
    },

    /**
    * Adds a line item to the shopping cart UI.
    * @param {object} lineItem the line item to add to the UI
    */
    addLineItemToShoppingCartUI: function(lineItem){
        this.renderLineItem(lineItem);
    },

    /**
    * Removes a line item from the shopping cart UI.
    * @param {integer} id the ID of the line item to remove from the UI
    */
    removeLineItemFromShoppingCartUI: function(id){
        var lineItemNode = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id=\"" + id + "\"]");
        lineItemNode.remove(true);
    },

    /**
    * Update the quantity of a line item in the shopping cart UI.
    * @param {integer} id the ID of the line item to update the quantity of
    * @param {integer} qtyDelta the positive/negative change in quantity
    */
    updateLineItemQtyInShoppingCartUI: function(id, qtyDelta){
        var lineItemNode = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id=\"" + id + "\"]"),
            quantityInput = lineItemNode.one("input.rn_LineItemQuantity"),
            currentQty = parseInt(quantityInput.get("value")),
            newQty = currentQty + qtyDelta;
        quantityInput.set("value", newQty);
        quantityInput.setAttribute("data-prev", newQty);
    },

    /**
    * Due to line items being added to the UI opportunistically before they've been added to the session data, 
    * we need a way to update the temporary ID to the actual ID once we have it. This routine does that.
    * @param {integer} tempID the temporary ID of the line item
    * @param {integer} actualID the actual ID of the line item
    */
    updateLineItemTempIDInUI: function(tempID, actualID){
        var lineItemNode = this.Y.one(this.baseSelector + " .rn_ShoppingCartLineItemContainer div.rn_LineItem[data-id=\"" + tempID + "\"]");
        lineItemNode.setAttribute("data-id", actualID);
    },

    /**
    * Gets a line item from the client-side line item cache.
    * @param {integer} id the ID of the line item in the cache
    */
    getLineItemFromCache: function(id){
        if(this.lineItemIndex[id] === undefined) throw "Line item with ID = " + id + " not in cache.";
        return this.lineItemIndex[id];
    },

    /**
    * Adds a line item to the client-side line item cache.
    * @param {object} lineItem the line item to add to the cache
    */
    addLineItemToCache: function(lineItem){
        if(this.lineItemIndex[lineItem.id] !== undefined) throw "Line item with ID = " + lineItem.id + " already in cache.";
        // Update index
        this.lineItemIndex[lineItem.id] = lineItem;
        // Update cache
        this.data.js.lineItems.push(lineItem);
    },

    /**
    * Removes a line item from the client-side line item cache.
    * @param {integer} id the ID of the line item to remove from the cache
    */
    removeLineItemFromCache: function(id){
        if(this.lineItemIndex[id] === undefined) throw "Line item with ID = " + id + " not in index.";
        // Update index
        delete this.lineItemIndex[id];
        // Update cache
        var indexToDelete = null;
        for(var i = 0; i < this.data.js.lineItems.length; i++){
            if(this.data.js.lineItems[i].id === id){
                indexToDelete = i;
                break;
            }
        }
        if(indexToDelete === null) throw "Line item with ID = " + id + " not in cache.";
        this.data.js.lineItems.splice(indexToDelete, 1);
    },

    /**
    * Updates the quantity of a line item in the client-side line item cache.
    * @param {integer} id the ID of the line item to update the quantity of
    * @param {integer} deltaQty the positive/negative change in quantity of the line item
    */
    updateLineItemQtyInCache: function(id, deltaQty){
        if(this.lineItemIndex[id] === undefined) throw "Line item with ID = " + id + " not in cache.";
        this.lineItemIndex[id].quantity += deltaQty;
    },

    /**
    * Updates the custom data for a line item in the client-side line item cache.
    * @param {integer} id the ID of the line item to update the quantity of
    * @param {object} updatedCustomData the object containing the updated custom data values
    */
    updateLineItemCustomDataInCache: function(id, updatedCustomData){
        if(this.lineItemIndex[id] === undefined) throw "Line item with ID = " + id + " not in cache.";
        this.Y.each(updatedCustomData, function(value, key){
            if(this.lineItemIndex[id].customData[key] === undefined) throw "Invaid custom data key: " + key;
            this.lineItemIndex[id].customData[key] = value;
        }, this, false);
    },

    /**
    * Displays an AJAX error in a RightNow.Dialog.
    * @param {object} response the AJAX response that reflects an error state
    */
    displayAJAXError: function(msg){
        RightNow.UI.Dialog.messageDialog(msg, {icon : "WARN"});
    },

    /**
     * Makes an AJAX request to retrieve all line items from the server.
     */
    getLineItemsAJAX: function(){
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.getLineItemsAJAX, eventObj.data, {
            successHandler: this.getLineItemsAJAXCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `getLineItemsAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from getLineItemsAJAX
     */
    getLineItemsAJAXCallback: function(response, originalEventObj){
        if(response.status === "success"){
            this.data.js.lineItems = response.lineItems;
            this.initializeWidget();
        }else{
            this.displayAJAXError("Error encountered while retrieving line items from server. Please refresh page.");
        }
    },

    /**
     * Makes an AJAX request to initiate a checkout of the items currently in the shopping cart's session data.
     */
    initiateCheckoutAJAX: function(){
    	
    	
		//need to wait until all update requests are done.  this is not a perfect solution but better
		//than 
    	setTimeout(function(args){ 
	        var eventObj = new RightNow.Event.EventObject(args, {data:{
	            w_id: args.data.info.w_id
	        }});
		    RightNow.Ajax.makeRequest(args.data.attrs.initiateCheckoutAJAX, eventObj.data, {
		        successHandler: args.initiateCheckoutAJAXCallback,
		        scope:          args,
		        data:           eventObj,
		        json:           true
		    });
    	}, 2000, this); 
        
    },

    /**
     * Handles the AJAX response for `initiateCheckoutAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from initiateCheckoutAJAX
     */
    initiateCheckoutAJAXCallback: function(response, originalEventObj){
        if(response.status === "success"){
            this.doCheckoutRedirect(response.data);
        }else{
             if(response.status === "success"){
            	this.doCheckoutRedirect(response.data);
	        }else{
	        	if(response.msg == ""){
	        		this.displayAJAXError("Error encountered while preparing for check out. Please try again later.");
	        	}else{
	            	this.displayAJAXError(response.msg)	;
	            }
	        }
        }

        // Re-enable checkout button
        this.Y.one(this.baseSelector + " button.rn_ShoppingCartCheckoutButton").set("disabled", false);
    },

    /**
     * Makes an AJAX request to remove a line item from the shopping cart's session data.
     * @param {intger} id the ID of the line item to remove from the shopping cart's session data
     */
    removeLineItemFromShoppingCartSessionDataAJAX: function(id){
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            lineItemID: id
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.removeLineItemFromShoppingCartSessionDataAJAX, eventObj.data, {
            successHandler: this.removeLineItemFromShoppingCartSessionDataAJAXCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `removeLineItemFromShoppingCartSessionDataAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from removeLineItemFromShoppingCartSessionDataAJAX
     */
    removeLineItemFromShoppingCartSessionDataAJAXCallback: function(response, originalEventObj){
        var removedLineItemID = originalEventObj.data.lineItemID;
        if(response.status === "success"){
            // Remove line item from line item cache
            this.removeLineItemFromCache(removedLineItemID);
        }else{
            this.displayAJAXError("Error encountered while trying to remove a line item from the shopping cart. Please try again later.");
            // Since we opportunistically removed the line item from the UI, we need to re-render it from the cache now and revert total
            var lineItem = this.getLineItemFromCache(removedLineItemID);
            this.renderLineItem(lineItem);
            this.updateTotal(lineItem.merch.price * lineItem.quantity, lineItem.quantity);
        }
    },

    /**
     * Makes an AJAX request to add one or more line items to the shopping cart's session data.
     * @param {array} lineItems the array of line items to add to the shopping cart's session data
     */
    addLineItemsToShoppingCartSessionDataAJAX: function(lineItems){
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            lineItems: JSON.stringify(lineItems)
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.addLineItemsToShoppingCartSessionDataAJAX, eventObj.data, {
            successHandler: this.addLineItemsToShoppingCartSessionDataAJAXCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `addLineItemsToShoppingCartSessionDataAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from addLineItemsToShoppingCartSessionDataAJAX
     */
    addLineItemsToShoppingCartSessionDataAJAXCallback: function(response, originalEventObj){
        if(response.status === "success"){
            var origLineItemsInRequest = JSON.parse(originalEventObj.data.lineItems);
            this.Y.each(response.data, function(lineItem, key){
                this.addLineItemToCache(lineItem); 
                this.updateLineItemTempIDInUI(origLineItemsInRequest[key].id, lineItem.id);
            }, this);
        }else{
            this.displayAJAXError("Error encountered while trying to add one or more line items to the shopping cart. Please try again later.");
            // Since we opportunistically added the line items to the UI, we need to remove them now and revert total
            this.Y.each(originalEventObj.data.lineItems, function(lineItem){
                this.removeLineItemFromShoppingCartUI(lineItem.id);
                this.updateTotal(lineItem.merch.price * -lineItem.quantity, -lineItem.quantity);
            }, this);
        }
    },

    /**
     * Makes an AJAX request to update a line item's quantity in the shopping cart session data.
     * @param {integer} id the ID of the line item to update the quantity of
     * @param {integer} qtyDelta the positive/negative change in quantity of the line item
     */
    updateLineItemQtyInShoppingCartSessionDataAJAX: function(id, qtyDelta){
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            lineItemID: id,
            qtyDelta: qtyDelta
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.updateLineItemQtyInShoppingCartSessionDataAJAX, eventObj.data, {
            successHandler: this.updateLineItemQtyInShoppingCartSessionDataAJAXCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `updateLineItemQtyInShoppingCartSessionDataAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from updateLineItemQtyInShoppingCartSessionDataAJAX
     */
    updateLineItemQtyInShoppingCartSessionDataAJAXCallback: function(response, originalEventObj){
        if(response.status === "success"){
            // Update the quantity of the line item in the line item cache
            this.updateLineItemQtyInCache(originalEventObj.data.lineItemID, originalEventObj.data.qtyDelta);
        }else{
            this.displayAJAXError("Error encountered while trying to update the quantity of a line item in the shopping cart. Please try again later.");
            // Since we opportunistically updated the quantity of the line item in the UI, we need to revert the update to the UI and revert total
            var lineItem = this.getLineItemFromCache(originalEventObj.data.lineItemID);
            this.updateLineItemQtyInShoppingCartUI(lineItem.id, -originalEventObj.data.qtyDelta);
            this.updateTotal(lineItem.merch.price * -originalEventObj.data.qtyDelta, 
                -originalEventObj.data.qtyDelta);
        }
    },

    /**
     * Makes an AJAX request to update a line item's customData in the shopping cart session data.
     * @param {integer} id the ID of the line item to update the customData of
     * @param {object} updatedCustomData the object containing the updated customData values
     * @param {function} onSuccess the callback to invoke if the update request succeeds
     * @param {function} onFailure the callback to invoke if the update request fails
     */
    updateLineItemCustomDataInShoppingCartSessionDataAJAX: function(id, updatedCustomData, onSuccess, onFailure){
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            lineItemID: id,
            updatedCustomData: JSON.stringify(updatedCustomData)
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.updateLineItemCustomDataInShoppingCartSessionDataAJAX, eventObj.data, {
            successHandler: function(response, originalEventObj){ 
                this.updateLineItemCustomDataInShoppingCartSessionDataAJAXCallback(response, originalEventObj, onSuccess, onFailure); },
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `updateLineItemCustomDataInShoppingCartSessionDataAJAX`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from updateLineItemCustomDataInShoppingCartSessionDataAJAX
     * @param {function} onSuccess the callback to invoke if the update request succeeds
     * @param {function} onFailure the callback to invoke if the update request fails
     */
    updateLineItemCustomDataInShoppingCartSessionDataAJAXCallback: function(response, originalEventObj, onSuccess, onFailure){
        if(response.status === "success"){
            this.updateLineItemCustomDataInCache(originalEventObj.data.lineItemID, response.data);
            onSuccess.call(this);
        }else{
            this.displayAJAXError("Error encountered while trying to update the custom data of a line item in the shopping cart. Please try again later.");
            onFailure.call(this);
        }
    }
});