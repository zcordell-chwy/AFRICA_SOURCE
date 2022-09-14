RightNow.namespace('Custom.Widgets.eventus.ccProcess');
Custom.Widgets.eventus.ccProcess = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor: function () {
		for (var i = 0; i < this.data.js.paymentMethods.length; i++) {
			$('#charge-' + this.data.js.paymentMethods[i].id).click(function () {
				alert("controller existing payment method");
			});
		}
	},
});
