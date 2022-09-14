 /* Originating Release: February 2019 */
RightNow.Widgets.MobileAnswerFeedback = RightNow.Widgets.extend({
    constructor: function() {
        this._threshold = 1;
        this._options = 2;

        this._noButton = this.Y.one(this.baseSelector + "_RatingNoButton");
        if (this._noButton) {
            this._noButton.on("click", this._onClick, this, 1);
        }
        this._yesButton = this.Y.one(this.baseSelector + "_RatingYesButton");
        if (this._yesButton) {
            this._yesButton.on("click", this._onClick, this, 2);
        }
        RightNow.Event.subscribe("evt_formTokenUpdate", this._onFormTokenUpdate, this);
    },

    /**
     * Event handler for when a user clicks on an answer rating
     * @param {Object} event Click event
     * @param {Number} rating The rating
     */
    _onClick: function(event, rating) {
        if(this._noButton && this._yesButton) {
            this._noButton.setStyle("display", "none");
            this._yesButton.setStyle("display", "none");
        }

        this._rate = rating;

        this._submitAnswerRating();
    },

    /**
     * Submit answer rating for clickstreams record. Called immediately
     * when the user chooses a rating.
     */
    _submitAnswerRating: function() {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                a_id: this.data.js.answerID,
                rate: this._rate,
                options_count: this._options
        }});
        RightNow.ActionCapture.record('answer', 'rate', this.data.js.answerID);
        //Record the rating as a percentage. To get it, subtract 1 off both the count and the rating to support the 0% case
        RightNow.ActionCapture.record('answer', 'rated', ((this._rate - 1) / (this.data.attrs.options_count - 1)) * 100);

        if (RightNow.Event.fire("evt_answerRatingRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_rating_ajax, eventObject.data, {successHandler: this._onRatingResponseReceived, scope: this, data: {eventName: "evt_answerRatingResponse", data: eventObject}, json: true});
            //Don't wait for the rating submission to finish before we show the user text allowing them to actually submit feedback content
            var thanksLabel = this.Y.one(this.baseSelector + "_ThanksLabel"),
                message;
            if (thanksLabel) {
                thanksLabel.set("role", "alert");
                if(this._rate === 1) {
                    message = this.Y.Node.create("<a href='javascript:void(0);' id='" + this.baseDomID + "_FeedbackLink'>" + this.data.attrs.label_provide_feedback + "</a>");
                    message.on("click", this._showDialog, this);
                    message.prepend("<span>" + this.data.attrs.label_dissatisfied + "</span><br>");
                }
                else {
                    message = this.data.attrs.label_satisfied;
                }
                thanksLabel.setContent(message)
                    .removeClass("rn_Hidden")
                    .set("tabIndex", -1)
                    .focus();
            }
        }
    },

    /**
     * Event handler for server sends rating response. Only fires the 'evt_answerRatingResponse' event since we have
     * no UI updates to perform
     * @param {String} type Event name
     * @param {Object} arg Event arguments
     */
    _onRatingResponseReceived: function(response, originalEventObj) {
        RightNow.Event.fire("evt_answerRatingResponse", {data: originalEventObj, response: response});
    },

    /**
     * Constructs and shows the dialog
     */
    _showDialog: function() {
        // get a new f_tok value each time the dialog is opened
        RightNow.Event.fire("evt_formTokenRequest",
            new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

        if (!this._dialog) {
            this._emailField = this.Y.one(this.baseSelector + "_Email");
            this._errorDisplay = this.Y.one(this.baseSelector + "_ErrorMessage");
            this._feedbackField = this.Y.one(this.baseSelector + "_FeedbackText");

            var buttons = [ { text: this.data.attrs.label_send_button, handler: {fn: this._onSubmit, scope: this}, isDefault: true},
                     { text: this.data.attrs.label_cancel_button, handler: {fn: this._onCancel, scope: this}, isDefault: false}],
                 dialogForm = this.Y.one(this.baseSelector + "_Form");
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, dialogForm, {"buttons": buttons, "cssClass": "rn_MobileAnswerFeedback"});
            RightNow.UI.show(dialogForm);
        }
        if (this._errorDisplay) {
            this._errorDisplay.set('innerHTML', '').removeClass('rn_MessageBox rn_ErrorMessage');
        }

        this._dialog.show();

        // Enable controls, focus the first input element
        var focusElement;
        if (this._emailField && this._emailField.get("value") === '')
            focusElement = this._emailField;
        else
            focusElement = this._feedbackField;

        focusElement.focus();
    },

    /**
     * Event handler for click of submit button.
     */
    _onSubmit: function() {
        this._dialog.disableButtons();
        if (!this._validateDialogData()) {
            this._dialog.enableButtons();
            return;
        }
        this._incidentCreateFlag = true;  //Keep track that we're creating an incident.
        this._submitFeedback();
    },

    /**
     * Event handler for click of cancel button.
     */
    _onCancel: function() {
        this._closeDialog(true);
    },

    /**
     * Validates dialog data.
     */
    _validateDialogData: function() {
        this._errorDisplay.removeClass('rn_MessageBox rn_ErrorMessage').set("innerHTML", "");

        var returnValue = true;
        if (this._emailField) {
            this._emailField.set("value", this.Y.Lang.trim(this._emailField.get("value")));
            if (this._emailField.get("value") === "") {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_email_address), this._emailField.get("id"));
                returnValue = false;
            }
            else if (!RightNow.Text.isValidEmailAddress(this._emailField.get("value"))) {
                this._addErrorMessage(this.data.attrs.label_email_address + ' ' + RightNow.Interface.getMessage("FIELD_IS_NOT_A_VALID_EMAIL_ADDRESS_MSG"), this._emailField.get("id"));
                returnValue = false;
            }
        }
        // Examine feedback text.
        this._feedbackField.set("value", this.Y.Lang.trim(this._feedbackField.get("value")));
        if (this._feedbackField.get("value") === "") {
            this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_comment_box), this._feedbackField.get("id"));
            returnValue = false;
        }
        return returnValue;
    },

    /**
     *  Close the dialog.
     * @param cancelled Boolean T if the dialog was canceled
    */
    _closeDialog: function(cancelled) {
        if(!cancelled) {
            //Feedback submitted: clear existing data if dialog is reopened
            this._feedbackField.set("value", "");
        }
        if(this._errorDisplay) {
            // Get rid of any existing error message, so it's gone if the user opens the dialog again.
            this._errorDisplay.set("innerHTML", "")
                    .removeClass('rn_MessageBox rn_ErrorMessage');
        }

        if (this._dialog) {
            this._dialog.enableButtons();
            this._dialog.hide();
        }
    },

    /**
     * Submit data to the server.
     */
    _submitFeedback: function() {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                a_id: this.data.js.answerID,
                rate: this._rate,
                threshold: this._threshold,
                options_count: this._options,
                message: this._feedbackField.get("value"),
                email: (this._emailField) ? this._emailField.get('value') : this.data.js.email,
                f_tok: this.data.js.f_tok
        }});

        if (RightNow.Event.fire("evt_answerFeedbackRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_feedback_ajax, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
        }
        RightNow.Event.subscribe("evt_answerFeedbackSubmitResponse", this._onResponseReceived, this);
    },

    /**
     * Event handler for server sends response.
     * @param {String} type Event name
     * @param {Object} arg Event arguments
     */
    _onResponseReceived: function(response, originalEventObj) {
        if (RightNow.Event.fire("evt_answerFeedbackResponse", {data: originalEventObj, response: response})) {
            // If this widget's request created an incident, show confirmation dialog.
            if (this._incidentCreateFlag) {
                this._incidentCreateFlag = false;
                var dialogOptions, message;
                if (typeof response === "string") {
                    message = response;
                    dialogOptions = {icon: "WARN", exitCallback: {fn: this._enableDialog, scope: this}};
                }
                else {
                    message = this.data.attrs.label_feedback_submit_success;
                    dialogOptions = {exitCallback: {fn: this._closeDialog, scope: this}};
                }
                RightNow.UI.Dialog.messageDialog(message, dialogOptions);
            }
            else {
                this._closeDialog();
            }
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes.
     * @param {String} message The error message to display
     * @param {String} focusID The HTML element id to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusID) {
        if(this._errorDisplay) {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusID + '\').focus(); return false;">' + message + '</a>',
                oldMessage = this._errorDisplay.get("innerHTML");
            this._errorDisplay.set("innerHTML", (oldMessage === "") ? newMessage : (oldMessage + "<br>" + newMessage))
                    .get("firstChild").focus();
        }
    },

    /**
     * Handles response from successful getFormToken request
     * @param type {string} Type/name of event
     * @param args {array} Contains an EventObject
     */
    _onFormTokenUpdate: function(type, args) {
        var eventObject = args[0];
        if (eventObject.data.newToken && this.instanceID === eventObject.w_id) {
            this.data.js.f_tok = eventObject.data.newToken;
        }
    }
});
