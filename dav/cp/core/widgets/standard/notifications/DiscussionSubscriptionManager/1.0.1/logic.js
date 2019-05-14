 /* Originating Release: February 2019 */
RightNow.Widgets.DiscussionSubscriptionManager = RightNow.Widgets.Multiline.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.bottomDiv = this.Y.one(this.baseSelector + '_Bottom');
            this.Y.one(this.baseSelector).delegate('click', this._deleteSubscription, 'button[class="rn_Discussion_Delete"]', this);
            this.Y.one(this.baseSelector + '_UnsubscribeAll').delegate('click', this._confirmAndSubmitUnsubscribeAllRequest, 'a', this);
            var addNotifButton = this.Y.one(this.baseSelector + '_AddButton');
            if (addNotifButton) {
                addNotifButton.on('click', this._openDialog, this);
                RightNow.Event.subscribe('evt_productCategorySelected', this._getProdCatID, this);
            }

            RightNow.Widgets.formTokenRegistration(this);
        },

        /**
         * Event handler received when report data is changed.
         * @param {String} type Event name
         * @param {Array} args Arguments passed with event
         */
        _onReportChanged: function(type, args) {
            args[0].data.label_unsubscribe = this.data.attrs.label_unsubscribe;
            this.parent(type, args);
            if (args[0].data.total_num === 0) {
                this.Y.one(this.baseSelector + "_Content").set("innerHTML", this.data.attrs.label_no_notification);
                this.Y.one(this.baseSelector + '_UnsubscribeAll').addClass('rn_Hidden');
            }
            else {
                this.Y.one(this.baseSelector + '_UnsubscribeAll').removeClass('rn_Hidden');
            }
            this.bottomDiv.show();
        },

        /**
         * Event handler received when search data is changing.
         * Shows progress icon during searches.
         * @param {String} evt Event name
         * @param {Array} args Arguments provided from event fire
         */
        _searchInProgress: function(evt, args) {
            this.bottomDiv.hide();
            this.parent(evt, args);
        }
    },

    /**
     * Creates and displays a warning dialog to the user asking whether they would like
     * to continue unsubscribe all action
     */
    _confirmAndSubmitUnsubscribeAllRequest: function() {
        if (!this._confirmDialog) {
            var dialogBody = this.Y.Node.create("<div>").set("innerHTML", this.data.attrs.label_unsubscribe_all_confirm);
            var buttons = [
                {text: RightNow.Interface.getMessage("YES_LBL"), handler: {fn: this._deleteAllSubscriptions, scope: this}},
                {text: RightNow.Interface.getMessage("NO_LBL"), handler: {fn: this._hideConfirmDialog, scope: this}, isDefault: true}
            ];
            this._confirmDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("WARNING_LBL"), dialogBody, {"buttons": buttons});
            this.Y.DOM.addClass(this._confirmDialog.id, 'rn_dialog');
            RightNow.UI.Dialog.addDialogEnterKeyListener(this._confirmDialog, this._deleteAllSubscriptions, this);
        }
        this._confirmDialog.show();
    },

    /**
     * Hide confirm box when user click on No / Confirmed to perform an action.
     */
    _hideConfirmDialog: function () {
        if (this._confirmDialog) {
            this._confirmDialog.hide();
        }
    },

    /**
     * Create event object and make AJAX request for unsubscribing to a question or product/category
     * @param {Object} e Current event object
     */
    _deleteSubscription: function(e) {
        var id = e.target.ancestor('.rn_Discussions').getData('id');
        this._makeAjaxRequest(this.data.attrs.delete_social_subscription_ajax, id, 'unsubscribe');
    },

    /**
     * Create event object and make AJAX request for unsubscribing to all questions or products/categories
     */
    _deleteAllSubscriptions: function() {
        this._hideConfirmDialog();
        this._makeAjaxRequest(this.data.attrs.delete_social_subscription_ajax, "-1", 'unsubscribe');
    },

    /**
     * Create event object and make AJAX request for subscribing to a question or product/category
     */
    _addSubscription: function() {
        this._closeDialog();
        if(this.prodCatID) {
            this._makeAjaxRequest(this.data.attrs.add_social_subscription_ajax, this.prodCatID, 'subscribe');
            return;
        }

        var mapping = {
                Product: RightNow.Interface.getMessage('PRODUCT_LC_LBL'),
                Category: RightNow.Interface.getMessage('CATEGORY_LWR_LBL'),
                Question: RightNow.Interface.getMessage('QUESTION_LC_LBL')
            },
            label = this.data.attrs.label_select_option,
            message = label.indexOf('%s') > -1 ? RightNow.Text.sprintf(label, mapping[this.data.attrs.subscription_type]) : label;

        RightNow.UI.displayBanner(message, { type: 'ERROR', focus: true });
    },

    /**
     * Event handler executed when a product/category is selected from menu
     * @param {Object} evt Current event object
     * @param {Array} eventData Event data
     */
    _getProdCatID: function(evt, eventData) {
        this.prodCatID = eventData[0].data.hierChain[eventData[0].data.hierChain.length - 1];
    },

    /**
     * Creates and opens a dialog allowing the user to add product or category notifications
     */
    _openDialog: function() {
        this.prodCatID = 0;
        var dialogBody = this.Y.one(this.baseSelector + "_Dialog");
        if (dialogBody && !this._dialog)
        {
            dialogBody = this.Y.Node.getDOMNode(dialogBody.removeClass('rn_Hidden'));
            var buttons = [
                {text: this.data.attrs.label_prodcat_dialog_add_button, handler: {fn: this._addSubscription, scope: this}, isDefault: false},
                {text: this.data.attrs.label_prodcat_dialog_cancel_button, handler: {fn: this._closeDialog, scope: this}, isDefault: true}
            ];
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_add_prodcat_notification_dialog, dialogBody, {"buttons": buttons});
            RightNow.UI.show(dialogBody);
        }
        this.Y.one("#" + this._dialog.get('id')).addClass("rn_DiscussionSubscriptionManager_Dialog");
        this._dialog.show();
        this._dialog.bodyNode.one("a").focus();
        this._dialog.hideEvent.subscribe(this._closeDialog, null, this);
    },

    /**
     * Closes the product/category dialog
     * @param evt {String|Object} Event name or node that was clicked
     */
    _closeDialog: function(evt) {
        if (this._dialog) {
            RightNow.Event.fire("evt_resetProductCategoryMenu", new RightNow.Event.EventObject(this));
            this._dialog.hide();
        }
    },

    /**
     * Makes an AJAX request to the enpoint
     * @param {String} requestEndpoint Ajax request url
     * @param {String} objectID ID of the social question or product/category
     */
    _makeAjaxRequest: function(requestEndpoint, objectID, action) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {id: objectID, type: this.data.attrs.subscription_type, action:action, f_tok: this.data.js.f_tok}});
        RightNow.Ajax.makeRequest(requestEndpoint, eventObject.data, {
            successHandler: this._onResponse, scope: this, data: eventObject, json: true
        });
    },

    /**
     * On successful subscribing/unsubscribing clear the cache and fire search
     * else display an error message
     * @param {Object} response Object which has success or error message to display
     * @param {Object} eventObject Object used for AJAX Request
     */
    _onResponse: function(response, eventObject) {
        var textToDisplay = [];
        if (response.success) {
            this.searchSource().clearCache();
            this.searchSource().fire("search", eventObject);
            if (eventObject.data.action === 'subscribe') {
                RightNow.UI.displayBanner(this.data.attrs.label_on_subscription_success_banner, {focus: true});
            }
            else {
                var discussionSubscriptionManager = this.Y.one(this.baseSelector);
                RightNow.UI.displayBanner(this.data.attrs.label_on_deleting_subscription_success_banner, {focus: true}).on('blur', function() {
                    var firstLink = discussionSubscriptionManager.get("children").item(0).one("a");
                    if (firstLink) { firstLink.focus(); }
                });
            }
        }
        else {
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                if (response.errors) {
                    this.Y.Object.each(response.errors, function(message) {
                        if (message.hasOwnProperty('externalMessage'))
                            textToDisplay.push(message.externalMessage);
                    });
                }
                RightNow.UI.displayBanner((textToDisplay.length !== 0) ? textToDisplay.join('<br>') : this.data.attrs.label_on_failure_banner, {focus: true, type: 'ERROR'});
            }
        }
    }
});
