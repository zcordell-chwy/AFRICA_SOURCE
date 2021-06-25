RightNow.namespace('Custom.Widgets.eventus.SingleGiftItem');
Custom.Widgets.eventus.SingleGiftItem = RightNow.Widgets.extend({     /**
     * Widget constructor.
     */
    constructor: function() {

        var submitButton = this.Y.one(".rn_GiftPopupGalleryItemPopupAddToCartForm button[value=\"submit\"]");
        submitButton.on("click", function(evt){ this.handleGiftPopupSubmitButtonClick(evt); }, this);
            
        var allCheckbox = this.Y.one(".rn_GiftPopupGalleryItemPopupSponsoredChildRecipient[value='all']");
        if(allCheckbox !== null){
            allCheckbox.on("click", this.handleAllCheckboxToggle, this);
        }

    },
    
    /**
    * Handles selecting/de-selecting all child checkboxes when all checkbox is checked or unchecked.
    * @param {object} evt the DOM event object for the onchange event
    */
     handleAllCheckboxToggle: function(evt){
        var sponsoredChildCheckboxes = this.Y.all(".rn_GiftPopupGalleryItemPopupSponsoredChildRecipient"),
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
     handleGiftPopupSubmitButtonClick: function(evt){
        
        //get the item id from the dom not the passed in content.
        var itemID = parseInt(this.Y.one(".rn_ItemGalleryItemPopup").getAttribute("data-id"));
        //var item = this.itemsByID[itemID],
        var addToCartFormData = {
                itemID: itemID,
                itemTitle: this.data.js.gifts[0].Title,
                quantity: 0,
                price: parseFloat(this.data.js.gifts[0].Amount),
                recipients: []
            },
            inputs = this.Y.all(".rn_GiftPopupGalleryItemPopupAddToCartForm input");

        this.Y.each(inputs, function(input){
            if(input.hasClass("rn_GiftPopupGalleryItemPopupSponsoredChildRecipient")){
                // Checkbox indicating sponsored child recipient of gift
                if(input.get("checked") === true){
                    var childIDString = input.get("value");
                    if(childIDString === "all") return;
                    var eligibleChild = this.getEligibleChildByID(this.data.js.eligibleChildren, childIDString);
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
        }
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
    * Convenience method for getting a child object from the eligible children array subobject of an item.
    * @param {object} item the gallery item data object
    * @param {integer} childID the ID of the child in the eligible children array
    */
     getEligibleChildByID: function(children, childID){
        for(var i = 0; i < children.length; i++){
            // Loose equality comparison here to handle special "NEEDY_CHILD" ID
            if(children[i].id == childID){
                return children[i];
            }
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
     * Makes an AJAX request for `default_ajax_endpoint`.
     */
    getDefault_ajax_endpoint: function() {
        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            // Parameters to send
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.default_ajax_endpoint, eventObj.data, {
            successHandler: this.default_ajax_endpointCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

     /**
    * Handles displaying user input errors on the add-to-cart form.
    * @param {array} formErrors the array of errors to display
    */
      displayAddToCartFormErrors: function(formErrors){
        var recipientFieldsetErrorContainer = this.Y.one(".rn_GiftPopupGalleryItemPopupSponsoredChildrenFieldsetErrorContainer"),
            inputErrorContainers = this.Y.all("div.rn_GiftPopupGalleryItemPopupInputContainer div.rn_GiftPopupGalleryItemPopupInputErrorContainer");

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
                var errorContainer = this.Y.one("div.rn_GiftPopupGalleryItemPopupInputContainer[data-field=\"" + error.field + "\"] div.rn_GiftPopupGalleryItemPopupInputErrorContainer");
                errorContainer.append(errorMsg);
            }
        }, this);
    },
    /**
     * Handles the AJAX response for `default_ajax_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
     */
    default_ajax_endpointCallback: function(response, originalEventObj) {
        // Handle response
    },    /**
     * Renders the `view.ejs` JavaScript template.
     */
    renderView: function() {
        // JS view:
        var content = new EJS({text: this.getStatic().templates.view}).render({
            // Variables to pass to the view
            // display: this.data.attrs.display
        });
    }
});