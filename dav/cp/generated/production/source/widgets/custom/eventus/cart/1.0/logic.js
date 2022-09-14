RightNow.namespace('Custom.Widgets.eventus.cart');
Custom.Widgets.eventus.cart = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
	
	 constructor: function() {
	
	   var total = 0;
	   var oneTime = 0;
	   var monthly = 0;
	   var fund = "";
	   var appeal = "";
	   var itemName = "";
		
	   RightNow.Event.subscribe("evt_GetDonationAmts", this.evt_SetDonationAmts, this);
	   
	   
	   var payNowBtn = this.Y.one(this.baseSelector + "btnPayNow");
       payNowBtn.on('click', this.evt_addToCart,this);       
   	
	   var checkoutdiv_right_lnk = this.Y.one(this.baseSelector + "checkoutdiv_right_lnk");
	   checkoutdiv_right_lnk.on('click', this.evt_removeAmountRight,this);	
	   checkoutdiv_right_lnk.setAttribute("href", "javascript:void(0)");
				
	   var checkoutdiv_left_lnk = this.Y.one(this.baseSelector + "checkoutdiv_left_lnk");	
	   checkoutdiv_left_lnk.setAttribute("href", "javascript:" + this.evt_removeAmountLeft);
	   checkoutdiv_left_lnk.on('click', this.evt_removeAmountLeft,this);
       
	   },
	   evt_SetDonationAmts: function(type,args)
	   {
			// var totalAmt = this.Y.one(this.baseSelector + "spantotalamt0");
			 this.oneTime = args[0].data.oneTimeAmount.valueOf();			 
			 this.monthly = args[0].data.monthlyAmount.valueOf();
			 this.fund = args[0].data.fundTitle;
			 this.appeal = args[0].data.appeal;
			 var title = args[0].data.itemname.replace(/\t/g, '').replace(/\n/g, '');			
			 this.itemName = title;
			//Set the donation description as visible here...and set its attributes
			 var checkoutdivbold = this.Y.one(this.baseSelector + "checkoutdivbold");	
			 
			 if(this.evt_AllowOnlyWholeNumbers(this.monthly, this.oneTime))
			{
			 checkoutdivbold.setAttribute("fund",args[0].data.fund);
			 checkoutdivbold.setAttribute("appeal",args[0].data.appeal);
			 checkoutdivbold.set('text', args[0].data.itemname);
			 
			 var checkoutdiv_left_amtOneTime = this.Y.one(this.baseSelector + "checkoutdiv_left_amtOneTime");
			 checkoutdiv_left_amtOneTime.set('text', this.oneTime);
			 
			 var checkoutdiv_right_amtMonthly = this.Y.one(this.baseSelector + "checkoutdiv_right_amtMonthly");
			 checkoutdiv_right_amtMonthly.set('text', this.monthly);
			
			 this._toggleCartVisibility();
			
			 this.evt_calculateTotal(this.monthly,this.oneTime);
			}
		}, 
		//function to render the 'Remove' links as visible. 
		_toggleCartVisibility: function(){
			
			this.Y.one(this.baseSelector +"checkoutdiv_left").setAttribute('class', 'rn_cartcheckoutdiv_leftShow');
			this.Y.one(this.baseSelector +"checkoutdiv_right").setAttribute('class', 'rn_cartcheckoutdiv_rightShow');
			this.Y.one(this.baseSelector +"checkoutdiv_right_lnk").setAttribute('class', 'rn_cartShowDiv');
			this.Y.one(this.baseSelector +"checkoutdiv_left_lnk").setAttribute('class', 'rn_cartShowDiv');
			
		}, //method to to recalculate the total and to reset the one time anchor to 0.00
		//tag when the one time Remove anchor is clicked
		evt_removeAmountLeft: function(){
			
			this.oneTime = 0;
			var checkoutdiv_left_amtOneTime = this.Y.one(this.baseSelector + "checkoutdiv_left_amtOneTime");			
			checkoutdiv_left_amtOneTime.set('text', this.oneTime); 
			if(this.evt_AllowOnlyWholeNumbers(this.monthly, this.oneTime))
			{
			this.Y.one(this.baseSelector +"checkoutdiv_left_lnk").setAttribute('class', 'rn_cartHideDiv');			
			this.Y.one(this.baseSelector +"checkoutdiv_left").setAttribute('class', 'rn_cartcheckoutdiv_left');
			}
			//pass month then one time here
			this.evt_calculateTotal(this.monthly,this.oneTime);
		},
		evt_removeAmountRight: function(){
			
			this.monthly = 0;
			var checkoutdiv_right_amtMonthly = this.Y.one(this.baseSelector + "checkoutdiv_right_amtMonthly");			
			checkoutdiv_right_amtMonthly.set('text', this.monthly);	
			//pass month then one time here 
			if(this.evt_AllowOnlyWholeNumbers(this.monthly, this.oneTime))
			{
				this.Y.one(this.baseSelector +"checkoutdiv_right_lnk").setAttribute('class', 'rn_cartHideDiv');
				this.Y.one(this.baseSelector +"checkoutdiv_right").setAttribute('class', 'rn_cartcheckoutdiv_right');
			}
			
			this.evt_calculateTotal(this.monthly,this.oneTime);
			
			
		},
		evt_AllowOnlyWholeNumbers: function(Month,OneTime)
		{
			var isWholePositiveNumber = false;
			var NumberRegEx = '/^\d*[1-9]\d*$/';			
			
			if(Month == "")
			{
				Month = 0;
				
			}
			if(OneTime == "")
			{
				OneTime = 0;				
			}
			
			
			var isOneTimeNumeric = /^\d*[1-9]\d*$/.test(OneTime);
			var isMonthlyNumeric = /^\d*[1-9]\d*$/.test(Month);
			
			if (isOneTimeNumeric && isMonthlyNumeric)
			{
				isWholePositiveNumber = true;		
				
			}
			else
			{
				isWholePositiveNumber = false;
								this.total = 0;
			}
			if (isOneTimeNumeric && !isMonthlyNumeric)
			{
				isWholePositiveNumber = true;		
				
				this.total = OneTime;
			}
			
			if (!isOneTimeNumeric && isMonthlyNumeric)
			{
				isWholePositiveNumber = true;		
				
				this.total = Month;
			}
			return isWholePositiveNumber;
		},
		
		evt_calculateTotal: function(Month,OneTime){
			
			
			
			
			var totalAmount;
			var outputMonth;
			var outputOneTime;
			var totalAmt = this.Y.one(this.baseSelector + "spantotalamt0");		
			if (this.evt_AllowOnlyWholeNumbers(Month,OneTime))
			{
				YUI().use("datatype-number", function(Y) {
				     outputMonth = Y.Number.parse(Month);				     
				     outputOneTime = Y.Number.parse(OneTime);
				     totalAmount = outputMonth + outputOneTime; 
				     totalAmt.set('text', totalAmount);				
					 this.total = totalAmount;

				});
				
			}
			else
			{
				this.total = "0";
				totalAmt.set('text', this.total);	
			}
			
			
		},
		
		evt_addToCart: function(){			
		
		
			var validNum = this.evt_AllowOnlyWholeNumbers(this.monthly,this.oneTime);
			if(validNum)
			{
				var itemsInCart=[];
				 itemsInCart[itemsInCart.length] = {
						 "itemName" : this.itemName,
						 'oneTime' : this.oneTime,
						 'recurring' :this.monthly,
						 'fund' : this.fund,
						 'appeal' : this.appeal
					 };
					
				
				$.ajax({
					type : "POST",
					url : '/ci/AjaxCustom/storeCartData',
					data : "form=" + JSON.stringify({
						'total' : this.total,
						'items' : itemsInCart,
						'donateValCookieContent' : 'donate_val_text'
					}),
					processData : false,
					success : function() {
						document.location.replace("/app/payment/checkout");
					},
					dataType : 'json'
				});
				
			}
			
			
		}
	
});