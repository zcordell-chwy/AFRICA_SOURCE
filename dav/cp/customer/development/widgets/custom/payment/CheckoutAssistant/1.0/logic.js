RightNow.namespace('Custom.Widgets.payment.CheckoutAssistant');
Custom.Widgets.payment.CheckoutAssistant = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
        this.paymentProcessAccordion = $(this.baseSelector + " #PaymentProcessAccordion");
        this.lockedTabs = [];
        this.tabID2Index = {};
        this.donorBillingInfoSubmitted = false;
        this.lastDonorBillingInfoFormConcatValueString = null;

        if(this.paymentProcessAccordion != null){
            var thisObj = this;
            // Build accordion control
            this.paymentProcessAccordion.accordion({
                heightStyle: "content",
                beforeActivate: function(evt, ui){
                    thisObj.handleTabActivate(evt, ui);
                }
            });
            // Build tab controls (back/continue buttons) for each tab of the accordion
            this.buildTabControls();
            // Lock the 'transaction summary' and 'payment info' tabs until the user has completed the steps to progress to
            // those tabs
            this.lockTab("trans_sum");
            this.lockTab("payment_info");
            // Show the accordion control now that we've finished building it
            this.paymentProcessAccordion.css("visibility", "visible");
        }

        // Register event handler for the 'evt_DonorBillingInfoSubmissionSucceeded' event, which signals to this widget that we should progress from the 
        // donor billing info tab to the transaction summary tab
        RightNow.Event.on("evt_DonorBillingInfoSubmissionSucceeded", function(evt){
            this.setActiveTab("trans_sum");
            // Re-enable donor billing info continue button but also set flag to prevent user from doing a double submission. Hitting the continue button
            // on subsequent tries should simply activate the trasction summary tab.
            $(this.baseSelector + " div.CheckoutAssistantTabControlsContainer[data-id=\"donor_billing_info\"]")
                        .find("button.CheckoutAssistantContinueButton").prop("disabled", false);
            this.donorBillingInfoSubmitted = true;
        }, this);
        // Register event handler for the 'evt_DonorBillingInfoSubmissionFailed' event, which signals to this widget to re-enable the continue button on the
        // donor billing info tab
        RightNow.Event.on("evt_DonorBillingInfoSubmissionFailed", function(evt){
            $(this.baseSelector + " div.CheckoutAssistantTabControlsContainer[data-id=\"donor_billing_info\"]")
                .find("button.CheckoutAssistantContinueButton").prop("disabled", false);
        }, this);
        // Register event handler for the 'evt_CustomFormSubmitted' event, looking for when the 'storedMethodForm' form is submitted, so that we can lock the
        // billing donor info & trans summary tabs & disable back button on payment info tab
        RightNow.Event.on("evt_CustomFormSubmitted", function(evt, args){
            if(args[0].formID == "storedMethodForm"){
                this.lockTab("donor_billing_info");
                this.lockTab("trans_sum");
                this.toggleTabControlButton("payment_info", "back", true);
            }
        }, this);
        // Register event handler for the 'evt_CustomFormValidationFailed' and 'evt_CustomFormSubmissionFailed' events, looking for when the 'storedMethodForm'
        // is submitted, so that we can unlock the billing donor info & trans summary tabs & re-enable the back button on the payment info tab
        RightNow.Event.on("evt_CustomFormValidationFailed", this.onPaymentFailure, this);
        RightNow.Event.on("evt_CustomFormSubmissionFailed", this.onPaymentFailure, this);

        // Setup handler for when subscribe to email checkbox on new contact form (displayed when contact is not logged in) is changed
        this.subscribeToEmailCheckbox = $(this.baseSelector + " #subscribeToEmailCheckbox");
        this.subscribeToEmailCheckbox.change(this.onSubscribeToEmailCheckboxChange);

        // Skip right to transaction summary tab if skip_confirm_donor_billing_tab is true
        if(this.data.js.skip_confirm_donor_billing_tab){
            this.setActiveTab("trans_sum");
        }
    },

    /**
     * Handles when the subscribe to email checkbox is changed. Toggles the hidden Contact.CustomFields.c.preferences. That field is
     * a custom menu, but we want to display it as a checkbox.
     */
    onSubscribeToEmailCheckboxChange: function(){
        if($(this).is(":checked")) {
            RightNow.Event.fire("evt_SubscribeToEmailCheckboxChanged", {checked: true});
        }else{
            RightNow.Event.fire("evt_SubscribeToEmailCheckboxChanged", {checked: false});
        }
    },

    /**
    * Handles when there is a payment failure after the 'storedMethodForm' is submitted. This is signaled by the 'evt_CustomFormValidationFailed'
    * and 'evt_CustomFormSubmissionFailed' events. When this happens, we need to unlock the donor billing info & trans summary tabs, and also 
    * re-enable the back button on the payment info tab.
    * @param {string} evt the name of the event
    * @param {array} args the array of event data associated with the event
    */
    onPaymentFailure: function(evt, args){
    	var form;
    	if(typeof(args[0].formID) == 'undefined'){
    		form = args[0].data.form;
    	}else if(typeof(args[0].data) == 'undefined'){
    		form = args[0].formID;
    	}
    	
    	
        if(form == "storedMethodForm"){
            this.unlockTab("donor_billing_info");
            this.unlockTab("trans_sum");
            this.toggleTabControlButton("payment_info", "back", false);
        }
    },

    /**
     * Builds the tab controls (back and continue button) for each tab and registers event handlers.
     */
    buildTabControls: function() {
        var tabs = this.paymentProcessAccordion.find("h3");

        var thisObj = this;
        $.each(tabs, function(index, tab){
            var tab = $(tab),
                tabID = tab.attr("data-id"),
                tabContentContainer = tab.next("div"),
                // Show back button if this is not the first tab in the accordion
                showBackButton = index !== 0 ? true : false,
                // Show continue button on all tabs except the payment information tab
                showContinueButton = tabID != "payment_info" && RightNow.Profile.isLoggedIn() ? true : false, 
                continueButtonLabel = thisObj.data.js.labels.continue;

            // Build tab control
            tabControlHTML = new EJS({text: thisObj.getStatic().templates.tabControl}).render({
                data: {
                    id: tabID,
                    showBackButton: showBackButton, 
                    backButtonLabel: thisObj.data.js.labels.back,
                    showContinueButton: showContinueButton,
                    continueButtonLabel: continueButtonLabel 
                }
            });

            // Register event handlers
            var tabControl = $(tabControlHTML),
                tabControlBackButton = tabControl.find("button.CheckoutAssistantBackButton"),
                tabControlContinueButton = tabControl.find("button.CheckoutAssistantContinueButton");

            tabControlBackButton.click(function(evt){
                thisObj.handleTabBackButtonClick(evt, tabID);
            });
            tabControlContinueButton.click(function(evt){
                thisObj.handleTabContinueButtonClick(evt, tabID);
            });

            // Add the tab control to the tab content container
            tabContentContainer.append(tabControl);

            // Update tab ID 2 index map
            thisObj.tabID2Index[tabID] = index;
        });
    },

    /**
    * Handles when a back button is clicked in an accordion tab. 
    * @param {object} evt the DOM event object for the click event 
    */
    handleTabBackButtonClick: function(evt, tabID){
        switch(tabID){
            case "trans_sum":
                this.setActiveTab("donor_billing_info");
            break;
            case "payment_info":
                this.setActiveTab("trans_sum");
            break;
        }
    },

    /**
    * Handles when a continue button is clicked in an accordion tab. 
    * @param {object} evt the DOM event object for the click event 
    */
    handleTabContinueButtonClick: function(evt, tabID){
        switch(tabID){
            case "donor_billing_info":
                // If donor billing info has been sumbitted and hasn't changed from the last time it was submitted, then progress to 
                // transaction summary tab, else manually submit the 'rn_CreateAccount1001' form. Upon successful 
                // response, the custom form submit widget on that form will fire an event that this widget listens for 
                // to know the response status and when or if to progress to the transaction summary tab.
                var donorBillingInfoFormConcatValueString = "";
                $("form#rn_CreateAccount1001 input[type!=\"submit\"], form#rn_CreateAccount1001 select").each(function(){ 
                    var input = $(this); 
                    donorBillingInfoFormConcatValueString += input.val(); 
                });
                if(this.data.js.skip_confirm_donor_billing_tab){
                    this.setActiveTab("trans_sum");
                }else if(this.donorBillingInfoSubmitted && this.lastDonorBillingInfoFormConcatValueString == donorBillingInfoFormConcatValueString){
                    this.setActiveTab("trans_sum");
                }else{
                    // Disable continue button on donor billing info tab while donor billing info is being submitted
                    $(this.baseSelector + " div.CheckoutAssistantTabControlsContainer[data-id=\"donor_billing_info\"]")
                        .find("button.CheckoutAssistantContinueButton").prop("disabled", true);

                    RightNow.Form.find("rn_CreateAccount1001")._onButtonClick(null);
                }
                this.lastDonorBillingInfoFormConcatValueString = donorBillingInfoFormConcatValueString;
            break;
            case "trans_sum":
                this.setActiveTab("payment_info");
            break;
        }
    },

    /**
    * Handles when a tab in the accordion is activated (expanded).
    * @param {object}
    */
    handleTabActivate: function(evt, ui){
        var activatedTabID = $(ui.newHeader[0]).attr("data-id");

        // If tab is locked, don't allow user to expand it
        if(this.lockedTabs.indexOf(activatedTabID) !== -1){
            // Tab is locked, cancel event
            evt.preventDefault();
        }
    },

    /**
    * Expands a particular tab.
    * @param {string} tabID the string ID of the tab to expand
    */
    setActiveTab: function(tabID){
        // If tab is locked, unlock it
        if(this.lockedTabs.indexOf(tabID) !== -1){
            this.unlockTab(tabID);
        }
        this.paymentProcessAccordion.accordion("option", "active", this.tabID2Index[tabID]);
    },

    /**
    * Locks a particular tab, preventing it from being expanded.
    * @param {string} tabID the string ID of the tab to lock.
    */
    lockTab: function(tabID){
        // Only process request if tab not already locked
        if(this.lockedTabs.indexOf(tabID) === -1){
            // Tab not already locked, process request

            // Add tab to lockedTabs array
            this.lockedTabs.push(tabID);

            // Add locked tab icon to tab status container and hide header icon
            var tab = this.paymentProcessAccordion.find("h3[data-id=\"" + tabID + "\"]"),
                tabStatus = tab.find("span.AccordionTabStatusIcon"),
                headerIcon = tab.find("span.ui-accordion-header-icon");

            tabStatus.html("<i class=\"fa fa-lock\"></i>");
            headerIcon.hide();
        }
    },

    /**
    * Unlocks a particular tab, allowing it to be expanded again.
    * @param {string} tabID the string ID of the tab to unlock.
    */
    unlockTab: function(tabID){
        // Only process request if tab is currently locked
        if(this.lockedTabs.indexOf(tabID) !== -1){
            // Tab is currently locked, process request

            // Remove from lockedTabs array
            this.lockedTabs = $.grep(this.lockedTabs, function(lockedTabID, index){
                return lockedTabID != tabID;
            });

            // Remove locked tab icon from tab status container and show header icon
            var tab = this.paymentProcessAccordion.find("h3[data-id=\"" + tabID + "\"]"),
                tabStatus = tab.find("span.AccordionTabStatusIcon"),
                headerIcon = tab.find("span.ui-accordion-header-icon");

            tabStatus.html("");
            headerIcon.show();
        }
    },

    /**
    * Enables/disables the back/continue tab control button on a particular tab.
    * @param {string} tabID the string ID of the tab to enable a button for
    * @param {string} button the string identifier of the tab control button ("back" or "continue")
    * @param {boolean} disable true, to disable the button, false, to enable the button (false is default)
    */
    toggleTabControlButton: function(tabID, button, disable){
        disable = disable == null ? false : disable;
        var tabControlContainer = this.paymentProcessAccordion.find("div.CheckoutAssistantTabControlsContainer[data-id=\"" + tabID + "\"]"),
            button = button == "back" ? 
                tabControlContainer.find("button.CheckoutAssistantBackButton") : 
                tabControlContainer.find("button.CheckoutAssistantContinueButton");
        if(disable){
            button.prop("disabled", true);
        }else{
            button.prop("disabled", false);
        }
    }
});