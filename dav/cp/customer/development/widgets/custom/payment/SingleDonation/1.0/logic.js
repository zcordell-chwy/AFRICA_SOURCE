RightNow.namespace("Custom.Widgets.payment.SingleDonation");
var monthly = null;
var oneTime = null;
var frequency = null;
Custom.Widgets.payment.SingleDonation = RightNow.Widgets.extend({
  /**
   * Widget constructor.
   */
  constructor: function () {
    window.global_this = this;
    document
      .getElementById("amount")
      .addEventListener("click", this._selectedOther);
    // document
    //   .getElementById("range")
    //   .addEventListener("click", this._selectedRange);
    monthly = this.data.js.DefaultMonthlyAmount;
    oneTime = this.data.js.DefaultOneTimeAmount;

    frequency = this.data.js.frequency;

    document
      .getElementById("monthly_radio")
      .addEventListener("click", this._selectedMonthly);

    document
      .getElementById("amount_other")
      .addEventListener("click", this._otherAmountChecked);

    document
      .getElementById("onetime_radio")
      .addEventListener("click", this._selectedRadio);

    document
      .getElementById("amount_25")
      .addEventListener("click", this._selectedAmountRadio);

    document
      .getElementById("amount_50")
      .addEventListener("click", this._selectedAmountRadio);

    document
      .getElementById("amount_100")
      .addEventListener("click", this._selectedAmountRadio);

    document
      .getElementById("other_amount")
      .addEventListener("keyup", this._otherAmount);

    this.Y.one(window).on("load", this._onLoad, this);
  },

  _otherAmountChecked: function () {
    if (document.getElementById("amount_other").checked) {
     
      $("#other_amount").removeClass("rn_Hidden");
      if(document.getElementById("other_amount").value >0){ 
        $("#onetime").text("$" + document.getElementById("other_amount").value );   
	$("#giftAmount").text( "$" + document.getElementById("other_amount").value);
	$("#monthly").text("$" + document.getElementById("other_amount").value);
	}
      else{
      	$("#onetime").text("$0");
      	$("#giftAmount").text("$0");
     	 $("#monthly").text("$0");
      }
      $("#amount_other").closest("label").addClass("sliderAmount");
      $("#amount_50").closest("label").removeClass("sliderAmount");
      $("#amount_100").closest("label").removeClass("sliderAmount");
      $("#amount_25").closest("label").removeClass("sliderAmount");
    } else {
      $("#other_amount").addClass("rn_Hidden");
      $("#other_amount").closest("label").removeClass("sliderAmount");
      $("#amount_other").closest("label").removeClass("sliderAmount");
    }
  },

  _selectedRange: function () {
    if (document.getElementById("onetime_radio").checked) {
      $("#onetime").text("$" + document.getElementById("range").value);
      $("#giftAmount").text("$" + document.getElementById("range").value);
    } else {
      $("#monthly").text("$" + document.getElementById("range").value);
      $("#giftAmount").text("$" + document.getElementById("range").value);
    }
  },

  _selectedOther: function () {
    if (document.getElementById("amount").value == "other") {
      $("#other_amount").removeClass("rn_Hidden");
      if (document.getElementById("onetime_radio").checked) {
        $("#onetime").text("$" + document.getElementById("other_amount").value);
        $("#giftAmount").text(
          "$" + document.getElementById("other_amount").value
        );
      } else {
        $("#monthly").text("$" + document.getElementById("other_amount").value);
        $("#giftAmount").text(
          "$" + document.getElementById("other_amount").value
        );
      }
    } else {
      $("#other_amount").addClass("rn_Hidden");

      if (document.getElementById("onetime_radio").checked) {
        $("#onetime").text("$" + document.getElementById("amount").value);
        $("#giftAmount").text("$" + document.getElementById("amount").value);
      } else {
        $("#monthly").text("$" + document.getElementById("amount").value);
        $("#giftAmount").text("$" + document.getElementById("amount").value);
      }
    }
  },

  _onLoad: function () {
    if (frequency) {
      // if frequency is not null
      if (frequency == 3) {
        $("#monthly_radio").prop("checked", true);
        $("#onetime_radio").removeClass("rn_Hidden");
        $("#monthly_radio").removeClass("rn_Hidden");
      } else if (frequency == 2) {
        $("#monthly_radio").prop("checked", true);
        $("#onetime_radio").addClass("rn_Hidden");
        $("#monthly_radio").removeClass("rn_Hidden");
      } else if (frequency == 1) {
        $("#onetime_radio").prop("checked", true);
        $("#onetime_radio").removeClass("rn_Hidden");
        $("#monthly_radio").addClass("rn_Hidden");
        $("#monthly_radio").parent("label").hide();
      } else {
        $("#monthly_radio").prop("checked", true);
        $("#onetime_radio").removeClass("rn_Hidden");
        $("#monthly_radio").removeClass("rn_Hidden");
      }
    } else {
      // If frequency is null
      $("#monthly_radio").prop("checked", true);
      $("#onetime_radio").removeClass("rn_Hidden");
      $("#monthly_radio").removeClass("rn_Hidden");
    }

    if (monthly && (frequency == 2 || frequency == 3)) {
      $("#monthly_radio").prop("checked", true);
      $("#giftOneTime").addClass("rn_Hidden");
      $("#giftamount").removeClass("rn_Hidden");
      $("#giftAmount").text("$" + monthly);
    } else {
      $("#onetime_radio").prop("checked", true);
      $("#giftamount").addClass("rn_Hidden");
      $("#giftOneTime").removeClass("rn_Hidden");
      $("#giftAmount").text("$" + oneTime);
    }
    
   // slide logic on load 
    var amount = "";

    $("#other_amount").addClass("rn_Hidden");

    if (document.getElementById("onetime_radio").checked) {

      amount = $("#onetime").text().replace('$','');

      if(parseInt(amount)!== NaN){

      if(parseInt(amount)===25){

      $("#amount_25").closest("label").addClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
        // $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
        }
        else if (parseInt(amount)===50){

          $("#amount_50").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
          // $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");

        }else if (parseInt(amount)===100){

          $("#amount_100").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
          // $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");


        }else{
          $("#amount_other").closest("label").addClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
          $("#other_amount").removeClass("rn_Hidden");
          $("#other_amount").val(parseInt(amount));
          // $("#other_amount").closest("label").removeClass("sliderAmount");
          

        }

      }
      
    } else if (document.getElementById("monthly_radio").checked) {

      amount = $("#monthly").text().replace('$','');

      if(parseInt(amount)!== NaN){

        if(parseInt(amount)===25){
  
        $("#amount_25").closest("label").addClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
           $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");
          }
          else if (parseInt(amount)===50){
  
            $("#amount_50").closest("label").addClass("sliderAmount");
            $("#amount_25").closest("label").removeClass("sliderAmount");
            $("#amount_100").closest("label").removeClass("sliderAmount");
             $("#other_amount").closest("label").removeClass("sliderAmount");
            $("#amount_other").closest("label").removeClass("sliderAmount");
  
          }else if (parseInt(amount)===100){
  
            $("#amount_100").closest("label").addClass("sliderAmount");
            $("#amount_25").closest("label").removeClass("sliderAmount");
            $("#amount_50").closest("label").removeClass("sliderAmount");
             $("#other_amount").closest("label").removeClass("sliderAmount");
            $("#amount_other").closest("label").removeClass("sliderAmount");
  
  
          }else{
            $("#amount_other").closest("label").addClass("sliderAmount");
            $("#amount_100").closest("label").removeClass("sliderAmount");
            $("#amount_25").closest("label").removeClass("sliderAmount");
            $("#amount_50").closest("label").removeClass("sliderAmount");
            // $("#other_amount").closest("label").removeClass("sliderAmount");
            $("#other_amount").removeClass("rn_Hidden");
            $("#other_amount").val(parseInt(amount));
            
  
          }
  
        }
      
    }
    
    // slide logic on load end 


  },

  _selectedMonthly: function () {
    $("#giftOneTime").addClass("rn_Hidden");
    $("#giftamount").removeClass("rn_Hidden");
    $("#giftAmount").text("$" + monthly);
    
    amount = $("#monthly").text().replace('$','');
    $("#other_amount").addClass("rn_Hidden");

      if(parseInt(amount)!== NaN){

      if(parseInt(amount)===25){

      $("#amount_25").closest("label").addClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
        }
        else if (parseInt(amount)===50){

          $("#amount_50").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
           $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");

        }else if (parseInt(amount)===100){

          $("#amount_100").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
           $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");


        }else{
          $("#amount_other").closest("label").addClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
          $("#other_amount").removeClass("rn_Hidden");
          $("#other_amount").val(parseInt(amount));
          // $("#other_amount").closest("label").removeClass("sliderAmount");
          

        }
     }
    


  },

  _selectedRadio: function () {
    $("#giftamount").addClass("rn_Hidden");
    $("#giftOneTime").removeClass("rn_Hidden");
    $("#giftAmount").text("$" + oneTime);
    
    amount = $("#onetime").text().replace('$','');
	$("#other_amount").addClass("rn_Hidden");
      if(parseInt(amount)!== NaN){

      if(parseInt(amount)===25){

      $("#amount_25").closest("label").addClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
        }
        else if (parseInt(amount)===50){

          $("#amount_50").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
           $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");

        }else if (parseInt(amount)===100){

          $("#amount_100").closest("label").addClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
           $("#other_amount").closest("label").removeClass("sliderAmount");
          $("#amount_other").closest("label").removeClass("sliderAmount");


        }else{
          $("#amount_other").closest("label").addClass("sliderAmount");
          $("#amount_100").closest("label").removeClass("sliderAmount");
          $("#amount_25").closest("label").removeClass("sliderAmount");
          $("#amount_50").closest("label").removeClass("sliderAmount");
          $("#other_amount").removeClass("rn_Hidden");
          $("#other_amount").val(parseInt(amount));
          // $("#other_amount").closest("label").removeClass("sliderAmount");
          

        }
       }
      
  },

  _otherAmount: function () {
    if (document.getElementById("onetime_radio").checked) {
      $("#onetime").text("$" + document.getElementById("other_amount").value);
      $("#giftAmount").text(
        "$" + document.getElementById("other_amount").value
      );
    } else {
      $("#monthly").text("$" + document.getElementById("other_amount").value);
      $("#giftAmount").text(
        "$" + document.getElementById("other_amount").value
      );
    }
  },

  _selectedAmountRadio: function () {
    var amount = "";

    $("#other_amount").addClass("rn_Hidden");

    if (document.getElementById("onetime_radio").checked) {
      if (document.getElementById("amount_25").checked) {
        amount = document.getElementById("amount_25").value;
        $("#amount_25").closest("label").addClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      } else if (document.getElementById("amount_50").checked) {
        amount = document.getElementById("amount_50").value;
        $("#amount_50").closest("label").addClass("sliderAmount");
        $("#amount_25").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      } else if (document.getElementById("amount_100").checked) {
        amount = document.getElementById("amount_100").value;
        $("#amount_100").closest("label").addClass("sliderAmount");
        $("#amount_25").closest("label").removeClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      }

      $("#onetime").text("$" + amount);
      $("#giftAmount").text("$" + amount);
    } else if (document.getElementById("monthly_radio").checked) {
      if (document.getElementById("amount_25").checked) {
        amount = document.getElementById("amount_25").value;
        $("#amount_25").closest("label").addClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      } else if (document.getElementById("amount_50").checked) {
        amount = document.getElementById("amount_50").value;
        $("#amount_50").closest("label").addClass("sliderAmount");
        $("#amount_25").closest("label").removeClass("sliderAmount");
        $("#amount_100").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      } else if (document.getElementById("amount_100").checked) {
        amount = document.getElementById("amount_100").value;
        $("#amount_100").closest("label").addClass("sliderAmount");
        $("#amount_25").closest("label").removeClass("sliderAmount");
        $("#amount_50").closest("label").removeClass("sliderAmount");
         $("#other_amount").closest("label").removeClass("sliderAmount");
        $("#amount_other").closest("label").removeClass("sliderAmount");
      }

      $("#monthly").text("$" + amount);
      $("#giftAmount").text("$" + amount);
    } else {
      $("#monthly").text("$" + amount);
      $("#giftAmount").text("$" + amount);
    }
  },

  /**
   * Sample widget method.
   */
  methodName: function () {},
});