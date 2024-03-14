RightNow.namespace('Custom.Widgets.eventus.ccProcess');
Custom.Widgets.eventus.ccProcess = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor: function () {
		window.global_this=this;
        this.Y.one(window).on('load', this._onLoad,this); 
		radios = document.forms["storedMethodForm"].elements["paymentMethodId"];
        if(radios){
            for (var i = 0, max = radios.length; i < max; i++) {
               radios[i].checked = false; // Unset the default selected option for single record.
              
            }
        }
		for (var i = 0; i < this.data.js.paymentMethods.length; i++) {
			$('#charge-' + this.data.js.paymentMethods[i].id).click(function () {
				alert("controller existing payment method");
			});
		}



	},
});

function callme(e)
{
  var tds=e.getElementsByTagName('td');
  var cardtype= tds[0].innerHTML.trim();
e.getElementsByTagName('td')[3].querySelector('input[name="paymentMethodId"]').checked=true;  

if(cardtype!=='Checking' && cardtype!=='Savings'){
  document.getElementById("cardpay2").classList.remove("rn_Hidden");
  document.getElementById("cvnum2").required = true;
}else{
  document.getElementById("cardpay2").classList.add("rn_Hidden");
  document.getElementById("cvnum2").required = false;
}

}