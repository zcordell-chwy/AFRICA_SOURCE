 /* Originating Release: February 2019 */
RightNow.Widgets.QuestionDetail = RightNow.Widgets.extend({
    constructor: function() {
        var questionDetails = this.Y.one(this.baseSelector);
        if (questionDetails) {
            questionDetails.delegate("click", this._toggleEditForm, '.rn_EditQuestionLink', this, true);
            questionDetails.delegate("click", this._toggleEditForm, '.rn_CancelEdit', this, false);
            questionDetails.delegate("click", this._deleteQuestionConfirm, '.rn_DeleteQuestion', this);
        }

        this._deleteDialog = null;
    },

    /**
     * Shows / hides the edit question area and
     * the content display area. Focuses on
     * the first focusable element in the
     * newly-displayed area.
     * @param {Object} e The event details
     * @param {Boolean} showForm Whether to show
     *                           the form (T) or hide it (F)
     */
    _toggleEditForm: function(e, showForm) {
        var toggleElements = this.Y.one(this.baseSelector).all('.rn_QuestionEdit,.rn_QuestionInfoOptions,.rn_QuestionHeader,.rn_QuestionBody,.rn_QuestionToolbar');
        toggleElements.toggleClass('rn_Hidden');
        this.Y.one(this.baseSelector + ((showForm) ? '_QuestionEdit input' : ' .rn_QuestionActions a')).focus();
        var questionEditDiv = this.Y.one(this.baseSelector + '_QuestionEdit');
        if(showForm && !this.data.attrs.use_rich_text_input && questionEditDiv.getAttribute('data-contentType') === 'text/html') {
            questionEditDiv.one('textarea.rn_TextArea').setAttribute('readonly', 'readonly');
        }
    },

    /**
     * Event handler to submit question delete action.
     * @param {Object} e Event
     */
    _deleteQuestionConfirm: function(event) {
        var confirmElement = this.Y.Node.create('<p id="rn_' + this.instanceID + '_QuestionDetailDeleteDialogText">')
                             .addClass('rn_QuestionDetailDeleteDialog')
                             .set('innerHTML', this.data.attrs.label_delete_confirm),
            buttons = [
                        { text: this.data.attrs.label_confirm_delete_button, handler: {fn: function(){
                            this._deleteQuestion(parseInt(event.currentTarget.getAttribute('data-questionID'), 10), event.currentTarget);
                        }, scope: this}, isDefault: true},
                        { text: this.data.attrs.label_cancel_delete_button, handler: {fn: function(){
                            this._deleteDialog.hide();
                        }, scope: this}, isDefault: false}
                      ];

        this._deleteDialog = RightNow.UI.Dialog.actionDialog(
            this.data.attrs.label_delete_confirm_title,
            confirmElement,
            {
                buttons: buttons,
                // Below attribute is required to make screen reader read dialog text
                dialogDescription: 'rn_' + this.instanceID + '_QuestionDetailDeleteDialogText'
            }
        );

        this._deleteDialog.show();
    },

    /**
     * Event handler to capture edit question delete click
     * @param {Object} event The event details
     */
    _deleteQuestion: function(questionID, target) {
        if (isNaN(questionID)) return;

        var eventObj = new RightNow.Event.EventObject(this, {data: {
            w_id:       this.data.info.w_id,
            questionID: questionID
        }});

        if (RightNow.Event.fire('evt_deleteQuestionRequest', eventObj)) {
            this._deleteDialog.destroy();
            target.setHTML(this.data.attrs.label_deleting)
                .toggleClass('rn_DeleteQuestion', false)
                .toggleClass('rn_DeletingQuestion', true)
                .set('disabled', true);

            RightNow.Ajax.makeRequest(this.data.attrs.delete_question_ajax, eventObj.data, {
                successHandler: this._deleteQuestionResponse,
                scope:          this,
                data:           [eventObj],
                json:           true
            });
        }
    },

    /**
     * Callback after the question's been deleted.
     * @param {Object} response Response from server
     * @param {Array} args `data` passed in the options to makeRequest
     */
    _deleteQuestionResponse: function(response, args) {
        var message;
        if(!response.errors && RightNow.Event.fire('evt_deleteQuestionResponse', response, args[0])) {
            RightNow.UI.displayBanner(this.data.attrs.successfully_deleted_question_banner, { type: 'SUCCESS' }).on('close', function() {
                (args[1] || window.location).href = this.data.attrs.deleted_question_redirect_url;
            }, this);
        }
        else if(!RightNow.Ajax.indicatesSocialUserError(response)) {
            var deleteButton = this.Y.one('.rn_DeletingQuestion');

            if (response.errors) {
                message = response.errors[0].externalMessage;
            }
            else {
                RightNow.UI.displayBanner(RightNow.Interface.getMessage("THERE_WAS_PROBLEM_IN_DELETING_QUESTION_MSG"), { type: 'ERROR', focus: true });
            }
            RightNow.UI.displayBanner(message , {
                type: 'ERROR',
                focusElement: deleteButton
            });

            if (deleteButton) {
                deleteButton.setHTML(this.data.attrs.label_delete_button)
                        .toggleClass('rn_DeletingQuestion', false)
                        .toggleClass('rn_DeleteQuestion', true)
                        .set('disabled', false);
            }
        }
    }
});
