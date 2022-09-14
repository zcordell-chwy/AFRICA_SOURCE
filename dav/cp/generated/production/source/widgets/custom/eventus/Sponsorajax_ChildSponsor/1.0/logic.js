RightNow.namespace('Custom.Widgets.eventus.Sponsorajax_ChildSponsor');
Custom.Widgets.eventus.Sponsorajax_ChildSponsor = RightNow.Widgets.extend({
	/**
	 * Widget constructor.
	 */
	constructor : function() { 
		var thisObj = this;

		$(document).ready(function() {
			$('#loadingDiv').hide();// hide it initially


                        //create teh form action for filters and modify them appropriately
                        var genderval = $('#gender1').val();
                        var ageval = $('#age1').val();
                        var commval = $('#community1').val();
                        var origURL = $("form#childselector").attr('action');
                        $("form#childselector").attr('action', origURL + "Gender/" + genderval + "/Age/" + ageval + "/Community/" + commval);

                        $( "#gender1, #age1, #community1").change(function() {
                            var genderval = $('#gender1').val();
                            var ageval = $('#age1').val();
                            var commval = $('#community1').val();
                            $("form#childselector").attr('action', origURL + "Gender/" + genderval + "/Age/" + ageval + "/Community/" + commval);
                          });
                        
                          
                          ///////end filters
                        
                        $(document).ajaxStart(function() {
                            $('#loadingDiv').show();
                         });

                         $(document).ajaxStop(function() {
                            $('#loadingDiv').hide();
                         });

			$("#NextItems").click(function() {
				//prepare the url params data
				var g_ender = $("#gender").text();
				var a_ge = $("#age").text();
				var c_ommunity = $("#community").text();
				var o_rder = $("#order").text();
				var p_age = $("#page").text();
				if ($("#nextres").text() == "1") {
					if (parseInt(p_age) == 0) {
						p_age = "1";
					}
					$("#nextst").text("1");
					$.ajax({
						type : "GET",
						url : "/cc/AjaxCustom/getUnsponsoredChildren1/6/" + g_ender + "/" + a_ge + "/" + c_ommunity + "/" + o_rder + "/" + p_age,
                                                data: { 
                                                    count: 6,
                                                    gender: g_ender, 
                                                    age: a_ge, 
                                                    community: c_ommunity,
                                                    order: o_rder,
                                                    page:p_age
                                                },
						success : function(data1) {
							var obj = $.parseJSON(data1);
							var flag = false;
							$.each(obj, function(index, element) {
								//alert(index);
								if (index == "sorting") {
									//set the values
									$("#gender").text(element[0]);
									$("#age").text(element[1]);
									$("#community").text(element[2]);
									$("#order").text(element[3]);
									$("#page").text(element[4]);
								} else {
									var chresp = element;
									var tt = 0;
									$.each(chresp, function(index1, element1) {
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("src",  this.imageLocation);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("alt", this.ChildRef);

										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("desc", this.Description);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("Gender", this.Gender);
										//populate the name
										var nm = this.GivenName;
										if (nm == null) {
											nm = "";
										}
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_name").text(nm);
										//populate the dob
										var dob = this.DayOfBirth;
										if (dob == null) {
											dob = "";
										}
										var mob = this.MonthOfBirth;
										if (mob == null) {
											mob = "";
										}
										var yob = this.YearOfBirth;
										if (yob == null) {
											yob = "";
										}
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("DBirth", dob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("MBirth", mob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("YBirth", yob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("Rate", this.Rate);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_age").text(this.Age);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_link").attr("childid", this.ID);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_link").attr("ref", this.ChildRef);
										tt++;
									});
									if (tt < 6) {
										$("#nextres").text("0");
										var p1_age = parseInt($("#page").text());
										p1_age = p1_age - 1;
										$("#page").text(p1_age);
										$(".carousel-thumb").each(function() {
											$(this).hide();
										});
										for (var v = 0; v <= tt; v++) {
											$(".carousel-thumb:eq(" + v + ")").show();
										}
									} else {
										$(".carousel-thumb").each(function() {
											$(this).show();
										});
									}
								}
							});
                                                        
						},
						error : function(error) {
							// console.log("Error:");
							//  console.log(error);
						}
					});
				}
			});
			$("#PrevItems").click(function() {
				var p_age1 = $("#page").text();
				if (parseInt(p_age1) >= 1) {
					var g_ender = $("#gender").text();
					var a_ge = $("#age").text();
					var c_ommunity = $("#community").text();
					var o_rder = $("#order").text();
					var p_age = $("#page").text();
					if ($("#nextst").text() == "1") {
						if (p_age1 > 2) {
							p_age1 = p_age1 - 2;
						} else {
							p_age1 = 0;
						}
					} else {
						p_age1 = p_age1 - 1;
					}
					$("#nextst").text("0");
					$.ajax({
						type : "GET",
						url : "/cc/AjaxCustom/getUnsponsoredChildren1/6/" + g_ender + "/" + a_ge + "/" + c_ommunity + "/" + o_rder + "/" + p_age1,
                                                data: { 
                                                    count: 6,
                                                    gender: g_ender, 
                                                    age: a_ge, 
                                                    community: c_ommunity,
                                                    order: o_rder,
                                                    page:p_age1
                                                },
						success : function(data1) {
							var obj = $.parseJSON(data1);
							var flag = false;
							$.each(obj, function(index, element) {
								//alert(index);
								if (index == "sorting") {
									//set the values
									$("#gender").text(element[0]);
									$("#age").text(element[1]);
									$("#community").text(element[2]);
									$("#order").text(element[3]);
									$("#page").text(element[4]);
								} else {
									var chresp = element;
									$.each(chresp, function(index1, element1) {
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("src", this.imageLocation);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("alt", this.ChildRef);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("desc", this.Description);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("Gender", this.Gender);
										//populate the name
										var nm = this.GivenName;
										if (nm == null) {
											nm = "";
										}
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_name").text(nm);
										//populate the dob
										var dob = this.DayOfBirth;
										if (dob == null) {
											dob = "";
										}
										var mob = this.MonthOfBirth;
										if (mob == null) {
											mob = "";
										}
										var yob = this.YearOfBirth;
										if (yob == null) {
											yob = "";
										}
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("DBirth", dob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("MBirth", mob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("YBirth", yob);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_img").attr("Rate", this.Rate);

										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_age").text(this.Age);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_link").attr("childid", this.ID);
										$(".carousel-thumb:eq(" + index1 + ")").find(".ct_link").attr("ref", this.ChildRef);

									});
									var p_age = $("#page").text(p_age1);
									$("#nextres").text("1");
									$(".carousel-thumb").each(function() {
										$(this).show();
									});
								}
							});
                                                        
						},
						error : function(error) {
							// console.log("Error:");
							//  console.log(error);
						}
					});
				}
			});
			$(".ct_link").click(function() {
				var el = $(this).parent().parent();
				var img_url = el.find(".ct_img").attr("src");
				var img_rate = el.find(".ct_img").attr("Rate");
				var img_ref = $(this).attr("ref");
				var img_id = $(this).attr("childid");
				var img_name = el.find(".ct_name").text();
				var img_age = el.find(".ct_age").text();
				$("#ind_link").attr("link", "/app/sponsorchild/ChildID/" + img_id);
				$("#ind_link").attr("rate", img_rate);
				$("#ind_link").attr("childId", img_id);

				$("#ind_image").attr("alt", img_ref);
				$("#ind_name").text(img_name);
				$("#ind_image").attr("src", img_url);
				$("#ind_age").text(img_age);
				$("#ind_ref").text(img_ref);
				$("#spo_dyntext").text($(this).find("img").attr("desc"));
				$("#ind_gender").text($(this).find("img").attr("gender"));
				$("#ind_rate").text("$" + img_rate + ".00/mo");

				if ($(this).find("img").attr("dbirth") != "" && $(this).find("img").attr("mbirth") != "" && $(this).find("img").attr("ybirth") != "") {
					var dob = $(this).find("img").attr("dbirth") + "/" + $(this).find("img").attr("mbirth") + "/" + $(this).find("img").attr("ybirth");
					$("#ind_dob").text(dob);
				} else {
					$("#ind_dob").text("");
				}

			});

		});

		$(document).ready(function() {
			$("#ind_link").click(function() {
				var childAlreadySponsoredMsg = 
		            "This child has already been sponsored. Please select another child.",
		            failedToAcquireLockOnChildMsg = 
		            "This unsponsored child is currently pending sponsorship from another user. Please select another child.",
		            childID = parseInt($(this).attr("childid")),
		            sponsorChildLinkObj = $(this),
            		sponsorChildLinkContainerObj = sponsorChildLinkObj.parent(),
            		processingMessageObj = $("<p>Processing request...<img src=\"/euf/assets/images/loading.gif\" width=\"20\" height=\"20\" /></p>"),
            		fund = $(this).attr("fund"),
            		appeal = $(this).attr("appeal"),
            		rate = $(this).attr("rate");

            	// Hide 'sponsor me' link while we're checking for/applying lock and show processing message to account for delay.
        		sponsorChildLinkObj.hide();
        		sponsorChildLinkContainerObj.append(processingMessageObj);

				// Verify child is still unsponsored
		        $.when(thisObj.isChildUnsponsored(childID)).then(
		            // Success, child is still unsponsored
		            function(){
		                $.when(thisObj.isChildRecordLocked(childID)).then(
		                    // Success, child record is not locked, so let's lock it
		                    function(){
		                        $.when(thisObj.lockChildRecord(childID)).then(
		                            // Success, child record has been locked successfully, now let's store the sponsorship item to the cart for this user
		                            function(){
										var itemsInCart = [];
										itemsInCart[itemsInCart.length] = {
											"itemName" : null,
											'oneTime' : null,
											'recurring' : null,
											'fund' : fund,
											'appeal' : appeal,
											'childId' : childID,
						                    'child_sponsorship' : true
										};

										$.ajax({
											type : "POST",
											url : '/ci/AjaxCustom/storeCartData',
											data : "form=" + JSON.stringify({
												'total' : rate,
												'items' : itemsInCart,
												'donateValCookieContent' : null
											}),
											processData : false,
											success : function() {
												document.location.replace("/app/payment/checkout");
											},
											dataType : 'json'
										});
		                            },
		                            // Failure, child record could not be locked successfully, so let's msg user
		                            function(){
		                                alert(failedToAcquireLockOnChildMsg);

		                                // Remove processing msg and reveal the sponsor me link
                                		processingMessageObj.remove();
                                		sponsorChildLinkObj.show();
		                            }
		                        )
		                    },
		                    // Failure, child record is locked, so let's msg user
		                    function(){
		                        alert(failedToAcquireLockOnChildMsg);

		                        // Remove processing msg and reveal the sponsor me link
                                processingMessageObj.remove();
                                sponsorChildLinkObj.show();
		                    }
		                );
		            },
		            // Failure, child is sponsored
		            function(){
		                alert(childAlreadySponsoredMsg);

		                // Remove processing msg and reveal the sponsor me link
                        processingMessageObj.remove();
                        sponsorChildLinkObj.show();
		            }
		        );
			});
		});
	},

	    /**
     * Deferred method that Performs an AJAX call to determine if the unsponsored child record is locked (already in another user's
     * transaction for sponsorship). The purpose of this function is to prevent the scenario where two user's
     * unknowingly sponsor the same child.
     * @param {integer} childID the ID of the child to check for a record lock on
     * @param deferred resolve, if the child record is not locked, otherwise a deferred reject
     */
    isChildRecordLocked: function(childID){
        var dfd = $.Deferred();

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/isChildRecordLocked/' + childID,
            success: function(status){
                if(status.isLocked) dfd.reject();
                else dfd.resolve();
            },
            dataType: "json" 
        });

        return dfd.promise();
    },

    /**
     * Deferred method that Performs an AJAX call to determine if the child is still unsponsored.
     * @param {integer} childID the ID of the child to check for sponsorship
     * @param deferred resolve, if the child record is unsponsored, otherwise a deferred reject
     */
    isChildUnsponsored: function(childID){
        var dfd = $.Deferred();

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/isChildSponsored/' + childID,
            success: function(status){
                if(status.isSponsored) dfd.reject();
                else dfd.resolve();
            },
            dataType: "json" 
        });

        return dfd.promise();
    },

    /**
     * Deferred method that performs an AJAX call to lock a child record (reserve it for a single user's sponsorship transaction). 
     * The purpose of this function is to prevent the scenario where two user's unknowingly sponsor the same child.
     * @param {integer} childID the ID of the child to lock
     * @param deferred resolve, if the child record was locked successfully, otherwise a deferred reject
     */
    lockChildRecord: function(childID){
        var dfd = $.Deferred();

        $.ajax({
            type: "GET",
            url: '/ci/AjaxCustom/lockChildRecord/' + childID,
            success: function(status){
                if(status.status == "success") dfd.resolve();
                else dfd.reject();
            },
            dataType: "json" 
        });

        return dfd.promise();
    }

});
