RightNow.namespace('Custom.Widgets.input.EmailPrefSelectionInput');
Custom.Widgets.input.EmailPrefSelectionInput = RightNow.Widgets.SelectionInput.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.SelectionInput#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            RightNow.Event.on("evt_SubscribeToEmailCheckboxChanged", this.onEmailCheckboxChanged, this);
        }

        /**
         * Overridable methods from SelectionInput:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
        // onValidate: function(type, args)
        // displayError: function(errors, errorLocation)
        // toggleErrorIndicator: function(showOrHide)
        // blurValidate: function()
        // countryChanged: function()
        // successHandler: function(response)
        // onProvinceResponse: function(type, args)
    },

    /**
     * Handles when the checkbox controlling the hidden Contact.CustomFields.c.preferences field has changed. That field is
     * a custom menu, but we want to display it as a checkbox.
     */
    onEmailCheckboxChanged: function(evt, args) {
        if(args[0]){
            if(args[0].checked === true){
                this.input.set("value", 14); // Email
            }else{
                this.input.set("value", 16); // No mail
            }
        }
    }
});