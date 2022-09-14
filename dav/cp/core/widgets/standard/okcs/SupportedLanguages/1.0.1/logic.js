 /* Originating Release: February 2019 */
RightNow.Widgets.SupportedLanguages = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._link = this.Y.one(this.baseSelector + "_Link");
            this._container = this.Y.one(this.baseSelector + "_Container");
            
            this._link.on("click", this._onToggleLanguageContainer, this);
            
            this.searchSource().on('collect', this.onCollect, this)
                .on('response', this._hideLanguageSelection, this);
        }
    },

    onCollect: function () {
        var selections = [];
        this.Y.all('input:checked').each(function(checkbox) {
            selections.push(checkbox.get('value'));
        });

        return new RightNow.Event.EventObject(this, {
            data: {value: selections.toString(), key: 'loc', type: 'loc'}
        });
    },
    
    /**
    * function to show/hide the languages
    *
    */
    _onToggleLanguageContainer: function()
    {
        this._container.toggleClass('rn_Hidden');
    },
    /**
    * function to hide the languages on search response 
    *
    */
    _hideLanguageSelection: function()
    {
        this._container.addClass('rn_Hidden');
    }
});
