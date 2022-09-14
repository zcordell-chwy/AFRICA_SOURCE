RightNow.namespace('Custom.Widgets.eventus.inspectlet');
Custom.Widgets.eventus.inspectlet = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor : function() {

		var script = this.data.js.snippet;
		var res = script.replace(/##transactionID##/gi, this.data.js.transaction);

		$('body').append(res);

		
	},

	
});