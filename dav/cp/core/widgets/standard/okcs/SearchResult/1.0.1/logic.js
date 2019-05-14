 /* Originating Release: February 2019 */
RightNow.Widgets.SearchResult = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._contentDiv = this.Y.one(this.baseSelector + "_Content");
            this._okcsSearchSession = this.data.js.okcsSearchSession;
            this._transactionID = this.data.js.transactionID;
            this._priorTransactionID = this.data.js.priorTransactionID;
            this._truncateSize = this.data.js.truncateSize;
            this._answerPageUrl = this.data.js.answerPageUrl;

            this.searchSource().setOptions(this.data.js.filter)
                .on('collect', this.sendOkcsSession, this)
                .on('collect', this.sendTruncateSize, this)
                .on('collect', this.sendPriorTransactionID, this)
                .on('collect', this.sendTransactionID, this)
                .on('search', this._searchInProgress, this)
                .on('response', this._onReportChanged, this);

            this._displayDialogIfError(this.data.js.error);
            this.Y.one(this.baseSelector).delegate('click', this.onResultClick, '.rn_Element1 a', this);
        }
    },
    
    /**
    * Event handler received when search data is changing.
    * Shows progress icon during searches.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _searchInProgress: function(evt, args) 
    {
        RightNow.Event.fire("evt_pageLoading");
        this._reportResults = null;
        var newSearch = false;
        if (args[0] !== undefined) {
            var params = args[1];
            newSearch = (args[0].allFilters && args[0].allFilters.facet) === undefined ? true : false;
        }
    },

    /**
     * Event handler received when report data is changed.
     * @param {String} type Event name
     * @param {Array} args Arguments passed with event
     */
    _onReportChanged: function(type, args) 
    {
        var newContent = "", results = undefined;
        if (args[0] !== undefined && args[0].data.error !== undefined) {
            error = args[0].data.error;
            newContent = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + error.errorCode + ': ' + error.externalMessage + ' - ' + error.source + '</div>';
        }
        else {
            if (args[0] && args[0].data.searchResults !== undefined && args[0].data.searchResults !== null && args[0].data.searchResults.results !== null) {
                results = args[0].data.searchResults.results.results;
            }
            if(args[0].data.searchState !== undefined && args[0].data.searchState !== null) {
                this._okcsSearchSession = args[0].data.searchState.session;
                this._transactionID = args[0].data.searchState.transactionID;
                this._priorTransactionID = args[0].data.searchState.priorTransactionID;
            }
            if (results && results.length > 0) {
                this.Y.one(this.baseSelector).removeClass("rn_NoSearchResult");
                this.Y.one(this.baseSelector + "_NoSearchResult").addClass("rn_Hidden");
                newContent = new EJS({text: this.getStatic().templates.view}).render({data: results[0].resultItems, title: this.data.attrs.label_results, answerPageUrl: this._answerPageUrl, fileDescription: this.data.js.fileDescription, searchSession: this._okcsSearchSession, transactionID: this._transactionID, getUrlData: this.getUrlData, yuiObj: this.Y});
            }
            else {
                if(!this.data.attrs.hide_when_no_results) {
                    this.Y.one(this.baseSelector + "_NoSearchResult").removeClass("rn_Hidden");
                    this.Y.one(this.baseSelector).addClass("rn_NoSearchResult");
                }
            }
        }
        if (this.Y.one(this.baseSelector + "_Content")) {
            if(this.data.attrs.hide_when_no_results && newContent === '') {
                this.Y.one(this.baseSelector).removeClass("rn_SearchResult");
            }
            else if(!this.Y.one(this.baseSelector).hasClass("rn_SearchResult")) {
                this.Y.one(this.baseSelector).addClass("rn_SearchResult");
            }
            this.Y.one(this.baseSelector + "_Content").set("innerHTML", newContent);
            this.Y.one(this.baseSelector).removeClass("rn_Hidden");
        }

        if(results.length > 0) {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_success_alert);
            anchor = (this.Y.one('.rn_Element1 a'));
            if (anchor) {
                anchor.focus();
            }
        }
        else {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_no_results_alert);
        }
    },
    
    /**
     * Displays a warning dialog when a report error is encountered.
     * @private
     * @param {null|String} error
     */
    _displayDialogIfError: function(error) 
    {
        if (error) {
            RightNow.UI.Dialog.messageDialog(error, {"icon": "WARN"});
        }
    },
    
    /**
     * Updates the text for the ARIA alert div that appears above the results listings.
     * @private
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) 
    {
        if (!text) return;
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + "_Alert");
        if(this._ariaAlert) {
            this._ariaAlert.set("innerHTML", text);
        }
    },

    /**
     * Click handler for result div.
     * Makes the result href easier to click
     * by making the entire result div clickable.
     * @param {object} evt Click event
     */
    onResultClick: function(evt)
    {
        evt.halt();
        var resultLink = evt.currentTarget.hasClass('rn_SearchResultIcon') ? evt.currentTarget.next() : evt.currentTarget,
            clickThroughLink = resultLink.getAttribute('data-url'),
            iqAction = this._getUrlParameter(clickThroughLink, 'iq_action'),
            eventObject = new RightNow.Event.EventObject(this, {data: {
                answerID: resultLink.getAttribute('id'),
                docID: resultLink.getAttribute('data-id'),
                type: true,
                clickThroughLink: clickThroughLink,
                iqAction: iqAction
            }}),
            navigationUrl = resultLink.getAttribute('href');

            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data,  {successHandler: this._clickThruNavigate,
                failureHandler: this._clickThruNavigate, scope: this, data: {navigationUrl: navigationUrl}}
            );
    },

    /**
    * Called when we get clickthru api response
    * @param {object} response Response Object
    * @param {string} url Navigation URL
    * It navigates to answer view page based on the widget's target attribute
    */
    _clickThruNavigate: function(response, url) {
        if(this.data.attrs.target === '_blank')
            window.open(url.navigationUrl, '_blank');
        else
            RightNow.Url.navigate(url.navigationUrl, true);
    },
    
    /**
    * Called when a new search is triggered by a widget with the same source_id attribute.
    * Resets the member keeping track of additional results and responds with an event object
    * containing the widget's id.
    * @return {object} EventObject
    */
    onSearch: function() {
        this._additionalResults = null;
        return new RightNow.Event.EventObject(this, { data: {
            // Since the keyword being searched on is automatically included
            // (see the constructor comments above),
            // the only thing we need to send to the server is the widget's id
            // which is used to sort out which widget instance's method should
            // be used to handle the AJAX request.
            w_id: this.data.info.w_id
        }});
    },

    /**
     * This method returns the iq action from the url parameter
     * @param {string} url Document URL
     * @param {string} parameterName Request parameter to be fetched
     * @return {string|null} action name
     */
    _getUrlParameter: function(url, parameterName ) {
        parameterName = parameterName.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]" + parameterName + "=([^&#]*)";
        var regex = new RegExp( regexS );
        var results = regex.exec( url );
        return results ? results[1] : null;
    },

    /**
     * Adds the session key to the filter list
     */
    sendOkcsSession: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._okcsSearchSession, key: 'okcsSearchSession', type: 'okcsSearchSession'}
        });
    },

    /**
     * Adds the truncate size to the filter list
     */
    sendTruncateSize: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._truncateSize, key: 'truncate', type: 'truncate'}
        });
    },

    /**
     * Adds the transaction id to the filter list
     */
    sendTransactionID: function() {
        this._transactionID = this._transactionID + 1;
        return new RightNow.Event.EventObject(this, {
            data: {value: this._transactionID, key: 'transactionID', type: 'transactionID'}
        });
    },

    /**
     * Adds the prior transaction id to the filter list
     */
    sendPriorTransactionID: function() {
        this._priorTransactionID = this._transactionID;
        return new RightNow.Event.EventObject(this, {
            data: {value: this._priorTransactionID, key: 'priorTransactionID', type: 'priorTransactionID'}
        });
    }
});
