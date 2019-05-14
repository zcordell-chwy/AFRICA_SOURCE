 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsAnswerNotificationManager = RightNow.Widgets.extend({
    constructor: function() {
        this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
        this.notificationList = this.Y.one(this.baseSelector + '_List');
        this._contentName = this.baseSelector + '_Content';
        this._contentDiv = this.Y.one(this._contentName);
        this._loadingDiv = this.Y.one(this.baseSelector + '_Loading');
        this.Y.all(this.baseSelector + ' button').on("click", this._unsubscribeAnswer, this);
        this.Y.one(this.baseSelector + '_Grid').delegate('click', this._sortSubscriptionList, 'th', this);
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
    },
    
    /**
    * Sort event handler executed when the column header is clicked
    * @param {Object} evt Event
    */
    _sortSubscriptionList: function(evt) {
        var sortIconNode = evt.target.getElementsByTagName('span').item(0);
        if(!sortIconNode && evt.target.getDOMNode().nodeName === 'SPAN') {
            sortIconNode = evt.target;
        }

        if(sortIconNode) {
            var sortDirection = sortIconNode.hasClass('rn_ArticlesSortDesc') ? 'desc' : 'asc',
                sortColumn = sortIconNode.get('parentNode').getAttribute('id').replace(this.baseDomID + '_', ''),
                eventObject = new RightNow.Event.EventObject(this, {data: {
                    direction: sortDirection,
                    sortColumn: sortColumn.trim(),
                    titleLength: this.data.attrs.max_wordbreak_trunc,
                    maxRecords: this.data.attrs.rows_to_display
                }});
            this._setLoading(true);
            RightNow.Ajax.makeRequest(this.data.attrs.sort_notification_ajax, eventObject.data, {
                successHandler: function(response, args){
                    if(response.failure) {
                        RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG"), {icon: "WARN"});
                    }
                    else {
                        var notificationList = this.Y.one(this.baseSelector + '_Body');
                        notificationList.get('childNodes').remove();
                        notificationList.append(new EJS({text: this.getStatic().templates.view}).render({
                            subscriptionList: response,
                            fields: this.data.js.fields,
                            target: this.data.attrs.target,
                            widgetInstanceID: this.baseDomID,
                            answerUrl: this.data.js.answerUrl
                        }));
                        if(sortIconNode.hasClass('yui3-datatable-sort-indicator')) {
                            sortIconNode.removeClass('yui3-datatable-sort-indicator');
                            this.Y.all('.rn_ArticlesSortDesc').toggleClass('yui3-datatable-sort-indicator').removeClass('rn_ArticlesSortDesc');
                            this.Y.all('.rn_ArticlesSortAsc').toggleClass('yui3-datatable-sort-indicator').removeClass('rn_ArticlesSortAsc');
                        }
                        sortIconNode.hasClass('rn_ArticlesSortDesc') ? sortIconNode.replaceClass ('rn_ArticlesSortDesc', 'rn_ArticlesSortAsc' ) : sortIconNode.replaceClass ('rn_ArticlesSortAsc', 'rn_ArticlesSortDesc' );
                    }
                    this._setLoading(false);
                },
                json: true, scope: this
            });
        }
    },

    /**
    * changes the loading icon and hides/unhide the data
    * @param {Bool} loading
    */
    _setLoading: function(loading) {
        var toOpacity = 1,
            method = 'removeClass';
        if (loading) {
            //keep height to prevent collapsing behavior
            this._contentDiv.setStyle('height', this._contentDiv.get('offsetHeight') + 'px');
            toOpacity = 0;
            method = 'addClass';
        }
        this._contentDiv.transition({
            opacity: toOpacity,
            duration: 0.4
        });
        this._loadingDiv[method]('rn_Loading');
    }
});
