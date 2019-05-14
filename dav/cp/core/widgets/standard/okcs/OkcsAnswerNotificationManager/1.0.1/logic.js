 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsAnswerNotificationManager = RightNow.Widgets.extend({
    constructor: function() {
        this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
        this.notificationList = this.Y.one(this.baseSelector + '_List');
        this.Y.all(this.baseSelector + ' button').on("click", this._unsubscribeAnswer, this);
    },
    
    /**
     * Remove answer from the subscription list
     * @param {Object} evt Event
     */
    _unsubscribeAnswer: function(evt) {
        var subscriptionID = evt.target.getAttribute('id'),
            eventObject = new RightNow.Event.EventObject(this, {data: {
            subscriptionID: subscriptionID
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.delete_notification_ajax, eventObject.data, {
            successHandler: function(response, args){
                if(response.failure) {
                    RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG"), {icon: "WARN"});
                }
                else {
                    this.displayMessage(this.data.attrs.label_notif_deleted);
                    var item = this.Y.one(this.baseSelector + '_' + subscriptionID), scope = this;
                    if(item) {
                        item.transition({
                            opacity: 0,
                            duration: 0.4
                        }, function() {
                            this.remove();
                            if(!scope.notificationList.all('.rn_Notification').size()) {
                                scope.notificationList.append(scope.data.attrs.label_no_notifs);
                            }
                        });
                    }
                }
            },
            json: true, data: {subscriptionID : subscriptionID}, scope: this
        });
    },

    /**
    * Displays success message in message box above widget or as user specified div.
    * @param message String Message to display.
    */
    displayMessage: function(message) {
        if(this.messageBox) {
            this.messageBox.setStyle("opacity", 0).addClass("rn_MessageBox");
            this.messageBox.transition({
                opacity: 1,
                duration: 0.4
            });
            this.messageBox.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this.messageBox.set('tabIndex', 0);
            this.messageBox.focus();
        }
        else {
            RightNow.UI.displayBanner(message, {focus: true});
        }
    }
});
