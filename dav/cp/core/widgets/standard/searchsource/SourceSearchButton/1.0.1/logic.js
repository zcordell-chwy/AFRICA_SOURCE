 /* Originating Release: February 2019 */
RightNow.Widgets.SourceSearchButton = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.searchButton = this.Y.one(this.baseSelector + "_SubmitButton");

            if(this.searchButton) {
                this.enableClickListener();
                this.searchSource()
                    .setOptions(this.searchOptions())
                    .setOptions(this.data.js.sources)
                    .on('response', this.enableClickListener, this);
            }
        }
    },

    /**
     * Returns options to apply towards the search.
     * @return {object} Search options
     */
    searchOptions: function () {
        return this._searchOptions || (this._searchOptions = {
            new_page: this.data.attrs.search_results_url,
            target: this.data.attrs.target,
            history_source_id: this.data.attrs.history_source_id,
            limit: this.data.attrs.per_page
        });
    },

    /**
     * Searches when the button is clicked.
     * @param  {object} e Click event
     */
    search: function(e) {
        e.halt();

        if(this.searchInProgress) return;

        this.disableClickListener();

        this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
            data: this.searchOptions()
        }));
    },

    /**
     * Enables the click listener.
     */
    enableClickListener: function() {
        this.searchInProgress = false;
        this.searchButton.on('click', this.search, this);
    },

    /**
     * Disables the click listener.
     */
    disableClickListener: function() {
        this.searchInProgress = true;
        this.searchButton.detach('click', this.search, this);
    }
});
