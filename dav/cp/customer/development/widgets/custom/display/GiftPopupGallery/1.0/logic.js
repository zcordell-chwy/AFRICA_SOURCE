RightNow.namespace('Custom.Widgets.display.GiftPopupGallery');
Custom.Widgets.display.GiftPopupGallery = Custom.Widgets.display.ItemPopupGallery.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides Custom.Widgets.display.ItemPopupGallery#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();
        },

        /**
        * Hook method, to be overridden by inheriting class, allowing for event registration on popup dialog content.
        * @param {object} itemPopupContent the YUI node for the outermost item popup dialog content container 
        * @param {object} itemPopupDialog the YUI panel object representing the dialog
        */
        onItemPopupDialogCreated: function(itemPopupContent, itemPopupDialog){
            // Skip dialog event setup if user is not logged in, as we'll only be showing them link to login before letting them add gift to cart
            if(this.data.js.isLoggedIn){
                // Register event handler for gift popup form buttons
                var submitButton = this.Y.one(this.baseSelector + " .rn_GiftPopupGalleryItemPopupAddToCartForm button[value=\"submit\"]"),
                    cancelButton = this.Y.one(this.baseSelector + " .rn_GiftPopupGalleryItemPopupAddToCartForm button[value=\"cancel\"]");
                submitButton.on("click", function(evt){ this.handleGiftPopupSubmitButtonClick(evt, itemPopupContent, itemPopupDialog); }, this);
                cancelButton.on("click", function(evt){ this.handleGiftPopupCancelButtonClick(evt, itemPopupContent, itemPopupDialog); }, this);

                // Register event handler for All sponsored children checkbox if present
                var allCheckbox = this.Y.one(this.baseSelector + " input.rn_GiftPopupGalleryItemPopupSponsoredChildRecipient[value='all']");
                if(allCheckbox !== null){
                    allCheckbox.on("click", this.handleAllCheckboxToggle, this);
                }
            }
        }
    },

    /**
    * Handles selecting/de-selecting all child checkboxes when all checkbox is checked or unchecked.
    * @param {object} evt the DOM event object for the onchange event
    */
    handleAllCheckboxToggle: function(evt){
        var sponsoredChildCheckboxes = this.Y.all(this.baseSelector + " input.rn_GiftPopupGalleryItemPopupSponsoredChildRecipient[value!='all']"),
            allCheckbox = evt.currentTarget,
            checkOrUncheck = null;

        if(allCheckbox.get("checked") === true){
            checkOrUncheck = "check";
        }else{
            checkOrUncheck = "uncheck";
        }

        // Check/uncheck all sponsored child checkboxes
        this.Y.each(sponsoredChildCheckboxes, function(checkbox){
            if(checkOrUncheck === "check"){
                checkbox.set("checked", true);
            }else{
                checkbox.set("checked", false);
            }
        }, this);
    },

    /**
    * Handles when the submit button in the add-to-cart form of the gift popup dialog is clicked.
    * @param {object} evt the DOM event object for the click event
    * @param {object} popupContent the YUI node object for the popup content
    * @param {object} dialog the YUI panel object representing the popup dialog
    */
    handleGiftPopupSubmitButtonClick: function(evt, popupContent, dialog){
        var itemID = parseInt(popupContent.getAttribute("data-id")),
            item = this.itemsByID[itemID],
            addToCartFormData = {
                itemID: itemID,
                itemTitle: item.data.title,
                quantity: 0,
                price: parseFloat(item.data.amount),
                recipients: []
            },
            inputs = this.Y.all(this.baseSelector + " .rn_GiftPopupGalleryItemPopupAddToCartForm input");

        this.Y.each(inputs, function(input){
            if(input.hasClass("rn_GiftPopupGalleryItemPopupSponsoredChildRecipient")){
                // Checkbox indicating sponsored child recipient of gift
                if(input.get("checked") === true){
                    var childIDString = input.get("value");
                    if(childIDString === "all") return;
                    var eligibleChild = this.getEligibleChildFromItemByID(item, childIDString);
                    addToCartFormData.recipients.push(eligibleChild);
                }
            }else if(input.hasClass("rn_GiftPopupGalleryItemPopupQuantity")){
                // Number field indicating quantity of gift
                addToCartFormData.quantity = parseInt(input.get("value"));
            }
        }, this);

        console.log(addToCartFormData);

        var formErrors = this.validateAddToCartFormData(addToCartFormData);
        this.displayAddToCartFormErrors(formErrors);
        if(formErrors.length === 0){
            this.submitAddToCartForm(addToCartFormData);
            // Destroy dialog
            dialog.destroy(true);
        }
    },

    /**
    * Handles validating the add-to-cart form data. If data is invalid, returns an array of one or more errors. If
    * data is valid, returns an empty array.
    * @param {object} addToCartFormData the object containing the add-to-cart form data
    */
    validateAddToCartFormData: function(addToCartFormData){
        var errors = [];
        
        // Validate that there is at least 1 recipient
        if(addToCartFormData.recipients.length === 0) errors.push({field: "recipient", msg: "You must choose at least 1 recipient for this gift."});

        // Validate quantity
        if(isNaN(addToCartFormData.quantity)) errors.push({field: "quantity", msg: "Quantity must be an integer."});
        else if(addToCartFormData.quantity < 1) errors.push({field: "quantity", msg: "Quantity must be greater than 0."});

        return errors;
    },

    /**
    * Handles submitting the add-to-cart form data, which results in firing "add line item" events for the gift shopping cart
    * for each recipient of the item.
    * @param {object} addToCartFormData the object containing the add-to-cart form data
    */
    submitAddToCartForm: function(addToCartFormData){
        // Create a new line item in the shopping cart for each recipient
        var lineItems = [];
        this.Y.each(addToCartFormData.recipients, function(recipient){
            lineItems.push(
                {
                    merch: {
                        id: addToCartFormData.itemID,
                        title: addToCartFormData.itemTitle,
                        price: addToCartFormData.price
                    },
                    quantity: addToCartFormData.quantity,
                    customData: {
                        childID: parseInt(recipient.id),
                        childName: recipient.name,
                        childImgURL: recipient.imgURL
                    }
                }
            );
        }, this);

        var eventObj = {
            shoppingCartID: "gift",
            lineItems: lineItems
        };
        RightNow.Event.fire("evt_addLineItemsToShoppingCartRequest", eventObj);
    },

    /**
    * Handles displaying user input errors on the add-to-cart form.
    * @param {array} formErrors the array of errors to display
    */
    displayAddToCartFormErrors: function(formErrors){
        var recipientFieldsetErrorContainer = this.Y.one(this.baseSelector + " .rn_GiftPopupGalleryItemPopupSponsoredChildrenFieldsetErrorContainer"),
            inputErrorContainers = this.Y.all(this.baseSelector + " div.rn_GiftPopupGalleryItemPopupInputContainer div.rn_GiftPopupGalleryItemPopupInputErrorContainer");

        // Clear previous errors
        recipientFieldsetErrorContainer.setHTML("");
        this.Y.each(inputErrorContainers, function(inputErrorContainer){
            inputErrorContainer.setHTML("");
        });

        // Build error messages
        this.Y.each(formErrors, function(error){
            var errorMsg = this.Y.Node.create("<p class=\"rn_GiftPopupGalleryItemPopupErrorMsg\">" + error.msg + "</p>");
            if(error.field === "recipient"){
                recipientFieldsetErrorContainer.append(errorMsg);
            }else{
                var errorContainer = this.Y.one(this.baseSelector + 
                    " div.rn_GiftPopupGalleryItemPopupInputContainer[data-field=\"" + error.field + "\"] div.rn_GiftPopupGalleryItemPopupInputErrorContainer");
                errorContainer.append(errorMsg);
            }
        }, this);
    },

    /**
    * Handles when the cancel button in the add-to-cart form of the gift popup dialog is clicked.
    * @param {object} evt the DOM event object for the click event
    * @param {object} popupContent the YUI node object for the popup content
    * @param {object} dialog the YUI panel object representing the popup dialog
    */
    handleGiftPopupCancelButtonClick: function(evt, popupContent, dialog){
        // Destroy dialog
        dialog.destroy(true);
    },

    /**
    * Convenience method for getting a child object from the eligible children array subobject of an item.
    * @param {object} item the gallery item data object
    * @param {integer} childID the ID of the child in the eligible children array
    */
    getEligibleChildFromItemByID: function(item, childID){
        for(var i = 0; i < item.data.eligibleChildren.length; i++){
            // Loose equality comparison here to handle special "NEEDY_CHILD" ID
            if(item.data.eligibleChildren[i].id == childID){
                return item.data.eligibleChildren[i];
            }
        }
    }
});