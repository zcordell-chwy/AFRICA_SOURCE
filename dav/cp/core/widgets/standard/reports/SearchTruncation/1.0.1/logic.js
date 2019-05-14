 /* Originating Release: February 2019 */
RightNow.Widgets.SearchTruncation = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function(){
            this.parent();
            
            var buttonElement = this.Y.one(this.baseSelector + "_Button");
            if(!buttonElement)
                return;
            buttonElement.on('click', this._onMoreClick, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onReportChanged, this);
        }
    },

    /**
     * Event handler on button click
     */
    _onMoreClick: function()
    {
        this.searchSource().fire("appendFilter", new RightNow.Event.EventObject(this, {filters: {
            no_truncate: 1,
            recordKeywordSearch: false
        }})).fire("search", new RightNow.Event.EventObject(this, {}));
    },

    /**
     * Event handler to display new results
     *
     * @param type string Event type
     * @param args object Arguments passed with event
     */
    _onReportChanged: function(type, args)
    {
        var newdata = args[0].data;
        var div = this.Y.one(this.baseSelector);
        if(div)
        {
            if(newdata.truncated)
                RightNow.UI.show(div);
            else
                RightNow.UI.hide(div);
        }
    }
});
