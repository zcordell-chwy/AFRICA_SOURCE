RightNow.namespace('Custom.Widgets.eventus.donationTotal');
Custom.Widgets.eventus.donationTotal = RightNow.Widgets.extend({ 
    /**
     * Widget constructor. this is the sender javascript
     */
	overrides: {
    constructor: function() {
	
	this.parent();
	//RightNowEvent.subscribe("evt_testevent", this._somefunction, this); //example
	RightNowEvent.subscribe("evt_GetDonationAmts", this.evt_GetDonationAmtsRec, this);
	
	
		}
    },
    evt_GetDonationAmtsRec: function (type, args)
    {
    	var oneTimeAmmount = this.Y.one(document.getElementById("txtOneTime")).get("value");
        var monthlyAmmount = this.Y.one(document.getElementById("txtMonthly")).get("value");
        console.log(oneTimeAmmount);
        console.log(monthlyAmmount);
        
        var amtOneTime = new RightNow.Event.EventObject(this, { data: { "field": this._fieldName, "value": oneTimeAmmount } });
        var amtMonthly = new RightNow.Event.EventObject(this, { data: { "field": this._fieldName, "value": monthlyAmmount } });
        
        
        alert(oneTimeAmmount);
        alert(monthlyAmmount);
    }
    /**
     * Sample widget method.
     */
    
});