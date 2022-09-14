RightNow.namespace("Custom.Widgets.eventus.ajaxDonateFormSubmit");
var amt = "";
var rec = "";
Custom.Widgets.eventus.ajaxDonateFormSubmit = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */
  constructor: function () {
    this.parentForm;
    this._formButton = this.Y.one(this.baseSelector + "_Button");
    this._errorMessageDiv = this.Y.one("#rn_ErrorLocation");
    this._formButton.on("click", this._onButtonClick, this);
    this._requestInProgress = false;
    // tabControlContinueButton = tabControl.find("button.CheckoutAssistantContinueButton");
    // tabControlBackButton.click(function(evt){
    // thisObj.handleTabBackButtonClick(evt, tabID);
    // });
    this._toggleClickListener(false);
  },

  /**
   * Sample widget method.
   */
  _onButtonClick: function () {
    if (this.data.attrs.confirm_message) {
      var response = confirm(this.data.attrs.confirm_message);
      if (response == true) {
        this._toggleClickListener(false);
        this.getDefault_ajax_endpoint();
      }
    } else {
      this._toggleClickListener(false);
      this.getDefault_ajax_endpoint();
    }
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
      //process stored payment & New payment
      var amount = "";
      //Fetch amount
      // if (document.getElementById("amount").value == "other") {
      //   amt = document.getElementById("other_amount").value;
      // } else {
      //   amt = document.getElementById("amount").value;
      // }
      if (document.getElementById("onetime_radio").checked) {
        amt = document.getElementById("onetime").innerHTML.split("$")[1];
        rec = "0";
      } else {
        amt = document.getElementById("monthly").innerHTML.split("$")[1];
        rec = "1";
      }
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
          this.disabled = false;
          return;
        }

        if (document.getElementById("cardpay_radio").checked) {
          this.saveCardPaymentMethod({
            CardNum: document
              .querySelector('input[name="cardnumber"]')
              .value.split(" ")
              .join(""),
            ExpMonth: document.querySelector('select[name="expmonth"]').value,
            ExpYear: document.querySelector('select[name="expyear"]').value,
            NameOnCard: document.querySelector('input[name="cardname"]').value,
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
                    ] - 2
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
            Amount: amt,
            monthly: rec,
          });
        } else if (document.getElementById("checkpay_radio").checked) {
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
                    ] - 2
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
            Amount: amt,
            monthly: rec,
          });
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

        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {
          data: {
            w_id: this.data.info.w_id,
            formData: RightNow.JSON.stringify(formData),
            Amount: amt,
            monthly: rec,
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
    }
  },

  validatePaymentMethod: function () {
    var validated;
    if (document.getElementById("cardpay_radio").checked) {
      validated = this.validate([
        document.querySelector('input[name="cardname"]').value,
        document.querySelector('input[name="cardnumber"]').value,
        document.querySelector('select[name="expmonth"]').value,
        document.querySelector('select[name="expyear"]').value,
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
      // let url = "/app/payment/success/t_id/0/authCode/0/";
      // RightNow.Url.navigate(url);
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

  /**
   * Handles the AJAX response for `default_ajax_endpoint`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCard_endpointCallback: function (response, originalEventObj) {
    if (response) {
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

  /**
   * Handles the AJAX response for `default_ajax_endpoint`.
   * @param {object} response JSON-parsed response from the server
   * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
   */
  saveCheck_endpointCallback: function (response, originalEventObj) {
    if (response) {
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
      this.disabled = false;
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
