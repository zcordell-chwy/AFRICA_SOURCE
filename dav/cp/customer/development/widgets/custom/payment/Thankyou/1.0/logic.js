RightNow.namespace('Custom.Widgets.payment.Thankyou');
Custom.Widgets.payment.Thankyou = RightNow.Widgets.extend({     /**
     * Widget constructor.
     */
    constructor: function() {    	

    	var child_id = this.data.js.childID;
		
		document.getElementById("giftPage").onclick = function () {
			//Navigate to GIFT FOR STUDENT PAGE
			location.href = "/app/give";
		};
		
		document.getElementById("bigImpactPage").onclick = function () {
			//Navigate to GIFT FOR STUDENT PAGE
      
			location.href =RightNow.Interface.getMessage("CUSTOM_MSG_IMPACT_BTN_LNK");//'/app/singleDonation/f_id/636';// "/app/donate";
		};
    },
    /**
     * Sample widget method.
     */
    methodName: function() {
    }
});