RightNow.namespace('Custom.Widgets.input.AutoDefaultingPasswordInput');
Custom.Widgets.input.AutoDefaultingPasswordInput = RightNow.Widgets.PasswordInput.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.PasswordInput#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            RightNow.Event.on("evt_FieldChangedNotification", this.onFieldChangedNotification, this);

            this.data.js.password_length_limit = 20;
        }

        /**
         * Overridable methods from PasswordInput:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
        // validate: function(errors)
        // initEvents: function()
        // onValidate: function(type, args)
        // displayError: function(errors, errorLocation)
        // showOverlay: function(e, type)
        // show: function()
        // hide: function()
        // get: function(what)
        // blurValidation: function(e, type)
        // validationClasses: function()
        // validateInput: function(e)
        // validateValidation: function()
        // updatePasswordChecklist: function(name, action)
        // _getPasswordStats: function(password)
    },

    /**
     * Handles when the email field changes.
     */
    onFieldChangedNotification: function(evt, args) {
        var evtData = args[0];
        if(evtData.fieldName === "Contact.Emails.PRIMARY.Address"){
            // Truncate email if too long to be password
            var autogenPassword = evtData.fieldValue.substring(0,this.data.js.password_length_limit);
            this.input.set("value", autogenPassword);
            this.validation.set("value", autogenPassword);
        }
    }
});