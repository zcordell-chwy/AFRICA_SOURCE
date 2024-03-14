RightNow.namespace("Custom.Widgets.input.SponsorshipSubmit");
Custom.Widgets.input.SponsorshipSubmit = RightNow.Widgets.FormSubmit.extend({
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
            this._navigateToUrlFlag=true;
            this.getDefault_ajax_endpoint();
          } else {
            this._navigateToUrlFlag=false;
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

  getDefault_ajax_endpoint: function () {
    // if (this._errorMessageDiv)
    //     this._errorMessageDiv.addClass("rn_Hidden").set("innerHTML", "");
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
      //process stored payment & New payment
      var amount = "";
      if (
        (document.getElementById("cardpay_radio").checked ||
          document.getElementById("checkpay_radio").checked) &&
        (document.querySelector('input[name="cardnumber"]').value.length > 1 ||
          document.querySelector('input[name="accountnumber"]').value.length >
            1)
      ) {
        console.log("New Payment Process");
        let validateError = this.validatePaymentMethod();
        if (validateError) {
          $("#paymentMethodError").text(validateError);
          //this.disabled = false;
this._navigateToUrlFlag=false;
this._resetFormButton();

          return;
        }

        if (document.getElementById("cardpay_radio").checked) {
          if(this.data.js.loggedin != false) {
            this.saveCardPaymentMethod({
              CardNum: document
                .querySelector('input[name="cardnumber"]')
                .value.split(" ")
                .join(""),
              CVNum: document.querySelector('input[name="cvnumber"]').value,
              ExpMonth: document.querySelector('select[name="expmonth"]').value,
              ExpYear: document.querySelector('select[name="expyear"]').value,
              NameOnCard: document.querySelector('input[name="cardname"]').value
            });
          } else  {
          this.saveCardPaymentMethod({
            CardNum: document
              .querySelector('input[name="cardnumber"]')
              .value.split(" ")
              .join(""),
            ExpMonth: document.querySelector('select[name="expmonth"]').value,
            ExpYear: document.querySelector('select[name="expyear"]').value,
            NameOnCard: document.querySelector('input[name="cardname"]').value,
            CVNum: document.querySelector('input[name="cvnumber"]').value,
            City: document.getElementsByName("Contact.Address.City")[
              document.getElementsByName("Contact.Address.City")["length"] - 1
            ].value,
            Zip: document.getElementsByName("Contact.Address.PostalCode")[
              document.getElementsByName("Contact.Address.PostalCode")[
                "length"
              ] - 1
            ].value,
            Street: document.getElementsByName("Contact.Address.Street")[
              document.getElementsByName("Contact.Address.Street")["length"] - 1
            ].value,
            Emails: document.getElementsByName(
              "Contact.Emails.PRIMARY.Address"
            )[
              document.getElementsByName("Contact.Emails.PRIMARY.Address")[
                "length"
              ] - 1
            ].value,
            CFName:
              document.getElementsByName("Contact.Name.First")[
                document.getElementsByName("Contact.Name.First")["length"] - 1
              ].value,
            CLName:
              document.getElementsByName("Contact.Name.Last")[
                document.getElementsByName("Contact.Name.Last")["length"] - 1
              ].value,
            CPass:
              document.getElementsByName("Contact.NewPassword")["length"] > 1
                ? document.getElementsByName("Contact.NewPassword")[
                    document.getElementsByName("Contact.NewPassword")[
                      "length"
                    ] - 1
                  ].value
                : "",
            State: document.getElementsByName(
              "Contact.Address.StateOrProvince"
            )[
              document.getElementsByName("Contact.Address.StateOrProvince")[
                "length"
              ] - 1
            ].value,
            Country: document.getElementsByName("Contact.Address.Country")[
              document.getElementsByName("Contact.Address.Country")["length"] -
                1
            ].value,
            AboutUS: document.getElementsByName(
              "Contact.CustomFields.CO.how_did_you_hear"
            )[
              document.getElementsByName(
                "Contact.CustomFields.CO.how_did_you_hear"
              )["length"] - 1
            ].value,
          });
        }
        } else if (document.getElementById("checkpay_radio").checked) {
          if(this.data.js.loggedin != false) {
            this.saveCardPaymentMethod({
              TransitNum: document.querySelector('input[name="routingnumber"]')
              .value,
            AccountNum: document.querySelector('input[name="accountnumber"]')
              .value,
            NameOnCheck: document.querySelector('input[name="checkname"]')
              .value,
            AccountType: document.querySelector('select[name="accounttype"]')
              .value,
            });
          } else  {
          this.saveCheckPaymentMethod({
            TransitNum: document.querySelector('input[name="routingnumber"]')
              .value,
            AccountNum: document.querySelector('input[name="accountnumber"]')
              .value,
            NameOnCheck: document.querySelector('input[name="checkname"]')
              .value,
            AccountType: document.querySelector('select[name="accounttype"]')
              .value,
            City: document.getElementsByName("Contact.Address.City")[
              document.getElementsByName("Contact.Address.City")["length"] - 1
            ].value,
            Zip: document.getElementsByName("Contact.Address.PostalCode")[
              document.getElementsByName("Contact.Address.PostalCode")[
                "length"
              ] - 1
            ].value,
            Street: document.getElementsByName("Contact.Address.Street")[
              document.getElementsByName("Contact.Address.Street")["length"] - 1
            ].value,
            Emails: document.getElementsByName(
              "Contact.Emails.PRIMARY.Address"
            )[
              document.getElementsByName("Contact.Emails.PRIMARY.Address")[
                "length"
              ] - 1
            ].value,
            CFName:
              document.getElementsByName("Contact.Name.First")[
                document.getElementsByName("Contact.Name.First")["length"] - 1
              ].value,
            CLName:
              document.getElementsByName("Contact.Name.Last")[
                document.getElementsByName("Contact.Name.Last")["length"] - 1
              ].value,
            CPass:
              document.getElementsByName("Contact.NewPassword")["length"] > 1
                ? document.getElementsByName("Contact.NewPassword")[
                    document.getElementsByName("Contact.NewPassword")[
                      "length"
                    ] - 1
                  ].value
                : "",
            State: document.getElementsByName(
              "Contact.Address.StateOrProvince"
            )[
              document.getElementsByName("Contact.Address.StateOrProvince")[
                "length"
              ] - 1
            ].value,
            Country: document.getElementsByName("Contact.Address.Country")[
              document.getElementsByName("Contact.Address.Country")["length"] -
                1
            ].value,
            AboutUS: document.getElementsByName(
              "Contact.CustomFields.CO.how_did_you_hear"
            )[
              document.getElementsByName(
                "Contact.CustomFields.CO.how_did_you_hear"
              )["length"] - 1
            ].value,
          });
          }
        }
      } else {
        formNode = this._formButton.ancestor("form");
        inputArea = "#" + formNode._node.id + " input";
        $.each($(inputArea), function (key, value) {
          formData[formData.length] = {
            name: value.name,
            value: value.value,
            checked: value.checked,
          };
        });

        if(!document.getElementById("cardpay2").classList.contains('rn_Hidden')){
          let validateError = this.validatePaymentMethodcvv();
          if (validateError) {
            $("#paymentMethodError").text(validateError);
            //$("#rn_ErrorLocation").text(validateError);
            //this.disabled = false;
this._navigateToUrlFlag=false;
		this._resetFormButton();

            return;
          }
        }

        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {
          data: {
            w_id: this.data.info.w_id,
            formData: RightNow.JSON.stringify(formData),
          },
        });

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
    }
  },

  /**
   * Deferred method that Performs an AJAX call to determine if the child is still unsponsored.
   * @param {integer} childID the ID of the child to check for sponsorship
   * @param deferred resolve, if the child record is unsponsored, otherwise a deferred reject
   */
  isChildUnsponsored: function (childID) {
    var dfd = $.Deferred();

    if (!this.data.attrs.check_for_lock) {
      dfd.resolve();
      return dfd.promise();
    }

    $.ajax({
      type: "GET",
      url: "/ci/AjaxCustom/isChildSponsored/" + childID,
      success: function (status) {
        if (status.isSponsored) dfd.reject();
        else dfd.resolve();
      },
      dataType: "json",
    });

    return dfd.promise();
  },

  /**
   * Deferred method that performs an AJAX call to lock a child record (reserve it for a single user's sponsorship transaction).
   * The purpose of this function is to prevent the scenario where two user's unknowingly sponsor the same child.
   * @param {integer} childID the ID of the child to lock
   * @param deferred resolve, if the child record was locked successfully, otherwise a deferred reject
   */
  lockChildRecord: function (childID) {
    var dfd = $.Deferred();

    if (!this.data.attrs.check_for_lock) {
      dfd.resolve(); //just say we locked it
      return dfd.promise();
    }

    $.ajax({
      type: "GET",
      url: "/ci/AjaxCustom/lockChildRecord/" + childID,
      success: function (status) {
        if (status.status == "success") dfd.resolve();
        else dfd.reject();
      },
      dataType: "json",
    });

    return dfd.promise();
  },

  /**
   * Deferred method that Performs an AJAX call to determine if the unsponsored child record is locked (already in another user's
   * transaction for sponsorship). The purpose of this function is to prevent the scenario where two user's
   * unknowingly sponsor the same child.
   * @param {integer} childID the ID of the child to check for a record lock on
   * @param deferred resolve, if the child record is not locked, otherwise a deferred reject
   */
  isChildRecordLocked: function (childID) {
    var dfd = $.Deferred();

    if (!this.data.attrs.check_for_lock) {
      dfd.resolve();
      return dfd.promise();
    }

    $.ajax({
      type: "GET",
      url: "/ci/AjaxCustom/isChildRecordLocked/" + childID,
      success: function (status) {
        if (status.isLocked) dfd.reject();
        else dfd.resolve();
      },
      dataType: "json",
    });

    return dfd.promise();
  },
  validatePaymentMethodcvv: function () {
    var validated;
    if(!document.getElementById("cardpay2").classList.contains('rn_Hidden')){
    validated = this.validate([document.querySelector('input[name="cvnumber2"]').value]);
    
    
    if (
      !/^\d+$/.test(
        document
          .querySelector('input[name="cvnumber2"]')
          .value.split(" ")
          .join("")
      )
    ) {
      validated =
        "Please input cvv number";
    }
  
}
    return validated;

  },

  validatePaymentMethod: function () {
    var validated;
    if (document.getElementById("cardpay_radio").checked) {
      validated = this.validate([
        document.querySelector('input[name="cardname"]').value,
        document.querySelector('input[name="cardnumber"]').value,
        document.querySelector('select[name="expmonth"]').value,
        document.querySelector('select[name="expyear"]').value,
        document.querySelector('input[name="cvnumber"]').value,
        document.getElementsByName("Contact.Name.First")[
          document.getElementsByName("Contact.Name.First")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Name.Last")[
          document.getElementsByName("Contact.Name.Last")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Emails.PRIMARY.Address")[
          document.getElementsByName("Contact.Emails.PRIMARY.Address")[
            "length"
          ] - 1
        ].value,
        document.getElementsByName("Contact.Address.Country")[
          document.getElementsByName("Contact.Address.Country")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Address.PostalCode")[
          document.getElementsByName("Contact.Address.PostalCode")["length"] - 1
        ].value,
      ]);
    } else if (document.getElementById("checkpay_radio").checked) {
      validated = this.validate([
        document.querySelector('input[name="checkname"]').value,
        document.querySelector('input[name="accountnumber"]').value,
        document.querySelector('input[name="routingnumber"]').value,
        document.querySelector('select[name="accounttype"]').value,
        document.getElementsByName("Contact.Name.First")[
          document.getElementsByName("Contact.Name.First")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Name.Last")[
          document.getElementsByName("Contact.Name.Last")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Emails.PRIMARY.Address")[
          document.getElementsByName("Contact.Emails.PRIMARY.Address")[
            "length"
          ] - 1
        ].value,
        document.getElementsByName("Contact.Address.Country")[
          document.getElementsByName("Contact.Address.Country")["length"] - 1
        ].value,
        document.getElementsByName("Contact.Address.PostalCode")[
          document.getElementsByName("Contact.Address.PostalCode")["length"] - 1
        ].value,
      ]);
    }
    if (!validated && document.getElementById("checkpay_radio").checked) {
      if (document.getElementById("checkpay_radio").checked) {
        if (
          !/^\d+$/.test(
            document.querySelector('input[name="accountnumber"]').value
          ) ||
          document.querySelector('input[name="accountnumber"]').value.length >
            17
        ) {
          validated = "Input a valid account number.";
        } else if (
          document.getElementById("checkpay_radio").checked &&
          (!/^\d+$/.test(
            document.querySelector('input[name="routingnumber"]').value
          ) ||
            document.querySelector('input[name="routingnumber"]').value
              .length != 9)
        ) {
          validated = "Input a valid routing number.";
        }
      }
    } else if (!validated && document.getElementById("cardpay_radio").checked) {
      if (
        !/^\d+$/.test(
          document
            .querySelector('input[name="cardnumber"]')
            .value.split(" ")
            .join("")
        )
      ) {
        validated =
          "Please input a properly formated credit card: 1111222233334444 / 1111 2222 3333 4444.";
      }
     if (
        !/^\d+$/.test(
          document
            .querySelector('input[name="cvnumber"]')
            .value.split(" ")
            .join("")
        )
      ) {
        validated =
          "Please input cvv number";
      }
    } else {
      validated = "Make sure all fields are filled.";
    }

    return validated;
  },

  validate: function (array, offset = 0) {
    var allFilled =
      array[offset] === null ||
      array[offset] === undefined ||
      array[offset] === 0 ||
      array[offset] === "" ||
      array[offset] === false ||
      array[offset] === NaN;

    return offset + 1 < array.length
      ? allFilled || this.validate(array, offset + 1)
      : allFilled;
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
      $(this.baseSelector + "_LoadingIcon").addClass("rn_Hidden");
      this._errorMessageDiv.append(errorMessage);
      this._errorMessageDiv.removeClass("rn_Hidden");
    } else {
      //pop error message
      //this._displayErrorDialog(RightNow.Interface.getMessage('CUSTOM_MSG_PAY_SERVICE_ERROR'));
      //this._toggleClickListener(true);

      //redirect to success page and let donor know their payment has been queued and they should get
      //an email receipt in 1-2 hours.
      //app/payment/successCC/t_id/0/authCode/0/
      let url = "/app/payment/success/t_id/0/authCode/0/";
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
      this._navigateToUrlFlag=false;
      // Didn't get any kind of a response object back; that's... unexpected.
      this._displayErrorDialog(
        RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG")
      );
    } else if (response.errors) {
      // Error message(s) on the response object.
      this._navigateToUrlFlag=false;
      var errorMessage = "";
      this.Y.Array.each(response.errors, function (error) {
        errorMessage += "<b>" + error + "</b>";
      });
      errorMessage += "</br> Please refresh the page and try again.";
      $(this.baseSelector + "_LoadingIcon").addClass("rn_Hidden");
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
        this._navigateToUrlFlag=false;
        // Response object with a result, but not the result we expect.
        this._displayErrorDialog();
      }
    } else {
      this._navigateToUrlFlag=false;
      // Response object didn't have a result or errors on it.
      this._displayErrorDialog();
    }
    if (response.result != null && response.result.newFormToken) {
      this.data.js.f_tok = result.newFormToken;
      RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.result.newFormToken}}));}

     this._resetFormButton();
	//this._toggleClickListener(true);
    return;
  },

  /**
   * Handles the AJAX response for `default_ajax_endpoint`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCard_endpointCallback: function (response, originalEventObj) {
    if (response && !response.errors) {
      var res = JSON.parse(response.response);
      response = res;
    }
    if (!response) {
      this._navigateToUrlFlag=false;
      // Didn't get any kind of a response object back; that's... unexpected.
      this._displayErrorDialog(
        RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG")
      );
    } else if (response.errors) {
      this._navigateToUrlFlag=false;
      // Error message(s) on the response object.
      var errorMessage = "";
      this.Y.Array.each(response.errors, function (error) {
        errorMessage += "<b>" + error + "</b>";
      });
      errorMessage += "</br> Please refresh the page and try again.";
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
        this._navigateToUrlFlag=false;
        // Response object with a result, but not the result we expect.
        this._displayErrorDialog();
      }
    } else {
      this._navigateToUrlFlag=false;
      // Response object didn't have a result or errors on it.
      this._displayErrorDialog();
    }
    if (response.result != null && response.result.newFormToken) {
      this.data.js.f_tok = result.newFormToken;
      RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.result.newFormToken}}));}

     this._resetFormButton();
	//this._toggleClickListener(true);
    return;
  },

  /**
   * Handles the AJAX response for `default_ajax_endpoint`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCheck_endpointCallback: function (response, originalEventObj) {
    if (response && !response.errors) {
      var res = JSON.parse(response.response);
      response = res;
    }
    if (!response) {
      // Didn't get any kind of a response object back; that's... unexpected.
      this._displayErrorDialog(
        RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG")
      );
    } else if (response.errors) {
      // Error message(s) on the response object.
      var errorMessage = "";
      this.Y.Array.each(response.errors, function (error) {
        errorMessage += "<b>" + error + "</b>";
      });
      errorMessage += "</br> Please refresh the page and try again.";
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
    if (response.result != null && response.result.newFormToken) {
      this.data.js.f_tok = response.result.newFormToken;
      RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.result.newFormToken}}));}

     this._toggleClickListener(true);
    return;
  },

  /**
   * Handles the AJAX response for `save_card_payment_method`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCardPaymentMethod: function (data) {
    // const params = new URLSearchParams(data);
    // console.log(params.toString());

    //Make AJAX request:
    var eventObj = new RightNow.Event.EventObject(this, {
      data: data,
    });

    RightNow.Ajax.makeRequest(
      this.data.attrs.save_card_payment_method,
      eventObj.data,
      {
        successHandler: this.saveCard_endpointCallback,
        failureHandler: this.ajaxFailed,
        scope: this,
        data: eventObj,
        json: false,
      }
    );
  },

  /**
   * Handles the AJAX response for `save_card_payment_method`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCheckPaymentMethod: function (data) {
    // const params = new URLSearchParams(data);
    // console.log(params.toString());

    //Make AJAX request:
    var eventObj = new RightNow.Event.EventObject(this, {
      data: data,
    });

    RightNow.Ajax.makeRequest(
      this.data.attrs.save_check_payment_method,
      eventObj.data,
      {
        successHandler: this.saveCheck_endpointCallback,
        failureHandler: this.ajaxFailed,
        scope: this,
        data: eventObj,
        json: false,
      }
    );
  },

  ajaxCallback: function (response, originalEventObj) {
    if (response.code != "success") {
      $("#paymentMethodError").text(response.message);
      //this.disabled = false;
this._navigateToUrlFlag=false;
this._resetFormButton();

    } else {
      this.closeDialog();
      this.successCallback(response, originalEventObj);
    }
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
