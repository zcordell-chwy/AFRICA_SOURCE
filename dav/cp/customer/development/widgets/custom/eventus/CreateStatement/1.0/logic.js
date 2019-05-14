RightNow.namespace('Custom.Widgets.eventus.CreateStatement');

Custom.Widgets.eventus.CreateStatement = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor : function() { 
		$(document).ready(function() {
                     $("#sendStatement").click(function() {
                         $.ajax({
                                type : "POST",
                                url : "/cc/AjaxCustom/sendStatement/",
                                data: { 
                                    cid: $("#contactid").val()
                                },
                                success : function(data1) {
                                        var obj = $.parseJSON(data1);
                                        $.each(obj, function(index, element) {
                                                //alert(index);
                                                console.log(index);

                                        });

                                },
                                error : function(error) {
                                        console.log("Error:");

                                }
                        });
                         
                     });
		});
	},
});
