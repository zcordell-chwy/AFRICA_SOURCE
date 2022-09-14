RightNow.namespace('Custom.Widgets.eventus.donateIndividual');
Custom.Widgets.eventus.donateIndividual = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	//overrides: {
	constructor: function() {
	
	var addDonationBtn = this.Y.one(this.baseSelector + "_adddonation");
	addDonationBtn.on('click', this.evt_GetDonationAmts,this);
	
	
		document.getElementById(this.baseDomID + "txtOneTime").onkeypress=function(evt){

			  var theEvent = evt || window.event;
			  var key = theEvent.keyCode || theEvent.which;
			  key = String.fromCharCode( key );
			  var regex = /[0-9]/;
			  if( !regex.test(key) ) {
			    theEvent.returnValue = false;
			    if(theEvent.preventDefault) theEvent.preventDefault();
			  }
			  
		}
		
		document.getElementById(this.baseDomID + "txtMonthly").onkeypress=function(evt){

			  var theEvent = evt || window.event;
			  var key = theEvent.keyCode || theEvent.which;
			  key = String.fromCharCode( key );
			  var regex = /[0-9]/;
			  if( !regex.test(key) ) {
			    theEvent.returnValue = false;
			    if(theEvent.preventDefault) theEvent.preventDefault();
			  }
			  
		}
	
	
	},
	
	/**
	 * widget method for single donations
	 */
	evt_GetDonationAmts: function() {
	
			var oneTimeAmount = (this.Y.one(this.baseSelector + "txtOneTime").get('value') ) ? this.Y.one(this.baseSelector + "txtOneTime").get('value'): 0;
			var monthlyAmount = (this.Y.one(this.baseSelector + "txtMonthly").get('value') ) ? this.Y.one(this.baseSelector + "txtMonthly").get('value'): 0;
			var fund = this.Y.one(this.baseSelector +"itemTag").getAttribute("fund");
			var appeal = this.Y.one(this.baseSelector +"itemTag").getAttribute("appeal");
			var itemid = this.Y.one(this.baseSelector +"itemTag").getAttribute("itemid");
			var itemname = this.Y.one(this.baseSelector +"itemTag").get("text");
			
			
			// var _eo = new RightNow.Event.EventObject(this, {data: {
				// oneTimeAmount: oneTimeAmount,
				// monthlyAmount: monthlyAmount,
				// fundTitle:fund,
				// appeal:appeal,
				// itemname:itemid
			// }});
			
			var lineItems = [];
        	lineItems.push(
                {
                    merch: {
                        id: itemid,
                        title: itemname,
                        price: parseInt(oneTimeAmount) + parseInt(monthlyAmount)
                    },
                    quantity: 1,
                    customData: {
                        amountOneTime: oneTimeAmount,
                        amountMonthly: monthlyAmount,
                        donationFundID: fund,
                        donationAppealID: appeal,
                        donationType: "fund"
                    }
                }
            );

        var eventObj = {
            shoppingCartID: "donation",
            lineItems: lineItems
        };
			
			RightNow.Event.fire("evt_addLineItemsToShoppingCartRequest", eventObj);
			
			return eventObj;  
		},
		
	

});