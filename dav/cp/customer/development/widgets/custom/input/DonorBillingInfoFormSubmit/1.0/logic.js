RightNow.namespace('Custom.Widgets.input.DonorBillingInfoFormSubmit');
Custom.Widgets.input.DonorBillingInfoFormSubmit = RightNow.Widgets.FormSubmit.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.FormSubmit#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();
        },

        /**
         * Overridable methods from FormSubmit:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
        // _onButtonClick: function(evt)
        // _fireSubmitRequest: function()
        // _onFormValidated: function()
        // _onFormValidationFail: function()
        // _formSubmitResponse: function(type, args)
        // _displayErrorDialog: function(message)
        // _onFormTokenUpdate: function(type, args)
        // _enableFormExpirationWatch: function()
        // _toggleLoadingIndicators: function(turnOn)
        // _toggleClickListener: function(enable)

        // On form validation fail, throw event notifying CheckoutAssistant of failure
        _onFormValidationFail: function(){
            RightNow.Event.fire("evt_DonorBillingInfoSubmissionFailed");
            this.parent();
        },

        // On a response, rather than redirecting, simply throw event notifying CheckoutAssistant widget whether the
        // request succeeded or failed
        _formSubmitResponse: function(type, args){
            var responseObject = args[0].data;

            if(
                !!responseObject && !responseObject.errors && 
                (responseObject.result.transaction || result.redirectOverride)
            ){
                RightNow.Event.fire("evt_DonorBillingInfoSubmissionSucceeded");

                // Still toggle click listener so that we can re-submit this form later
                this._toggleLoadingIndicators(false);
                this._toggleClickListener(true);
            }else{
                RightNow.Event.fire("evt_DonorBillingInfoSubmissionFailed");

                // Unsuccessful response, call parent method to handle it
                this.parent(type, args);
            }
        }
    }
});