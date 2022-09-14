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
			location.href = "/app/donate";
		};
    },
    /**
     * Sample widget method.
     */
    methodName: function() {
    }
});