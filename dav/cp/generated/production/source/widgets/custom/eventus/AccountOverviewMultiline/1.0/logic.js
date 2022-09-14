RightNow.namespace('Custom.Widgets.eventus.AccountOverviewMultiline');
Custom.Widgets.eventus.AccountOverviewMultiline = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
		
		this.reportData = this.data.js.reportData;
		
		for (var i = 0, len = this.data.js.reportData.length; i < len; i++) {
			
		  	//$(this.baseSelector + "_" + i).click($.proxy(this.showAlertPopup));
		  	this.Y.one(this.baseSelector + "_" + i).on('click', this.showAlertPopup,this); 
		}
		
		
		// this._detailLink = this.Y.one(this.baseSelector + "_Link");
// 		
		// $(".alertLink").click($.proxy(function() {
			// console.log("here");
		// }));
		
    },

    /**
     * Sample widget method.
     */
    showAlertPopup: function(args) {
    	
    	
    	detailElement = $("#" + args._currentTarget.id + "_Detail")
    	var detail = detailElement.html();
    	if(detail){
    		document.getElementById('alertContainer').innerHTML = "<br/></br><div class='alertDetail'>" + detail + "</div><br/><br/>";
    		
    		if(!this._dialog) {
	            this._dialog = RightNow.UI.Dialog.actionDialog("Message from Africa New Life Ministries",
	                                document.getElementById("alertContainer"),
	                                {buttons: []}
	            );
	            
	            RightNow.UI.show(document.getElementById('alertContainer'));
	        }
	        
	        this._dialog.show();
    	}
		
    },

    /**
     * Renders the `view.ejs` JavaScript template.
     */
    renderView: function() {
        // JS view:
        var content = new EJS({text: this.getStatic().templates.view}).render({
            // Variables to pass to the view
            // display: this.data.attrs.display
        });
    }
});