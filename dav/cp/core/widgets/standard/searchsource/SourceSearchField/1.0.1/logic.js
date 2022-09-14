 /* Originating Release: February 2019 */
RightNow.Widgets.SourceSearchField = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.input = this.Y.one(this.baseSelector + '_SearchInput');

            if (this.data.attrs.initial_focus) {
                this.input.focus();
            }

            this.searchSource()
                .on('collect', this.onCollect, this)
                .on('updateFilters', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);
        }
    },

    /**
     * Returns a RightNow.Event.EventObject with the filter value filled in
     * @return {object} RightNow.Event.EventObject with the filter value filled in
     */
    onCollect: function () {
        return new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, { value: this.input.get('value') })
        });
    },

    /**
     * Updates the filter value.
     * @param  {string} e  Event name
     * @param  {object} eo RightNow.Event.EventObject
     */
    onFilterUpdate: function(e, eo) {
        this.Y.Array.each(eo[0].data, function(filter) {
            if(filter.key === this.data.js.filter.key) {
                this.input.set('value', filter.value);
            }
        }, this);
    },

    /**
     * Resets the input field to its original value.
     */
    onReset: function () {
        this.input.set('value', this.data.js.prefill);
    }
});
