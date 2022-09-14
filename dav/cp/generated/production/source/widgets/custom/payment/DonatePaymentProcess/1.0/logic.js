RightNow.namespace("Custom.Widgets.payment.DonatePaymentProcess");
Custom.Widgets.payment.DonatePaymentProcess = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */

  constructor: function () {
    window.global_this = this;
    this.Y.one(window).on("load", this._onLoad, this);

    radios = document.forms["storedMethodForm"].elements["paymentMethodId"];
    if (radios) {
      for (var i = 0, max = radios.length; i < max; i++) {
        radios[i].checked = false; // Unset the default selected option for single record.
        radios[i].onclick = function () {
          //hide payment form if stored payment method is selected
          if (document.getElementById("newPaymentForm"))
            document
              .getElementById("newPaymentForm")
              .classList.add("rn_Hidden");
        };
      }
    }
    if (document.getElementById("paymentFormLink"))
      document
        .getElementById("paymentFormLink")
        .addEventListener("click", this._formPayment);
  },

  //function to show new payment form and un select the stored payment method
  _formPayment: function (type, args) {
    if (document.getElementById("paymentMethodId"))
      document.getElementById("paymentMethodId").checked = false;

    if (radios) {
      for (var i = 0, max = radios.length; i < max; i++) {
        radios[i].checked = false;
      }
    }
    document.getElementById("newPaymentForm").classList.remove("rn_Hidden");
  },
  // constructor: function() {
  //     window.global_this=this;
  //     this.Y.one(window).on('load', this._onLoad,this);
  //     document.getElementById("Card").addEventListener("click", this._cardPayment);
  //     document.getElementById("Bank").addEventListener("click", this._bankPayment);
  // },

  // _cardPayment: function(type,args) {
  //     document.getElementById("checkpay").classList.add("rn_Hidden");
  //     document.getElementById("cardpay").classList.remove("rn_Hidden");
  // },

  // _bankPayment: function(type,args) {
  //     document.getElementById("checkpay").classList.remove("rn_Hidden");
  //     document.getElementById("cardpay").classList.add("rn_Hidden");
  // },
  // _onLoad: function(evt, args){
  //     document.getElementById("checkpay").classList.add("rn_Hidden");
  // },
  /**
   * Sample widget method.
   */
  methodName: function () {},
});
