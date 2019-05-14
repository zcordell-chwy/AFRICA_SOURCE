 /* Originating Release: February 2019 */
RightNow.Widgets.LoadingIndicator = RightNow.Widgets.extend({
    constructor: function() {
        this._loadingDiv = this.Y.one('#' + this.data.attrs.dom_id_loading_icon);
        RightNow.Event.subscribe("evt_pageLoading", this._showPageLoading, this);
        RightNow.Event.subscribe("evt_pageLoaded", this._hidePageLoading, this);
    },

    /**
    * This function adds the class rn_OkcsLoading to the dom dom_id_loading_icon.
    */	
    _showPageLoading: function(){
        if (this._loadingDiv)
            this._loadingDiv.addClass('rn_OkcsLoading');
    },

    /**
    * This function removes the class rn_OkcsLoading from the dom dom_id_loading_icon.
    */
    _hidePageLoading: function(){
        if (this._loadingDiv) 
            this._loadingDiv.removeClass('rn_OkcsLoading');
    }
});