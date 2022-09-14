 /* Originating Release: February 2019 */
RightNow.Widgets.OkcsRecommendContent = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
            this._contentDiv = this.Y.one(this.baseSelector + '_RecommendContent');
            this._contentTypeList = this.data.js.contentTypes;
            this._selectedContentType = this.data.js.selectedContentType;
            this._isRecommendChange = this.data.js.isRecommendChange;
            this._recommendationButton = this.Y.one(this.baseSelector + '_RecommendContentButton');
            var recommendationSubmit = this.Y.one(this.baseSelector + '_RecommendationSubmit');
            var recommendationCancel = this.Y.one(this.baseSelector + '_RecommendationCancel');
            this._contentType = this.Y.one(this.baseSelector + '_ContentType');
            this._recommendationForm = this.Y.one(this.baseSelector + '_RecommendForm');
            this._charactersRemainingDiv = this.Y.one(this.baseSelector + '_CharacterRemaining');
            this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);
            this._errorHtml = '';
            this._contentDescriptionValue = '';
            this._charactersRemaining = '';
            
            if(this._recommendationButton)
                this._recommendationButton.on('click', this._onRecommendationButtonClick, this);

            if(recommendationSubmit)
                recommendationSubmit.on('click', this._onRecommendationSubmit, this);

            if(recommendationCancel)
                recommendationCancel.on('click', this._onRecommendationCancel, this);
            
            this._contentDescription = this.Y.one(this.baseSelector + '_Description');
            if(this._contentDescription)
                this._contentDescription.on('keyup', this._updateCharacterCount, this);
            
            if(this.data.js.sources) {
                this.searchSource().setOptions(this.data.js.sources);
                this.searchSource().on('response', this._onContentTypeChanged, this);
            }
        }
    },

    /**
    * Event handler executed when recommendation button is clicked
    */
    _onRecommendationButtonClick: function() {
        var errorDiv = this.Y.one(this.baseSelector + '_ErrorLocation'),
            simpleText = this.Y.one(this.baseSelector + '_Description');

        errorDiv.removeClass('rn_MessageBox rn_ErrorMessage');
        errorDiv.set("innerHTML", '');
        this._errorHtml = '';
        var caseNumber = this.Y.one(this.baseSelector + '_CaseNumber');
        if(caseNumber !== null ){caseNumber.set('value', '');}
        this.Y.one(this.baseSelector + '_Title').set('value', this.data.js.title);
        var priority = this.Y.one(this.baseSelector + '_Priority');
        if(priority !== null ) {
             priority.set('value', 'None');
        }
        this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
        this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);

        if(simpleText)
            simpleText.set('value', '');

        if(this._contentTypeList === undefined && !this._isRecommendChange) {
            var eventObject = new RightNow.Event.EventObject(this, {data: {contentTypeList: this.data.attrs.content_type_list}});
            RightNow.Ajax.makeRequest(this.data.attrs.retrieve_content_type_ajax, eventObject.data, {
                successHandler: this._showContentTypes,
                json: true, scope: this
            });
        }
        else {
            this._recommendationButton.addClass('rn_Hidden');
            this._recommendationForm.removeClass('rn_Hidden');
        }
    },
    
    /**
     * Content Type AJAX call success handler.
     * @param {Object} response Response Object
     */
    _showContentTypes: function(response){
        if(!response.failure && this._contentType !== null) {
            this._contentTypeList = response;
            this.Y.Array.each(this._contentTypeList, function(currentNode){
                this._contentType.appendChild(this.Y.Node.create('<option value="' + currentNode.referenceKey + '" >' + currentNode.name + '</option>'));
            }, this);
            this._recommendationButton.addClass('rn_Hidden');
            this._recommendationForm.removeClass('rn_Hidden');
        }
    },
    
    /**
     * This method is called to create content recommendation in info manager
     * @param {array} contentData Array containg content recommendation data
     */
    createRecommendation: function(contentData) {
        var caseNumberRef = this.Y.one(this.baseSelector + '_CaseNumber');
        var priorityRef = this.Y.one(this.baseSelector + '_Priority');	
        var contentType,
            recordId,
            referenceKey,
            name,
            selectedContentType = this._selectedContentType,
            isRecommendChange = this._isRecommendChange,
            caseNumber = caseNumberRef != null ? caseNumberRef.get('value') : '',
            title = this.Y.one(this.baseSelector + '_Title').get('value'),
            priority = priorityRef != null ? priorityRef.get('value') : 'None',
            errorHtml = '';
        if(selectedContentType === '' && !isRecommendChange)
            selectedContentType = this.Y.one(this.baseSelector + '_ContentType').get('value');
        this.Y.Array.each(this._contentTypeList, function(currentNode){
            if (currentNode.referenceKey === selectedContentType) {
                contentType = currentNode;
                recordId = contentType.recordId;
                referenceKey = contentType.referenceKey;
                name = contentType.name;
            }
        }, this);
        
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            caseNumber: caseNumber,
            title: title,
            priority: priority,
            comments: contentData.comments,
            isRecommendChange: isRecommendChange,
            contentTypeRecordId: recordId,
            contentTypeReferenceKey: referenceKey,
            contentTypeName: name,
            answerId: this.data.js.answerId,
            contentRecordId: this.data.js.contentRecordID,
            documentId: this.data.js.docID
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.create_recommendation_ajax, eventObject.data, {
            successHandler: this._displayRecommendationSubmissionMessage,
            json: true, scope: this
        });
    },
    
    /**
     * Displays Recommendation submission message.
     * @param {Object} response Response from the recommendation submit AJAX request.
     */
    _displayRecommendationSubmissionMessage: function(response){
        this._toggleLoadingIndicators(false);
        if(response.failure) {
            this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
        }
        else if(response.validationError) {
            var errorDiv = this.Y.one(this.baseSelector + '_ErrorLocation'),
                error = '<div><b>' + this.data.attrs.label_recommend_description + " : " + this.data.attrs.label_maxlength_error + '</b></div>';
            errorDiv.set("innerHTML", error);
            errorDiv.addClass('rn_MessageBox rn_ErrorMessage');
            this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
        }
        else {
            this.displayMessage(this.data.attrs.label_success_msg);
            this._recommendationForm.addClass('rn_Hidden');
            this._recommendationButton.removeClass('rn_Hidden');
        }
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
        RightNow.Event.fire("evt_clearRichTextInput");
    },

    /**
     * Event handler executed when recommendation is submitted
     * @param {Object} e Event
     */
    _onRecommendationSubmit: function(e) {
        e.halt();
        var errorDiv = this.Y.one(this.baseSelector + '_ErrorLocation');
        errorDiv.removeClass('rn_MessageBox rn_ErrorMessage');
        errorDiv.set("innerHTML", '');
        this._errorHtml = '';
        
        if(this.Y.one(this.baseSelector + '_Title').get('value') === '')
            this._errorHtml += '<div><b>' + this.data.attrs.label_recommend_title + this.data.js.fieldIsRequired + '</b></div>';
        
        this._contentDescriptionValue = this.Y.one(this.baseSelector + '_Description').get('value');
        
        if(this._contentDescriptionValue === '')
            this._errorHtml += '<div><b>' + this.data.attrs.label_recommend_description + this.data.js.fieldIsRequired + '</b></div>';
        
        if(this._errorHtml !== '') {
            errorDiv.set("innerHTML", this._errorHtml);
            errorDiv.addClass('rn_MessageBox rn_ErrorMessage');
            return;
        }
        else {
            this._toggleLoadingIndicators();
            this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", "true");
            this.createRecommendation({'comments' : this._contentDescriptionValue});
        }
    },

    /**
     * Event handler executed when recommendation is cancelled
     * @param {Object} e Event
     */
    _onRecommendationCancel: function(e) {
        this._recommendationForm.addClass('rn_Hidden');
        this._recommendationButton.removeClass('rn_Hidden');
        this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);
    },

    /**
     * Event handler executed on click of content type
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onContentTypeChanged: function(type, args) {
        this._recommendationButton.removeClass('rn_Hidden');
        this._recommendationForm.addClass('rn_Hidden');
        this._selectedContentType = args[0].data && args[0].data.selectedChannel;
        
        var isRecommendationAllowed = false;
        for (var i = 0; this._contentTypeList[i]; i++) {
            if (this._contentTypeList[i].referenceKey === this._selectedContentType) {
                isRecommendationAllowed = true;
            }
        }
        isRecommendationAllowed ? this._contentDiv.removeClass('rn_Hidden') : this._contentDiv.addClass('rn_Hidden');
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
    },

    /**
     * Hides / shows the status message.
     * @param {Boolean} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F), or toggle their current state (default) (optional)
     */
    _toggleLoadingIndicators: function(turnOn) {
        var classFunc = ((typeof turnOn === "undefined") ? "toggleClass" : ((turnOn === true) ? "removeClass" : "addClass")),
            message = this.Y.one(this.baseSelector + "_StatusMessage");
        if (message) {
            message[classFunc]("rn_Hidden").setAttribute("aria-live", (message.hasClass("rn_Hidden")) ? "off" : "assertive");
        }
        this.Y.one(this.baseSelector + "_StatusMessage").addClass('rn_OkcsSubmit');
    },
    
    /**
     * Updates the number of characters entered in description field
     */
    _updateCharacterCount: function() {
        this._charactersRemaining = this.data.attrs.default_maxlength_value - this._contentDescription.get('value').length;
        this._charactersRemainingDiv.set('innerHTML', this._charactersRemaining + " " + this.data.attrs.label_characters_remaining)
    }
});
