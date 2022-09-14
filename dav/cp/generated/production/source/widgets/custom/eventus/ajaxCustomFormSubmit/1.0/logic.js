RightNow.namespace('Custom.Widgets.eventus.ajaxCustomFormSubmit');
Custom.Widgets.eventus.ajaxCustomFormSubmit = RightNow.Widgets.FormSubmit.extend({
	/**
	 * Place all properties that intend to
	 * override those of the same name in
	 * the parent inside `overrides`.
	 */
	overrides : {
		/**
		 * Overrides RightNow.Widgets.FormSubmit#constructor.
		 */
		constructor : function() {
			// Call into parent's constructor
			this.parentForm;
			this.parent();
			RightNow.Event.on("on_before_ajax_request", this._onSubmit, this);

		},
		/**
		 * Overridable methods from FormSubmit:
		 *
		 * Call `this.parent()` inside of function bodies
		 * (with expected parameters) to call the parent
		 * method being overridden.
		 */
		// _onButtonClick: function(evt)
		// _fireSubmitRequest: function()
		// _onFormValidated: function()
		// _onFormValidationFail: function()
		// _formSubmitResponse: function(type, args)
		// _displayErrorDialog: function(message)
		// _onFormTokenUpdate: function(type, args)
		// _enableFormExpirationWatch: function()
		// _toggleLoadingIndicators: function(turnOn)
		// _toggleClickListener: function(enable)

		_onButtonClick : function(evt) {
			if(this.data.attrs.confirm_message){
				var response = confirm(this.data.attrs.confirm_message);
				if (response == true) {
					RightNow.Event.fire("evt_CustomFormSubmitted", {
						formID : this._parentForm
					});
					this.parent();
				}
			}else{
				this.parentForm = this._parentForm;
				this.parent();
			}
		},

		// On form validation fail, throw event notifying subscribers of failure
		_onFormValidationFail : function() {
			RightNow.Event.fire("evt_CustomFormValidationFailed", {
				formID : this._parentForm
			});
			this.parent();
		},

		// On a response, throw event notifying subscribers whether the
		// request succeeded or failed
		_formSubmitResponse : function(type, args) {
			var responseObject = args[0].data;

			var eo = new RightNow.Event.EventObject(this, {
				data : {
					form : this._parentForm,
				}
			});

			if (responseObject.message == "Success!") {
				RightNow.Event.fire("evt_CustomFormSubmissionSucceeded");
			} else if (responseObject.message == "SuccessDelete!"){
				
				this._showSuccessPopup(responseObject.data.confirmMessage);
				var payMethodContainer = $(this.baseSelector).parents('.payMethodContainer');
				
				if(payMethodContainer){
					payMethodContainer.remove();
					return; 
					//if we can't get the parent DOM object then just let it pass to the parent to do the redirect.
					//may be necessary for older browsers
				}
				
			} else {
				RightNow.Event.fire("evt_CustomFormSubmissionFailed", eo);
			}

			this.parent(type, args);
			
		}
	},
	
	_showSuccessPopup : function(message){

    	
    	if(message){
    		document.getElementById('alertContainer').innerHTML = "<br/></br><div class='alertDetail'>" + message + "</div><br/><br/>";
    		
    		if(!this._dialog) {
	            this._dialog = RightNow.UI.Dialog.actionDialog("Message from Africa New Life Ministries",
	                                document.getElementById("alertContainer"),
	                                {buttons: []}
	            );
	            
	            RightNow.UI.show(document.getElementById('alertContainer'));
	        }
	        
	        this._dialog.show();
    	}
    	
    	return;

	},

	/**
	 * Sample widget method.
	 */
	_onSubmit : function(type, args) {

		if (args[0].scope._parentForm === "rn_CreateAccount1001") {
			return;
		}
		if (args[0].url == "/ci/ajaxRequestMin/getCountryValues") {
			return;
		}
		if (args[0].url === "/ci/ajaxRequest/doLogin") {
			return;
		}
		

		if (args[0].url != "/ci/ajaxRequest/getReportData") {//the data table sort causes this to throw an js error on app/account/pledge/pledgepay

			formData = RightNow.JSON.parse(args[0].post.form);

			//this is for selecting a payMethod from /app/accounts/payments page.  
			//there are many ajaxCustomFormSubmit buttons on this page so we have to 
			//this.parentForm is set onClick by the button that selected it
			//all the buttons will run through this logic but on the one that was selected will post
			if (this.data.attrs.target_ajax_endpoint !=  "ajaxCustom/processTransaction" && this.parentForm){
				args[0].url = this.data.attrs.target_ajax_endpoint;
				inputArea = '#' + this._parentForm + ' input';
			}else if(this.data.attrs.target_ajax_endpoint !=  "ajaxCustom/processTransaction" && !this.parentForm){
				return;
			}

			if (this._parentForm == "storedMethodForm"){
				args[0].url = "/ci/AjaxCustom/runStoredPayment";
				inputArea = '#storedMethodForm input';
			}
			//if on the pledge edit page
			if (this._parentForm == "pledgeeditform") {
				args[0].url = "/ci/AjaxCustom/updatePledge";
				inputArea = '#pledgeeditform input, #pledgeeditform select';
			}
			if (this._parentForm == "payMethodsform") {
				args[0].url = "/ci/AjaxCustom/deletePayMethods";
				inputArea = '#payMethodsform input';
			}
			if (this._parentForm == "pledgepayform") {
				args[0].url = "/ci/AjaxCustom/runManualPayment";
				inputArea = '#pledgepayform input, #pledgepayform select';
			}
			
			
			

			$.each($(inputArea), function(key, value) {
				formData[formData.length] = {
					"name" : value.name,
					"value" : value.value,
					"checked" : value.checked
				};
			});

			args[0].post.form = RightNow.JSON.stringify(formData);

		}
	}
});
