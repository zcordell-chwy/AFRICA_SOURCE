RightNow.namespace('Custom.Widgets.eventus.pledgeform');
Custom.Widgets.eventus.pledgeform = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {

        $(document).ready(function(){
            
            $(document).ajaxStart(function() {
                $('#cancelPledge_LoadingIcon').toggleClass('rn_Hidden');
                $('#cancelPledge_StatusMessage').toggleClass('rn_Hidden');
             });

             $(document).ajaxStop(function() {
                $('#cancelPledge_LoadingIcon').toggleClass('rn_Hidden');
                $('#cancelPledge_StatusMessage').toggleClass('rn_Hidden');
             });
             
             $("#cancelPledge").click(function() {
			
                var response = confirm("You are about to permanently cancel your Pledge.\n Please confirm this is your intention.");
                if (response == true) {
                    $.ajax({
                    type : "POST",
                    url : "/cc/AjaxCustom/cancelPledge",
                    data: { 
                        pledgeID: $('#pledge_id').val()
                    },
                    success : function(args) {
                        formData = RightNow.JSON.parse(args);
                        if(formData.result.redirectOverride){
                            window.location.href = formData.result.redirectOverride;
                        }
                    },
                    error : function(error) {
                            // console.log("Error:");
                            //  console.log(error);
                    }
                });
                    
                } 
                 
                
            });

        });
 
    },

    /**
     * Sample widget method.
     */
    methodName: function() {

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