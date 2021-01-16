RightNow.namespace('Custom.Widgets.eventus.ajaxCustomFormSubmit');
Custom.Widgets.eventus.ajaxCustomFormSubmit = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
		
		this.parentForm;
		this._formButton = this.Y.one(this.baseSelector + "_Button");
		this._errorMessageDiv = this.Y.one("#rn_ErrorLocation");
		this._formButton.on("click", this._onButtonClick, this);
		this._requestInProgress = false;
		// tabControlContinueButton = tabControl.find("button.CheckoutAssistantContinueButton");
		// tabControlBackButton.click(function(evt){
                // thisObj.handleTabBackButtonClick(evt, tabID);
            // });

    },

    /**
     * Sample widget method.
     */
    _onButtonClick: function() {
		
		if(this.data.attrs.confirm_message){
			var response = confirm(this.data.attrs.confirm_message);
			if (response == true) {
				this._toggleClickListener(false);
				this.getDefault_ajax_endpoint();
			}
		}else{
			this._toggleClickListener(false);
			this.getDefault_ajax_endpoint();
		}
		 
    },

    /**
     * Makes an AJAX request for `default_ajax_endpoint`.
     */
    getDefault_ajax_endpoint: function() {
	    
	    if(this._errorMessageDiv)	
	    	this._errorMessageDiv.addClass("rn_Hidden").set("innerHTML", "");
	    
	    var formData = [];
	    var formNode;
	    
	    var endpoint = this.data.attrs.default_ajax_endpoint;
	    inputArea = '#storedMethodForm input';
	    
	    if(this.data.attrs.formname == "changePayMethod"){//change a payment method
	    	formNode = this._formButton.ancestor('form');

	    	inputArea = '#' + formNode._node.id + ' input';
	    	
	    	$.each($(inputArea), function(key, value) {
				formData[formData.length] = {
					"name" : value.name,
					"value" : value.value,
					"checked" : value.checked
				};
			});
				
	        // Make AJAX request:
	        var eventObj = new RightNow.Event.EventObject(this, {data:{
	            w_id: this.data.info.w_id,
	            formData: RightNow.JSON.stringify(formData)
	            // Parameters to send
	        }});
	        RightNow.Ajax.makeRequest(this.data.attrs.changepaymethod_ajax_endpoint, eventObj.data, {
	            successHandler: this.default_ajax_endpointCallback,
	            scope:          this,
	            data:           eventObj,
	            json:           true
	        });
	        
	    }else if(this.data.attrs.formname == "deletePayMethod"){//delete a payment method
	    	
	    	formNode = this._formButton.ancestor('form');
	    	inputArea = '#' + formNode._node.id + ' input';
	    	
	    	$.each($(inputArea), function(key, value) {
				formData[formData.length] = {
					"name" : value.name,
					"value" : value.value,
					"checked" : value.checked
				};
			});
				
	        // Make AJAX request:
	        var eventObj = new RightNow.Event.EventObject(this, {data:{
	            w_id: this.data.info.w_id,
	            formData: RightNow.JSON.stringify(formData)
	            // Parameters to send
	        }});
	        RightNow.Ajax.makeRequest(this.data.attrs.deletepaymethod_ajax_endpoint, eventObj.data, {
	            successHandler: this.default_ajax_endpointCallback,
	            scope:          this,
	            data:           eventObj,
	            json:           true
	        });
	    	
	    }else{//process stored payment
	    	
	    	$.each($(inputArea), function(key, value) {
				formData[formData.length] = {
					"name" : value.name,
					"value" : value.value,
					"checked" : value.checked
				};
			});
				
	        // Make AJAX request:
	        var eventObj = new RightNow.Event.EventObject(this, {data:{
	            w_id: this.data.info.w_id,
	            formData: RightNow.JSON.stringify(formData)
	            // Parameters to send
			}});

			//set loading
			$(this.baseSelector + "_LoadingIcon").removeClass( "rn_Hidden");

			//testing timeout link
				//this.data.attrs.default_ajax_endpoint = "https://africanewlife.custhelp.com/cgi-bin/africanewlife.cfg/php/custom/testpost.php";
	        RightNow.Ajax.makeRequest(this.data.attrs.default_ajax_endpoint, eventObj.data, {
				successHandler: this.default_ajax_endpointCallback,
				failureHandler: this.ajaxFailed,
				timeout:  10000, //10 seconds
	            scope:          this,
	            data:           eventObj,
	            json:           true
	        });
	    }
	    
		
		
		
	},
	
	/**
     * Handles the AJAX response timeout.
     * 
     */
    ajaxFailed: function(response, originalEventObj) {

		if (response.errors) {
            // Error message(s) on the response object.
            var errorMessage = "";
            this.Y.Array.each(response.errors, function(error) {
                errorMessage += "<div><b>" + error + "</b></div>";
            });
            this._errorMessageDiv.append(errorMessage);
            this._errorMessageDiv.removeClass("rn_Hidden");
        }else{
			//pop error message
			//this._displayErrorDialog(RightNow.Interface.getMessage('CUSTOM_MSG_PAY_SERVICE_ERROR'));
			//this._toggleClickListener(true);

			//redirect to success page and let donor know their payment has been queued and they should get
			//an email receipt in 1-2 hours.
			//app/payment/successCC/t_id/0/authCode/0/
			let url = '/app/payment/successCC/t_id/0/authCode/0/'
			RightNow.Url.navigate(url);

		}

		$(this.baseSelector + "_LoadingIcon").addClass( "rn_Hidden");
		
	},

    /**
     * Handles the AJAX response for `default_ajax_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getDefault_ajax_endpoint
     */
    default_ajax_endpointCallback: function(response, originalEventObj) {
        
        if (!response) {
            // Didn't get any kind of a response object back; that's... unexpected.
            this._displayErrorDialog(RightNow.Interface.getMessage('ERROR_REQUEST_ACTION_COMPLETED_MSG'));
        }
        else if (response.errors) {
            // Error message(s) on the response object.
            var errorMessage = "";
            this.Y.Array.each(response.errors, function(error) {
                errorMessage += "<div><b>" + error + "</b></div>";
            });
            this._errorMessageDiv.append(errorMessage);
            this._errorMessageDiv.removeClass("rn_Hidden");
        }
        else if (response.result) {
            result = response.result;

            if (result.sa) {
                // trap SmartAssistant™ case here
                if(result.newFormToken) {
                    // Check if a new form token was passed back and use it the next time the the form is submitted
                    this.data.js.f_tok = result.newFormToken;
                    RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: result.newFormToken}}));
                }
            }
            else if (result.redirectOverride) {
                // success
                
                    var url;

                    if (result.redirectOverride) {
                        url = result.redirectOverride;
                    }
            
                    RightNow.Url.navigate(url);
                	
            }
            else {
                // Response object with a result, but not the result we expect.
                this._displayErrorDialog();
            }
        }
        else {
            // Response object didn't have a result or errors on it.
            this._displayErrorDialog();
        }

        this._toggleClickListener(true);
        return;
                
    },
    
    _displayErrorDialog: function(message) {
        RightNow.UI.Dialog.messageDialog(message || RightNow.Interface.getMessage('ERROR_PAGE_PLEASE_S_TRY_MSG'), {icon : "WARN"});
    },
    
    _toggleClickListener: function(enable) {
        this._formButton.set("disabled", !enable);
        this._requestInProgress = !enable;
        this.Y.Event[((enable) ? "attach" : "detach")]("click", this._onButtonClick, this._formButton, this);
    },
});