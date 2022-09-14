RightNow.namespace('Custom.Widgets.eventus.ccProcess');
Custom.Widgets.eventus.ccProcess = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor : function() {

		var targetIframe = "paymentIdFrame";

		// Default payment choice is "New payment", so load iframe right away
		this.postToIframe();
		$("input:radio[name=ptype]").click($.proxy(function() {
			//var radioVal = $("input:radio[name=ptype]").val();
			//if (radioVal == "new") {
			//	this.postToIframe();
			//}
			$("#newPaymentContainer").toggleClass("rn_Hidden");
			$("#existingPaymentContainer").toggleClass("rn_Hidden");
		}, this));

		for (var i = 0; i < this.data.js.paymentMethods.length; i++) {
			$('#charge-' + this.data.js.paymentMethods[i].id).click(function() {
				alert("controller existing payment method");
			});
		}

		$('#' + this.targetIframe).load(this.handleIframeLoad);
	},

	/**
	 * POST values to the iframe, then remove the temporary form
	 */
	postToIframe : function() {
		var targetIframe = "paymentIdFrame";

		$('body').append('<form action="' + this.data.js.consumerEndpoint + '" method="post" target="' + targetIframe + '" id="postToIframe"></form>');
		$.each(this.data.js.postToFsVals, function(n, v) {
			$('#postToIframe').append('<input type="hidden" name="' + n + '" value="' + v + '" />');
		});
		$('#postToIframe').submit().remove();
	},

	handleIframeLoad : function(args1, args2) {
		if (this.targetIframe.src = this.data.js.postbackUrl) {
			alert("store new payment method");
		}
	}
});
