RightNow.namespace("Custom.Widgets.eventus.managepaymethods");
Custom.Widgets.eventus.managepaymethods = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */
  constructor: function () {
    this.newPaymentForm = this.Y.one("#newPaymentForm");

    this.cardPaymentType = this.Y.one("#cardpay");
    this.checkPaymentType = this.Y.one("#checkpay");

    this.formPaymentType = this.Y.one("#paymenttype");
    this.formPaymentType.on("change", this.changePaymentType, this);

    this.newPaymentLink = this.Y.one("#newPaymentLink");
    this.newPaymentLink.on("click", this.onAddNewPayLinkClicked, this);
    this.disabled = true;
  },

  onAddNewPayLinkClicked: function () {
    this.newPaymentFormDialog ||
      (this.newPaymentFormDialog = this.createDialog());

    this.newPaymentFormDialog.show();

    RightNow.UI.Dialog.enableDialogControls(
      this.newPaymentFormDialog,
      this.dialogKeyListener
    );

    this.disabled = false;
  },

  createDialog: function () {
    var newPaymentFormDialog = RightNow.UI.Dialog.actionDialog(
      this.data.attrs.title,
      this.newPaymentForm,
      {
        buttons: [
          {
            text: "Submit",
            handler: {
              fn: this.onSubmit,
              scope: this,
            },
            name: "submit",
          },
          {
            text: "Cancel",
            handler: {
              fn: this.onCancel,
              scope: this,
              href: "javascript:void(0)",
            },
          },
        ],
      }
    );

    // Set up keylistener for <enter> to run onSubmit()
    this.dialogKeyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(
      newPaymentFormDialog,
      this.savePaymentMethod,
      this
    );

    newPaymentFormDialog.validate = this.validatePaymentMethod;

    if (RightNow.Env("module") === "standard") {
      //Perform dialog close cleanup if the [x] cancel button or esc is used
      //(only standard page set has [x] or uses esc button)
      newPaymentFormDialog.cancelEvent.subscribe(this.onCancel, null, this);
    }

    RightNow.UI.show(this.newPaymentForm);

    return newPaymentFormDialog;
  },

  validatePaymentMethod: function () {
    // Validate Form Data.

    var data = Object.fromEntries(
      new FormData(document.getElementById("newPaymentForm")).entries()
    );
    var validated;

    switch (data.paymenttype) {
      case "card":
        validated = this.validate([
          data.cardname,
          data.cardnumber,
          data.expmonth,
          data.expyear,
        ]);

        if (!validated) {
          if (!/^\d+$/.test(data.cardnumber.split(" ").join(""))) {
            validated =
              "Please input a properly formated credit card: 1111222233334444 / 1111 2222 3333 4444.";
          }
        } else {
          validated = "Make sure all fields are filled.";
        }
        break;
      case "check":
        validated = this.validate([
          data.checkname,
          data.accountnumber,
          data.routingnumber,
          data.accounttype,
        ]);

        if (!validated) {
          if (
            !/^\d+$/.test(data.accountnumber) ||
            data.accountnumber.length > 17
          ) {
            validated = "Input a valid account number.";
          } else if (
            !/^\d+$/.test(data.routingnumber) ||
            data.routingnumber.length != 9
          ) {
            validated = "Input a valid routing number.";
          }
        } else {
          validated = "Make sure all fields are filled.";
        }
        break;
      default:
        validated = "Select Payment Type";
        break;
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

  onSubmit: function (event, args) {
    if (this.disabled == true) return;
    this.disabled = true;

    let validateError = this.validatePaymentMethod();
    if (validateError) {
      $("#paymentMethodError").text(validateError);
      this.disabled = false;
      return;
    }

    var data = Object.fromEntries(
      new FormData(document.getElementById("newPaymentForm")).entries()
    );

    switch (data.paymenttype) {
      case "card":
        this.saveCardPaymentMethod({
          CardNum: data.cardnumber.split(" ").join(""),
          ExpMonth: data.expmonth,
          ExpYear: data.expyear,
          NameOnCard: data.cardname,
        });
        break;
      case "check":
        this.saveCheckPaymentMethod({
          TransitNum: data.routingnumber,
          AccountNum: data.accountnumber,
          NameOnCheck: data.checkname,
          AccountType: data.accounttype,
        });
        break;
    }
  },

  onCancel: function (event, args) {
    if (this.disabled == true) return;

    this.closeDialog();

    $("#newPaymentForm")[0].reset();
    $("#paymentMethodError").text("");
    RightNow.UI.hide(this.cardPaymentType);
    RightNow.UI.hide(this.checkPaymentType);
  },

  changePaymentType: function (event, args) {
    RightNow.UI.hide(this.cardPaymentType);
    RightNow.UI.hide(this.checkPaymentType);

    switch (event._event.target.value) {
      case "card":
        RightNow.UI.show(this.cardPaymentType);
        break;
      case "check":
        RightNow.UI.show(this.checkPaymentType);
        break;
    }
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
        successHandler: this.ajaxCallback,
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
        successHandler: this.ajaxCallback,
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

  successCallback: function (response, originalEventObj) {
    // var eventObject = new RightNow.Event.EventObject(this, {data: response});
    // RightNow.Event.fire("evt_paymentMethodAdded", eventObject);
    return Function(
      "response",
      "originalEventObj",
      this.data.attrs.success_callback
    )(response, originalEventObj);
  },

  closeDialog: function () {
    this.disabled = true;
    RightNow.UI.Dialog.disableDialogControls(
      this.newPaymentFormDialog,
      this.dialogKeyListener
    );
    this.newPaymentFormDialog.hide();
  },
});
