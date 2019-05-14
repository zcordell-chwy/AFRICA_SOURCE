RightNow.namespace('Custom.Widgets.eventus.managepaymethods');
Custom.Widgets.eventus.managepaymethods = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
        constructor: function() {
			this._container = this.Y.one("#newPaymentContainer");

            $("#newPaymentLink").click($.proxy(function() {
            		this.onAddNewPayLinkClicked();
                    	
                    // $("#newPaymentContainer").toggleClass("rn_Hidden");
                    // $("#existingPaymentContainer").toggleClass("rn_Hidden");
            }, this));

        },


	   onAddNewPayLinkClicked : function(){
	   		if(!this._dialog) {
	            this._dialog = RightNow.UI.Dialog.actionDialog("Add New Payment Method",
	                                document.getElementById("newPaymentContainer"),
	                                {buttons: []}
	            );
	            
	            RightNow.UI.show(this._container);
	        }
	        else if(this._errorDisplay)
	        {
	            this._errorDisplay.set("innerHTML", "");
	        }
	        
	        this.postToIframe();
	
	        this._dialog.show();
	   	
	   	
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
       },

    /**
     * Makes an AJAX request for `default_ajax_endpoint`.
     */
    getDefault_ajax_endpoint: function() {
        // Make AJAX request:
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            // Parameters to send
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.default_ajax_endpoint, eventObj.data, {
            successHandler: this.default_ajax_endpointCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `default_ajax_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
     */
    default_ajax_endpointCallback: function(response, originalEventObj) {
        // Handle response
    }
});