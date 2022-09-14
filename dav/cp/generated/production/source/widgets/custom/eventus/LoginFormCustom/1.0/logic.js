RightNow.namespace('Custom.Widgets.eventus.LoginFormCustom');
Custom.Widgets.eventus.LoginFormCustom = RightNow.Widgets.LoginForm.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.LoginForm#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            if(this.data.js.isLoggedIn){
            	RightNow.Url.navigate(this.data.js.redirectOverride);
            }
            this.parent();
            
        }

        
    },

    
});