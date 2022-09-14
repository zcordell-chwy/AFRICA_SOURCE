RightNow.namespace('Custom.Widgets.reports.LetterFaqsMultiline');
Custom.Widgets.reports.LetterFaqsMultiline = RightNow.Widgets.Multiline.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.Multiline#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            // Setup accordion toggle functionality
            this.Y.all(this.baseSelector + " .accordion-title").on("click", this.toggleAccordion, this);
        }

        /**
         * Overridable methods from Multiline:
         *
         * Call `this.parent()` inside of function bodies
         * (with expected parameters) to call the parent
         * method being overridden.
         */
        // _setFilter: function()
        // _searchInProgress: function(evt, args)
        // _setLoading: function(loading)
        // _onReportChanged: function(type, args)
        // _displayDialogIfError: function(error)
        // _updateAriaAlert: function(text)
    },

    /**
     * Handles toggling an accordion tab.
     */
    toggleAccordion: function(evt) {
        var toggle = this.Y.one(evt._currentTarget),
            accord = toggle.ancestor(".accordion");

        if(accord.hasClass("open")){
            // Collapsing
            accord.removeClass("open");
        }else{
            // Expanding
            accord.addClass("open");
        }
    }
});