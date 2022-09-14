 /* Originating Release: February 2019 */
RightNow.Widgets.LogoutLink = RightNow.Widgets.extend({
    constructor: function(){
        var logoutLink = this.Y.one(this.baseSelector + "_LogoutLink");
        if(!logoutLink)
            return;
        logoutLink.on("click", this._onLogoutClick, this);
    },

    /**
     * Event handler for when logout has occured
     * @param {Object} response Response object
     * @param {Object} originalEventObj Original event object
     */
    _onLogoutCompleted: function(response, originalEventObj) {
        if(!RightNow.Event.fire("evt_logoutResponse", {data: originalEventObj, response: response}))
            return;

        var Url = RightNow.Url;
        if(response.success === 1 && !RightNow.UI.Form.logoutInProgress && originalEventObj.w_id === this.instanceID)
        {
            RightNow.UI.Form.logoutInProgress = true;
            //If redirect is specified in the controller, use it, otherwise default
            //to response from server for compatability
            if(this.data.js && this.data.js.redirectLocation)
            {
                if(response.session)
                    this.data.js.redirectLocation = Url.addParameter(this.data.js.redirectLocation, 'session', RightNow.Text.getSubstringAfter(response.session, 'session/'));
                Url.navigate(this.data.js.redirectLocation, true);
            }
            else
            {
                if(response.socialLogout)
                    Url.navigate(response.socialLogout, true);
                else if(this.data.attrs.redirect_url === '')
                    Url.navigate(response.url, true);
                else
                    Url.navigate(this.data.attrs.redirect_url + response.session, true);
            }
        }
    },

    /**
     * Event handler for when logout is clicked.
     */
    _onLogoutClick: function() {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            w_id: this.instanceID,
            currentUrl: window.location.pathname, 
            redirectUrl: this.data.attrs.redirect_url
        }});
        if(RightNow.Event.fire("evt_logoutRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.logout_ajax,
                eventObject.data,
                {successHandler: this._onLogoutCompleted, scope: this, data: eventObject, json: true});
        }
    }
});
