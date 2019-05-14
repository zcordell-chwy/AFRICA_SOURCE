RightNow.namespace('Custom.Widgets.input.NotifyOnChangeTextInput');
Custom.Widgets.input.NotifyOnChangeTextInput = RightNow.Widgets.TextInput.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.TextInput#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();
        },

        /**
         * Overridable methods from TextInput:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
        // onValidate: function(type, args)
        // _displayError: function(errors, errorLocation)
        // _toggleErrorIndicator: function(showOrHide, fieldToHighlight, labelToHighlight)
        // _toggleFormSubmittingFlag: function(event)
        // _blurValidate: function(event, validateVerifyField)
        // _validateVerifyField: function(verifyField, errors)
        // _checkExistingAccount: function()
        // _massageValueForModificationCheck: function(value)
        // _onAccountExistsResponse: function(response, originalEventObject)
        // onProvinceChange: function(type, args)
        // _initializeMask: function()
        // _createMaskArray: function(mask)
        // _getSimpleMaskString: function()
        // _compareInputToMask: function(submitting)
        // _showMaskMessage: function(error)
        // _setMaskMessage: function(message)
        // _showMask: function()
        // _hideMaskMessage: function()
        // _onValidateFailure: function()

        // Overriding so that we can throw an event signalling the value in the field changed
        onValidate: function(type, args){
            RightNow.Event.fire("evt_FieldChangedNotification", {fieldName: this.data.attrs.name, fieldValue: this.input.get("value") });

            return this.parent(type, args);
        }
    },

    /**
     * Sample widget method.
     */
    methodName: function() {

    }
});