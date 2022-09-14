RightNow.namespace("Custom.Widgets.eventus.pledgepayment");
Custom.Widgets.eventus.pledgepayment = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */
  constructor: function () {
    this._amountSelector = $(this.baseSelector + "_amountSelector");
    this._paymentButton = $(this.baseSelector + "_makePayment");
    this.errorLocation = $(this.baseSelector + "_ErrorLocation");
    this._readyForCheckout = false;

    if (this._paymentButton)
      this._paymentButton.on("click", $.proxy(this._paymentClicked, this));
  },

  _paymentClicked: function () {
    //is this the first time the

    this.paymentVal = $(
      "input[name=" + this.baseDomID + "_pledgepayamount]:checked"
    ).val();
    if (this.paymentVal == "other") {
      this.paymentVal = $(this.baseSelector + "_pledgeotheramount").val();
    }

    if (typeof this.paymentVal != "undefined") {
      this.paymentVal = this._cleanAndVerifyAmount(this.paymentVal);
      if (this.paymentVal) {
        this._paymentButton.attr("disabled", "disabled");
        this._paymentButton.addClass("loading");
        //write the session and move to cart
        var success = this.writeSessionAndNavigate(this.paymentVal);
      }
    } else {
      this._amountSelector.removeClass("rn_Hidden");
      this._paymentButton.html(this.data.attrs.label_payment);
      console.log("value");
    }
  },

  _cleanAndVerifyAmount: function (val) {
    var errorMsg = "";
    val = val.replace("$", "");

    //make sure they are entering a whole dollar amount
    var regex = /^[0-9]+(?:\.[0-9]{1,2})?$/;
    if (!regex.test(val)) {
      errorMsg = "Please Enter a Valid Amount";
    } else {
      var sections = val.split(".");
      if (parseInt(sections[1]) > 0) {
        errorMsg = "Please Enter a Whole Dollar Amount";
      }
    }

    if (errorMsg != "") {
      this.errorLocation.removeClass("rn_Hidden").html(errorMsg);
      return false;
    } else {
      return val;
    }
  },

  writeSessionAndNavigate: function (val) {
    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        pledgeAmount: val,
        pledgeId: $(this.baseSelector + "_pledge_id").val(),
      },
    });

    RightNow.Ajax.makeRequest(
      this.data.attrs.setsessionforpledge,
      eventObj.data,
      {
        successHandler: this.navigateToCart,
        scope: this,
        data: eventObj,
        json: true,
      }
    );
  },

  navigateToCart: function (args) {
    if (args.message == "Success" && args.result.redirectOverride) {
      window.location = args.result.redirectOverride;
    }
  },
});
