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

        this.searchSource().fire('collect');

        if(this.searchSource().multiple) {
            this.filters = this.searchSource().sources[0].filters;
        }
        else {
            this.filters = this.searchSource().filters;
        }
        if(this.filters.query && this.Y.Lang.trim(this.filters.query.value) !== '') {
            if (this.filters.query.key === "kw" && this.Y.Lang.trim(this.filters.query.value) === "*") {
                this.filters.direction = this.filters.direction || {};
                this.filters.direction.value = 0;
                // Wildcard(*) search returns result in random order, so force it to use sorting on 'UpdatedTime' column. Additional note: use 1 for 'UpdatedTime' and 2 for 'CreatedTime'.
                this.filters.sort = this.filters.sort || {};
                this.filters.sort.value = 1;
            }

            this.disableClickListener();
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
               data: this.searchOptions()
           }));
        }
        else {
            RightNow.UI.displayBanner(this.data.attrs.label_enter_search_keyword, {
                type: 'WARNING',
                focusElement: this.searchButton
            });
        }
    },

    /**
     * Enables the click listener.
     */
    enableClickListener: function() {
        this.searchInProgress = false;
        this.searchButton.set('disabled', false).on('click', this.search, this);
    },

    /**
     * Disables the click listener.
     */
    disableClickListener: function() {
        this.searchInProgress = true;
        this.searchButton.set('disabled', true).detach('click', this.search, this);
    }
});
