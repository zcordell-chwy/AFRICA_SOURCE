RightNow.namespace('Custom.Widgets.display.DonationFundPopupGallery');
Custom.Widgets.display.DonationFundPopupGallery = Custom.Widgets.display.ItemPopupGallery.extend({ 
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
            // Register event handler for donation fund popup form buttons
            var submitButton = this.Y.one(this.baseSelector + " .rn_DonationFundPopupGalleryItemPopupAddToCartForm button[value=\"submit\"]"),
                cancelButton = this.Y.one(this.baseSelector + " .rn_DonationFundPopupGalleryItemPopupAddToCartForm button[value=\"cancel\"]");
            submitButton.on("click", function(evt){ this.handleDonationFundPopupSubmitButtonClick(evt, itemPopupContent, itemPopupDialog); }, this);
            cancelButton.on("click", function(evt){ this.handleDonationFundPopupCancelButtonClick(evt, itemPopupContent, itemPopupDialog); }, this);
        }
    },

    /**
    * Handles when the submit button in the add-to-cart form of the donation fund popup dialog is clicked.
    * @param {object} evt the DOM event object for the click event
    * @param {object} popupContent the YUI node object for the popup content
    * @param {object} dialog the YUI panel object representing the popup dialog
    */
    handleDonationFundPopupSubmitButtonClick: function(evt, popupContent, dialog){
        var itemID = parseInt(popupContent.getAttribute("data-id")),
            item = this.itemsByID[itemID],
            inputContainers = this.Y.all(this.baseSelector + " div.rn_DonationFundPopupGalleryItemPopupInputContainer");
        if(item.data.type === "fund"){
            addToCartFormData = {
                itemID: itemID,
                itemTitle: item.data.title,
                amountOneTime: 0,
                amountMonthly: 0,
                donationFundID: item.data.donationFundID,
                donationAppealID: item.data.donationAppealID,
                type: item.data.type
            }
        }else{ // missionary
            addToCartFormData = {
                itemID: itemID,
                itemTitle: null,
                amountOneTime: 0,
                amountMonthly: 0,
                donationFundID: null,
                donationAppealID: null,
                type: "missionary"
            }
        }

        this.Y.each(inputContainers, function(inputContainer){
            // Skip last input container with submit/cancel buttons
            if(inputContainer.getAttribute("data-field") === "") return;
            var input = inputContainer.one("input");
            if(input == null) input = inputContainer.one("select");
            if(inputContainer.getAttribute("data-field") === "amountOneTime"){
                addToCartFormData.amountOneTime = input.get("value");
            }else if(inputContainer.getAttribute("data-field") === "amountMonthly"){
                addToCartFormData.amountMonthly = input.get("value");
            }else if(inputContainer.getAttribute("data-field") === "missionaryList"){
                var selectedOption = input.one("option:checked"),
                    selectedMissionaryIndex = parseInt(selectedOption.get("value")),
                    selectedMissionaryName = selectedOption.get("text");
                addToCartFormData.donationFundID = item.data.missionaries[selectedMissionaryIndex].donationFundID;
                addToCartFormData.donationAppealID = item.data.missionaries[selectedMissionaryIndex].donationAppealID;
                addToCartFormData.itemTitle = selectedMissionaryName;
            }else{
                throw "unknown input on add-to-cart form";
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

        // Validate that amount one-time and amount monthly are positive numbers
        if(isNaN(addToCartFormData.amountOneTime) || addToCartFormData.amountOneTime < 0) 
            errors.push({field: "amountOneTime", msg: "Invalid amount."});
        if(isNaN(addToCartFormData.amountMonthly) || addToCartFormData.amountMonthly < 0) 
            errors.push({field: "amountMonthly", msg: "Invalid amount."});
        // If fund item, validate that either one-time amount or monthly amount are non-zero
        if(addToCartFormData.type === "fund" && addToCartFormData.amountOneTime == 0 && addToCartFormData.amountMonthly == 0) 
            errors.push({field: "amountMonthly", msg: "You must enter at least one positive amount."});
        // If missionary item, validate that one-time amount is non-zero
        if(addToCartFormData.type === "missionary" && addToCartFormData.amountOneTime == 0)
            errors.push({field: "amountOneTime", msg: "You must enter a positive amount."});
        // If no errors, change amounts to be floats
        if(errors.length === 0){
            addToCartFormData.amountOneTime = parseFloat(addToCartFormData.amountOneTime);
            addToCartFormData.amountMonthly = parseFloat(addToCartFormData.amountMonthly);
        }

        return errors;
    },

    /**
    * Handles submitting the add-to-cart form data, which results in firing an "add line item" event for the donation 
    * shopping cart.
    * @param {object} addToCartFormData the object containing the add-to-cart form data
    */
    submitAddToCartForm: function(addToCartFormData){
        // Create a new line item in the shopping cart for the donation
        var lineItems = [];
        lineItems.push(
                {
                    merch: {
                        id: addToCartFormData.itemID,
                        title: addToCartFormData.itemTitle,
                        price: addToCartFormData.amountOneTime + addToCartFormData.amountMonthly
                    },
                    quantity: 1,
                    customData: {
                        amountOneTime: addToCartFormData.amountOneTime,
                        amountMonthly: addToCartFormData.amountMonthly,
                        donationFundID: addToCartFormData.donationFundID,
                        donationAppealID: addToCartFormData.donationAppealID,
                        donationType: addToCartFormData.type
                    }
                }
            );

        var eventObj = {
            shoppingCartID: "donation",
            lineItems: lineItems
        };
        RightNow.Event.fire("evt_addLineItemsToShoppingCartRequest", eventObj);
    },

    /**
    * Handles displaying user input errors on the add-to-cart form.
    * @param {array} formErrors the array of errors to display
    */
    displayAddToCartFormErrors: function(formErrors){
        var inputErrorContainers = this.Y.all(this.baseSelector + " div.rn_DonationFundPopupGalleryItemPopupInputContainer div.rn_DonationFundPopupGalleryItemPopupInputErrorContainer");

        // Clear previous errors
        this.Y.each(inputErrorContainers, function(inputErrorContainer){
            inputErrorContainer.setHTML("");
        });

        // Build error messages
        this.Y.each(formErrors, function(error){
            var errorMsg = this.Y.Node.create("<p class=\"rn_DonationFundPopupGalleryItemPopupErrorMsg\">" + error.msg + "</p>");
            var errorContainer = this.Y.one(this.baseSelector + 
                " div.rn_DonationFundPopupGalleryItemPopupInputContainer[data-field=\"" + error.field + "\"] div.rn_DonationFundPopupGalleryItemPopupInputErrorContainer");
            errorContainer.append(errorMsg);
        }, this);
    },

    /**
    * Handles when the cancel button in the add-to-cart form of the donation popup dialog is clicked.
    * @param {object} evt the DOM event object for the click event
    * @param {object} popupContent the YUI node object for the popup content
    * @param {object} dialog the YUI panel object representing the popup dialog
    */
    handleDonationFundPopupCancelButtonClick: function(evt, popupContent, dialog){
        // Destroy dialog
        dialog.destroy(true);
    }
});