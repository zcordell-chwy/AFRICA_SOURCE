 /* Originating Release: February 2019 */
RightNow.Widgets.DocumentRating = RightNow.Widgets.extend({
    constructor: function() {
        this._rating = '';
        var submitButton = this.Y.one(this.baseSelector + '_SubmitButton');
        if(submitButton)
            submitButton.on('click', this._submitRating, this);
        this.Y.all(this.baseSelector + ' .rn_RatingInput').on('click', this._updateRating, this);
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on('click', this._updateRating, this);
        this.Y.one(this.baseSelector).delegate('click', this._selectRating, 'button.rn_Rating', this);
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on({mouseenter : this._onMouseOver, mouseleave: this._onMouseOut}, this, this);
    },

    /**
    * Event handler executed when the channel is clicked
    * @param {Object} evt Event
    */
    _submitRating: function(evt) {
        this._toggleLoadingIndicators();
        this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", "true");
        var ratingData = this._rating.split(':');
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            surveyRecordID: ratingData[0],
            answerRecordID: ratingData[1],
            contentRecordID: ratingData[2],
            localeRecordID: this.data.js.locale,
            answerID: this.data.js.answerID,
            ratingPercentage: this._ratingPercentage
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response, args){
                this._toggleLoadingIndicators(false);
                this.Y.one(this.baseSelector + '_ThanksMessage').removeClass('rn_Hidden');
                this._updateAriaAlert(this.Y.one(this.baseSelector + '_ThanksMessage').get('innerHTML'));
                this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('click', this._updateRating, this);
                this.Y.all(this.baseSelector + ' .rn_RatingInput').detach('click', this._updateRating, this);
                this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseenter', this._onMouseOver, this);
                this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseleave', this._onMouseOut, this);
                this.Y.all(this.baseSelector + ' .rn_RatingInput').set('disabled', true);
                this.Y.one(this.baseSelector + '_SubmitButton').set('disabled', true);
            },
            json: true, scope: this
        });
    },

    /**
    * Event handler executed when the rating is clicked
    * @param {Object} evt Event
    */
    _updateRating: function(evt) {
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseleave', this._onMouseOut, this);
        this.Y.one(this.baseSelector + '_SubmitButton').removeAttribute('disabled');
        this._rating = evt.target.getAttribute('data-rating');
        var ratingIndex = evt.target.getAttribute('data-id'),
            maxRating = evt.target.getAttribute('data-maxRating');
        this._ratingPercentage = (maxRating === 2 ? (ratingIndex - 1) / (ratingIndex - 1) : (ratingIndex / maxRating)) * 100;
    },

    /**
    * Method to call on click of rating
    * @param {Object} evt Event
    */
    _selectRating: function(evt) {
        var ratingIndex = evt.target.getAttribute('data-id');
        var ratings = this.Y.all(this.baseSelector + ' .rn_StarRatingInput');
        ratings.removeClass('rn_Selected');
        for(var i = 0; i < ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
    },

    /**
    * Method to call on mouse over event
    * @param {Object} evt Event
    */
    _onMouseOver: function(evt) {
        var ratingIndex = evt.target.getAttribute('data-id');
        var ratings = this.Y.all(this.baseSelector + ' .rn_StarRatingInput');
        ratings.removeClass('rn_Selected');
        for(var i = 0; i < ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on('mouseleave', this._onMouseOut, this);
    },
    
    /**
    * Method to be called on mouse out event
    */
    _onMouseOut: function() {
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').removeClass('rn_Selected');
        this.Y.one(this.baseSelector + '_SubmitButton').set('disabled', true);
    },
    
    /**
     * Hides / shows the loading icon and status message.
     * @param {Boolean=} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F), or toggle their current state (default) (optional)
     */
    _toggleLoadingIndicators: function(turnOn) {
        var classFunc = ((typeof turnOn === "undefined") ? "toggleClass" : ((turnOn === true) ? "removeClass" : "addClass")),
            loading = this.Y.one(this.baseSelector + "_LoadingIcon"),
            message = this.Y.one(this.baseSelector + "_StatusMessage");
        if (loading) {
            loading[classFunc]("rn_Hidden");
        }
        if (message) {
            message[classFunc]("rn_Hidden").setAttribute("aria-live", (message.hasClass("rn_Hidden")) ? "off" : "assertive");
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above document rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
