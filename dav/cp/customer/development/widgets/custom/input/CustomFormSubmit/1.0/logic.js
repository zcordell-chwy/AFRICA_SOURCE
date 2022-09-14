RightNow.namespace("Custom.Widgets.input.CustomFormSubmit");
Custom.Widgets.input.CustomFormSubmit = RightNow.Widgets.FormSubmit.extend({
  /**
   * Place all properties that intend to
   * override those of the same name in
   * the parent inside `overrides`.
   */
  overrides: {
    /**
     * Overrides RightNow.Widgets.FormSubmit#constructor.
     */
    constructor: function () {
      // Call into parent's constructor
      this.parent();
    },
    _onFormValidated: function () {
      var response = confirm(this.data.attrs.confirm_message);
      if (response == true) {
        this.parent();
      }
    },
    _formSubmitResponse: function (tye, args) {
      console.log(args);
      var responseObject = args[0].data,
        result;

      if (
        !this._handleFormResponseFailure(responseObject) &&
        responseObject.result
      ) {
        result = responseObject.result;

        // Don't process a SmartAssistant response.
        if (!result.sa) {
          if (result.transaction || result.redirectOverride) {
            // return this._handleFormResponseSuccess(result);
            // alert("this is where I process payment");
            this._toggleClickListener(false);
            this.getDefault_ajax_endpoint();
          } else {
            // Response object has a result, but not a result we expect.
            this._displayErrorDialog();
          }
        }
      }

      args[0].data || (args[0].data = {});
      args[0].data.form = this._parentForm;
      // RightNow.Event.fire('evt_formButtonSubmitResponse', args[0]);
    },
    /**
     * Overridable methods from FormSubmit:
     *
     * Call `this.parent()` inside of function bodies
     * (with expected parameters) to call the parent
     * method being overridden.
     */
    // _onButtonClick: function(evt)
    // _fireSubmitRequest: function()
    // _onFormValidated: function()
    // _onFormValidationFail: function()
    // _clearFlashData: function()
    // _absoluteOffset: function(element)
    // _displayErrorMessages: function(messageArea)
    // _defaultFormSubmitResponse: function(type, args)
    // _formSubmitResponse: function(type, args)
    // _handleFormResponseSuccess: function(result)
    // _handleFormResponseFailure: function(responseObject)
    // _navigateToUrl: function(result)
    // _confirmOnNavigate : function(result)
    // fn: function()
    // _resetFormForSubmission: function()
    // _onFormUpdated: function()
    // _onErrorResponse: function(response)
    // _resetFormButton: function()
    // _removeFormErrors: function()
    // _displayErrorDialog: function(message)
    // _toggleLoadingIndicators: function(turnOn)
    // _toggleClickListener: function(enable)
  },
  /**
   * Makes an AJAX request for `default_ajax_endpoint`.
   */
  getDefault_ajax_endpoint: function () {
    if (this._errorMessageDiv)
      this._errorMessageDiv.addClass("rn_Hidden").set("innerHTML", "");

    var formData = [];
    var formNode;

    var endpoint = this.data.attrs.default_ajax_endpoint;
    inputArea = "#storedMethodForm input";

    if (this.data.attrs.formname == "changePayMethod") {
      //change a payment method
      formNode = this._formButton.ancestor("form");

      inputArea = "#" + formNode._node.id + " input";

      $.each($(inputArea), function (key, value) {
        formData[formData.length] = {
          name: value.name,
          value: value.value,
          checked: value.checked,
        };
      });

      // Make AJAX request:
      var eventObj = new RightNow.Event.EventObject(this, {
        data: {
          w_id: this.data.info.w_id,
          formData: RightNow.JSON.stringify(formData),
          // Parameters to send
        },
      });
      RightNow.Ajax.makeRequest(
        this.data.attrs.changepaymethod_ajax_endpoint,
        eventObj.data,
        {
          successHandler: this.default_ajax_endpointCallback,
          scope: this,
          data: eventObj,
          json: true,
        }
      );
    } else if (this.data.attrs.formname == "deletePayMethod") {
      //delete a payment method

      formNode = this._formButton.ancestor("form");
      inputArea = "#" + formNode._node.id + " input";

      $.each($(inputArea), function (key, value) {
        formData[formData.length] = {
          name: value.name,
          value: value.value,
          checked: value.checked,
        };
      });

      // Make AJAX request:
      var eventObj = new RightNow.Event.EventObject(this, {
        data: {
          w_id: this.data.info.w_id,
          formData: RightNow.JSON.stringify(formData),
          // Parameters to send
        },
      });
      RightNow.Ajax.makeRequest(
        this.data.attrs.deletepaymethod_ajax_endpoint,
        eventObj.data,
        {
          successHandler: this.default_ajax_endpointCallback,
          scope: this,
          data: eventObj,
          json: true,
        }
      );
    } else {
      //process stored payment

      $.each($(inputArea), function (key, value) {
        formData[formData.length] = {
          name: value.name,
          value: value.value,
          checked: value.checked,
        };
      });

      // Make AJAX request:
      var eventObj = new RightNow.Event.EventObject(this, {
        data: {
          w_id: this.data.info.w_id,
          formData: RightNow.JSON.stringify(formData),
          // Parameters to send
        },
      });

      //set loading
      $(this.baseSelector + "_LoadingIcon").removeClass("rn_Hidden");

      //testing timeout link
      //this.data.attrs.default_ajax_endpoint = "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/testpost.php";
      RightNow.Ajax.makeRequest(
        this.data.attrs.default_ajax_endpoint,
        eventObj.data,
        {
          successHandler: this.default_ajax_endpointCallback,
          failureHandler: this.ajaxFailed,
          timeout: 10000, //10 seconds
          scope: this,
          data: eventObj,
          json: true,
        }
      );
    }
  },

  /**
   * Handles the AJAX response timeout.
   *
   */
  ajaxFailed: function (response, originalEventObj) {
    if (response.errors) {
      // Error message(s) on the response object.
      var errorMessage = "";
      this.Y.Array.each(response.errors, function (error) {
        errorMessage += "<div><b>" + error + "</b></div>";
      });
      this._errorMessageDiv.append(errorMessage);
      this._errorMessageDiv.removeClass("rn_Hidden");
    } else {
      //pop error message
      //this._displayErrorDialog(RightNow.Interface.getMessage('CUSTOM_MSG_PAY_SERVICE_ERROR'));
      //this._toggleClickListener(true);

      //redirect to success page and let donor know their payment has been queued and they should get
      //an email receipt in 1-2 hours.
      //app/payment/successCC/t_id/0/authCode/0/
      let url = "/app/payment/successCC/t_id/0/authCode/0/";
      RightNow.Url.navigate(url);
    }

    $(this.baseSelector + "_LoadingIcon").addClass("rn_Hidden");
  },

  /**
   * Handles the AJAX response for `default_ajax_endpoint`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  default_ajax_endpointCallback: function (response, originalEventObj) {
    if (!response) {
      // Didn't get any kind of a response object back; that's... unexpected.
      this._displayErrorDialog(
        RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG")
      );
    } else if (response.errors) {
      // Error message(s) on the response object.
      var errorMessage = "";
      this.Y.Array.each(response.errors, function (error) {
        errorMessage += "<div><b>" + error + "</b></div>";
      });
      this._errorMessageDiv.append(errorMessage);
      this._errorMessageDiv.removeClass("rn_Hidden");
    } else if (response.result) {
      result = response.result;

      if (result.sa) {
        // trap SmartAssistant™ case here
        if (result.newFormToken) {
          // Check if a new form token was passed back and use it the next time the the form is submitted
          this.data.js.f_tok = result.newFormToken;
          RightNow.Event.fire(
            "evt_formTokenUpdate",
            new RightNow.Event.EventObject(this, {
              data: { newToken: result.newFormToken },
            })
          );
        }
      } else if (result.redirectOverride) {
        // success

        var url;

        if (result.redirectOverride) {
          url = result.redirectOverride;
        }

        RightNow.Url.navigate(url);
      } else {
        // Response object with a result, but not the result we expect.
        this._displayErrorDialog();
      }
    } else {
      // Response object didn't have a result or errors on it.
      this._displayErrorDialog();
    }

    this._toggleClickListener(true);
    return;
  },

  _displayErrorDialog: function (message) {
    RightNow.UI.Dialog.messageDialog(
      message || RightNow.Interface.getMessage("ERROR_PAGE_PLEASE_S_TRY_MSG"),
      { icon: "WARN" }
    );
  },

  _toggleClickListener: function (enable) {
    this._formButton.set("disabled", !enable);
    this._requestInProgress = !enable;
    this.Y.Event[enable ? "attach" : "detach"](
      "click",
      this._onButtonClick,
      this._formButton,
      this
    );
  },
});
