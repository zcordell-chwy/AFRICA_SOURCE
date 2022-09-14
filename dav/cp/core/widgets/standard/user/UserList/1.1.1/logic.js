 /* Originating Release: February 2019 */
RightNow.Widgets.UserList = RightNow.Widgets.extend({
    constructor: function() {
        if(this.data.attrs.content_load_mode === 'asynchronous') {
            this.contentLoad();
        }
    },
    
    /**
     * Calls the method to load the content. 
     */
    contentLoad: function() {
        this.Y.one(this.baseSelector + ' .rn_LoadingIcon').removeClass('rn_Hidden');
        this.Y.one(this.baseSelector + ' .rn_ErrorMsg').addClass('rn_Hidden');
        this._makeRequest();
    },
    
    /**
     * Calls the method to make a request.
     */
    _makeRequest: function() {
        var eo = new RightNow.Event.EventObject(this, {data: {
            w_id:       this.data.info.w_id
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.content_load_ajax, eo.data, {
            data: eo,
            scope: this,
            json : true,
            successHandler: this._display,
            failureHandler: this._error
        });
    },
    
    /**
     * Callback on successful ajax request.
     */
    _display: function(response) {
        this.Y.one(this.baseSelector + ' .rn_LoadingIcon').addClass('rn_Hidden');
        this.Y.one(this.baseSelector + ' .rn_UsersView').setHTML(response.result);
    },
    
    /**
     * Displays error on ajax request failure.
     */
    _error: function() {
        this.Y.one(this.baseSelector + ' .rn_LoadingIcon').addClass('rn_Hidden');
        this.Y.one(this.baseSelector + ' .rn_ErrorMsg').setHTML(this.data.attrs.label_content_load_error).removeClass('rn_Hidden');
        this.Y.one(this.baseSelector + ' .contentLoadError').on('click', this.contentLoad, this);
    }
});
