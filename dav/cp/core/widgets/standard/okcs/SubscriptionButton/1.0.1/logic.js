 /* Originating Release: February 2019 */
RightNow.Widgets.SubscriptionButton = RightNow.Widgets.extend({
    constructor: function() {
        var subscribeButton = this.Y.one(this.baseSelector + '_SubscribeButton');
          if(subscribeButton)
          subscribeButton.on('click', this._onSubscribeClick, this);
    },

    /**
    * Event handler executed when the subscription button is clicked
    * @param {Object} e Event
    */
    _onSubscribeClick: function(e) {
        e.halt();
        var eventObject;
        if(this.data.js.subscriptionID && this.data.js.subscriptionID !== null){
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               subscriptionID: this.data.js.subscriptionID,
               action: 'Unsubscribe'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displayUnsubscribeMessage,
                json: true, scope: this
            });
        }
        else {
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               docId: this.data.js.docId,
               versionID: this.data.js.versionID,
               active: true,
               action: 'Subscribe'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displaySubscribeMessage,
                json: true, scope: this
            });
        }
    },

    /**
    * Displays Unsubscription message from the ajax response success.
    * @param response Object response.
    */
    _displayUnsubscribeMessage: function(response) {
        this.data.js.subscriptionID = null;
        this._updateAriaAlert(this.data.attrs.label_unsubscribe_msg);
        RightNow.UI.displayBanner(this.data.attrs.label_unsubscribe_msg, {focus: true});
        this.Y.one(this.baseSelector + '_SubscribeButton').set("innerHTML", this.data.attrs.label_sub_button);
    },

    /**
    * Displays Subscription message from the ajax response success.
    * @param response Object response.
    */
    _displaySubscribeMessage: function(response) {
        this.data.js.answerID = response.content.answerId;
        this.data.js.subscriptionID = response.recordId;
        this.data.js.versionID = response.content.versionId;
        this._updateAriaAlert(this.data.attrs.label_subscribe_msg);
        RightNow.UI.displayBanner(this.data.attrs.label_subscribe_msg, {focus: true});
        this.Y.one(this.baseSelector + '_SubscribeButton').set("innerHTML", this.data.attrs.label_unsub_button);
    },

    /**
     * Updates the text for the ARIA alert div that appears above search rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
