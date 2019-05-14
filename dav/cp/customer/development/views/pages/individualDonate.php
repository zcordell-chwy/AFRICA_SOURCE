<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="responsive.php" clickstream="indFundPage"  login_required="false" />
<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Make an Individual Donation" banner_img_path="/euf/assets/images/banners/donate.jpg" />
<div class="donate-content" style="overflow: auto;">

	<div class="donate-info">
		<!-- <rn:widget path="custom/donatemember"/> add donation total widget, 
		and remove the donation total html, & add it to the donation total widget, remove horrible inline javascript for cart-->
		<div>
			<div class="donate-carousel">
				<div class="carousel-content">
					<rn:widget path="custom/eventus/IndividualFund"/>	
																
				</div>
				<div style="width:100%;">
					<p>
						&nbsp;
					</p>
				</div>
			</div>
		</div>			
	</div>
	
	<!-- <div class="donate-total"></div>  --> 
	<rn:widget path="custom/eventus/donationTotal"/>
		<!--  <h3>Donation Total</h3>
		<p id="items_elements"></p>
		<p id="items_txt" style="display:none;"></p>
		<p style="display:none;">
			&nbsp;One Time:
		</p>
		<p style="display:none;">
			&nbsp;$<span id="spantotalamt1">0</span>.00
		</p>
		<p style="display:none;">
			&nbsp;Monthly:
		</p>
		<p style="display:none;">
			&nbsp;$<span id="spantotalamt2">0</span>.00
		</p>
		<p style="color:#911A1D;font-size:16px;font-weight:bold;margin-bottom:20px;">
			&nbsp;<span style="border-top:2px solid black;padding-top:10px;">Total</span>&nbsp;:&nbsp;$<span id="spantotalamt0">0</span>.00
		</p>
		<p>
			&nbsp;<a class="sponsor-button" href="javascript:void(0);"><img src="images/pay_now.png" style="width:135px;"/></a>
		</p>-->
	
</div>
<!-- end donate content -->

<!-- <div style="width:100%; float:none;";>
<div></div>&nbsp;<span></span>

<div style="width:100%; float:none;";>
<p>&nbsp;</p>
</div> -->

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

<style type="text/css">
	.txtfield_box input {
		float: right;
	}
	.txtfield_box a {
		float: right;
	}
	.txtfield_box p {
		padding-top: 2px;
		display: inline-block;
	}
	.txtfield_box {
		margin-bottom: 7px;
	}
	.donate-carousel {
		display: inline-block;
		padding-top: 20px;
		/*border-top: 2px dotted #eee;*/
	}
	.itemname {
		font-weight: normal;
		padding-top: 10px;
		padding-bottom: 16px;
		display: inline-block;
		width: 138px;
	}
	.itemname1 {
		font-weight: normal;
		padding-top: 10px;
		padding-bottom: 16px;
		display: inline-block;
		width: 138px;
	}
	.onetime {
		text-decoration: none;
		font-weight: normal;
		display: inline-block;
		padding-bottom: 5px;
	}
	.monthly {
		padding-top: 15px;
		text-decoration: none;
		font-weight: normal;
		display: inline-block;
		padding-bottom: 5px;
	}
	.itemname2 {
		color: rgb(145, 26, 29);
		font-weight: normal;
		font-size: 14px;
	}
	.checkoutdiv {
		padding: 5%;
		margin-bottom: 20px;
		display: inline-block;
		width: 90%;
	}
	.checkoutdiv_left {
		font-weight: bold;
		width: 50%;
		float: left;
		display: inline-block;
	}
	.checkoutdiv_right {
		font-weight: bold;
		width: 48%;
		float: right;
		display: inline-block;
	}
	.checkoutdiv_left_lnk {
		display: block;
		font-weight: normal;
		font-size: 10px;
		color: rgb(145, 26, 29);
		text-decoration: none;
	}
	.checkoutdiv_right_lnk {
		display: block;
		font-weight: normal;
		font-size: 10px;
		color: rgb(145, 26, 29);
		text-decoration: none;
	}
	input[type="text"] {
		background: transparent url('images/textback2.png') no-repeat;
		border: 1px solid white;
		height: 25px;
		width: 85px;
		padding-left: 5px;
		padding-right: 5px;
	}
	input[type="text"]:focus {
		border: 1px solid white;
		background-color: white;
		background: transparent url('images/textback2.png') no-repeat;
	}
</style>

<!--New donate page script
<script type="text/javascript">
	function getalltext() {
		var i = 0;
		var txt = "";
		$(".itemname").each(function() {
			if ($(this).attr("id") == "sam_item") {
				if ($(this).parent().parent().parent().find(".txtamt2").attr("value") != "" || $(this).parent().parent().parent().find(".txtamt1").attr("value") != "") {
					//add to the main text
					i++;
					if ($(this).attr("id") == "sel_item") {
						if ($(".txtamt2").last().attr("value") != "" || $(".txtamt1").last().attr("value") != "") {
							txt = txt + "<span class='dynsel'>" + $(this).find("option:selected").text() + "</span>";//</br>
						}

					} else if ($(this).attr("id") == "sel_item1") {
						if ($(".txtamt2").last().attr("value") != "" || $(".txtamt1").last().attr("value") != "") {
							txt = txt + "<span class='dynsel'>" + $(this).find("option:selected").text() + "</span>";//</br>
						}

					} else {
						txt = txt + $(this).text();//</br>
					}
					$("#items_txt").html(txt);
				}
			} else {
				if ($(this).parent().parent().find(".txtamt2").attr("value") != "" || $(this).parent().parent().find(".txtamt1").attr("value") != "") {
					//add to the main text
					i++;
					if ($(this).attr("id") == "sel_item") {
						if ($(".txtamt2").last().attr("value") != "" || $(".txtamt1").last().attr("value") != "") {
							txt = txt + "<span class='dynsel'>" + $(this).find("option:selected").text() + "</span>";//</br>
						}

					} else if ($(this).attr("id") == "sel_item1") {
						if ($(".txtamt2").last().attr("value") != "" || $(".txtamt1").last().attr("value") != "") {
							txt = txt + "<span class='dynsel'>" + $(this).find("option:selected").text() + "</span>";//</br>
						}

					} else {
						txt = txt + $(this).text();
					}
					var txt1 = txt.replace('\t/g', "");
					var txt2 = txt1.replace('\n/g', "");
					txt = txt2;
					$("#items_txt").html(txt2);
				}
			}

		});

		if (i == 0) {
			$("#items_txt").html("");
		}
		var txt1 = txt.replace(/\t/g, ' ');
		var txt2 = txt1.replace(/\n/g,' ');
		$("#items_txt").html(txt2);
		//alert(txt2); //WTIG?
		txt = txt2;
		//alert(txt + " Reset Text var");
		
	}

	function buildCartDisplay() {
		var i = 0;
		$(".itemname").each(function() {
			//$(this).attr("tab", "tab" + i); 
			i++;
		});
		var finalstr = "";
		$(".itemname").each(function() {
			if ($(this).attr("id") == "sel_item") {
				var tg = $(this).attr("tab");
				var io = 0;
				$(this).find("option").each(function() {
					var fu = $(this).attr("fund");
					var ap = $(this).attr("appeal");
					var nm = $(this).text().replace(/\t/g, '').replace(/\n/g, '');
					//finalstr = finalstr + "<div class='checkoutdiv' style='display:none;'><div><b tab='" + tg + "' option='" + io + "' class='itemname2 itemname22' fund='" + fu + "' appeal='" + ap + "'>" + nm + "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";
					finalstr = finalstr + "<div class='checkoutdiv' style='display:none;'><div><b option='" + io +
					 "' class='itemname2 itemname22' fund='" + fu + "' appeal='" + ap + "'>" + nm +
					  "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";
					io++;
				});
			} else if ($(this).attr("id") == "sel_item1") {
				var tg = $(this).attr("tab");
				var io = 0;
				$(this).find("option").each(function() {
					var nm = $(this).text().replace(/\t/g, '').replace(/\n/g, '');
					var fu = $(this).attr("fund");
					var ap = $(this).attr("appeal");
					//finalstr = finalstr + "<div class='checkoutdiv' style='display:none;'><div><b tab='" + tg + "' option='" + io + "' class='itemname2 itemname22' fund='" + fu + "' appeal='" + ap + "'>" + nm + "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right' style='display:none;'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";
					finalstr = finalstr +
					 "<div class='checkoutdiv' style='display:none;'><div><b option='" + io +
					  "' class='itemname2 itemname22' fund='" + fu + "' appeal='" + ap 
					  + "'>" + nm + "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right' style='display:none;'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";	
					io++;
				});
			} else {
				var fu = $(this).attr("fund");
				var ap = $(this).attr("appeal");
				//finalstr = finalstr + "<div class='checkoutdiv' style='display:none;'><div><b tab='" + $(this).attr("tab") + "' class='itemname2' fund='" + fu + "' appeal='" + ap + "'>" + $(this).text() + "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";
				finalstr = finalstr + "<div class='checkoutdiv' style='display:none;'><div><b class='itemname2' fund='" + fu + "' appeal='" + ap + "'>" + $(this).text() + "</b></div><div class='checkoutdiv_left'>One-time:<br/>$<span class='checkoutdiv_left_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_left_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div><div class='checkoutdiv_right'>Monthly:<br/>$<span class='checkoutdiv_right_amt'>0</span>.00<a href='javascript:void(0);' class='checkoutdiv_right_lnk' style='color:rgb(145, 26, 29);display:none;'>Remove</a></div></div>";
				}
		});
		$("#items_elements").html(finalstr);
		$(".don_linkbutton").click(function() {
			var ele = $(this).parent().parent().find(".itemname");
			var onetime = $(this).parent().parent().find(".txtamt1").attr("value");

			if (!checkifnumber(onetime)) {
				onetime = "";
				$(this).parent().parent().find(".txtamt1").attr("value", "");
			}
			var month = $(this).parent().parent().find(".txtamt2").attr("value");
			//alert(month.length);
			if (!checkifnumber(month)) {
				month = "";
				$(this).parent().parent().find(".txtamt2").attr("value", "");
			}
			var tbid = $(this).parent().parent().find(".itemname").attr("tab"); //
			if (ele.attr("id") == "sel_item") {
				//alert(ele.find("option:selected").text());
				if (onetime == "" && month != "") {
					ele.find("option:selected").attr("amount", "0," + month);
					addvaluestocheckout(tbid, "0", month, "sel");
				} else if (onetime != "" && month == "") {
					ele.find("option:selected").attr("amount", onetime + ",0");
					addvaluestocheckout(tbid, onetime, "0", "sel");
				} else if (onetime != "" && month != "") {
					ele.find("option:selected").attr("amount", onetime + "," + month);
					addvaluestocheckout(tbid, onetime, month, "sel");
				} else {
					ele.find("option:selected").attr("amount", "0,0", "sel");
					addvaluestocheckout(tbid, "0", "0");
				}
			} else if (ele.attr("id") == "sel_item1") {
				//alert(ele.find("option:selected").text());
				if (onetime == "" && month != "") {
					ele.find("option:selected").attr("amount", "0," + month);
					addvaluestocheckout(tbid, "0", month, "sel");
				} else if (onetime != "" && month == "") {
					ele.find("option:selected").attr("amount", onetime + ",0");
					addvaluestocheckout(tbid, onetime, "0", "sel");
				} else if (onetime != "" && month != "") {
					ele.find("option:selected").attr("amount", onetime + "," + month);
					addvaluestocheckout(tbid, onetime, month, "sel");
				} else {
					ele.find("option:selected").attr("amount", "0,0", "sel");
					addvaluestocheckout(tbid, "0", "0");
				}
			} else {
				//alert(ele.find("option:selected").text());
				if (onetime == "" && month != "") {
					addvaluestocheckout(tbid, "0", month, "");
				} else if (onetime != "" && month == "") {
					addvaluestocheckout(tbid, onetime, "0", "");
				} else if (onetime != "" && month != "") {
					addvaluestocheckout(tbid, onetime, month, "");
				} else {
					addvaluestocheckout(tbid, "0", "0", "");
				} 
			}

			//calculate the total value
			var totalt = 0;
			var totalo = 0;
			var totalm = 0;

			$(".txtamt1").each(function() {
				if ($(this).hasClass("seldiv") == false) {
					if ($(this).attr("value").length > 0) {
						if (!isNaN($(this).attr("value"))) {
							totalt = totalt + parseInt($(this).attr("value"));
							totalo = totalo + parseInt($(this).attr("value"));
						}
					}
				}
			});
			$(".txtamt2").each(function() {
				if ($(this).hasClass("seldiv") == false) {
					if ($(this).attr("value").length > 0) {
						if (!isNaN($(this).attr("value"))) {
							totalt = totalt + parseInt($(this).attr("value"));
							totalm = totalm + parseInt($(this).attr("value"));
						}
					}
				}
			});

			$("#sel_item").find("option").each(function() {
				if ($(this).attr("amount") != "0,0") {
					var amtstr = $(this).attr("amount");
					var amtarr = amtstr.split(",");
					if (!isNaN(amtarr[0])) {
						totalt = totalt + parseInt(amtarr[0]);
					}
					if (!isNaN(amtarr[1])) {
						totalt = totalt + parseInt(amtarr[1]);
					}
				}
			});

			$("#sel_item1").find("option").each(function() {
				if ($(this).attr("amount") != "0,0") {
					var amtstr = $(this).attr("amount");
					var amtarr = amtstr.split(",");
					if (!isNaN(amtarr[0])) {
						totalt = totalt + parseInt(amtarr[0]);
					}
					if (!isNaN(amtarr[1])) {
						totalt = totalt + parseInt(amtarr[1]);
					}
				}
			});

			$("#spantotalamt1").html(totalo);
			$("#spantotalamt2").html(totalm);
			$("#spantotalamt0").html(totalt);
			getalltext();
			//select change text code
			//if(tbid=="tab5")
			//{
			//$(".itemname22").text($(this).parent().parent().find("option:selected").text());
			//}
		});
	}

	function addvaluestocheckout(elid, onetime, month, itemtype) {
		//alert("shouldBe Elid " + elid);
		//alert("Should Be item type" + itemtype);
		if (itemtype == "sel") {
			$(".itemname2").each(function() {
				if ($(this).attr("tab") == elid) {
					var el = $(this).parent().parent();
					var opt = $(this).attr("option");
					if (elid == "tab5") {
						$("#sel_item").find("option").each(function() {
							if ($(this).attr("value") == opt) {
								if ($(this).attr("amount") != "0,0") {
									//find the element in the divs and show it
									var amtlst = $(this).attr("amount");
									var amts = amtlst.split(",");
									el.show();
									if (amts[0] == "0" && amts[1] != "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").show();
										el.find(".checkoutdiv_left_lnk").hide();
										el.show();

									} else if (amts[0] != "0" && amts[1] == "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").hide();
										el.find(".checkoutdiv_left_lnk").show();
										el.show();
									} else if (amts[0] != "0" && amts[1] != "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").show();
										el.find(".checkoutdiv_left_lnk").show();
										el.show();
									} else {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").hide();
										el.find(".checkoutdiv_left_lnk").hide();
										el.hide();
									}
								} else {
									el.hide();
								}
							}
						});
					} else if ( elid = "tab6") {
						$("#sel_item1").find("option").each(function() {
							if ($(this).attr("value") == opt) {
								if ($(this).attr("amount") != "0,0") {
									//find the element in the divs and show it
									var amtlst = $(this).attr("amount");
									var amts = amtlst.split(",");
									el.show();
									if (amts[0] == "0" && amts[1] != "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").show();
										el.find(".checkoutdiv_left_lnk").hide();
										el.show();

									} else if (amts[0] != "0" && amts[1] == "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").hide();
										el.find(".checkoutdiv_left_lnk").show();
										el.show();
									} else if (amts[0] != "0" && amts[1] != "0") {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").show();
										el.find(".checkoutdiv_left_lnk").show();
										el.show();
									} else {
										el.find(".checkoutdiv_left_amt").text(amts[0]);
										el.find(".checkoutdiv_right_amt").text(amts[1]);
										el.find(".checkoutdiv_right_lnk").hide();
										el.find(".checkoutdiv_left_lnk").hide();
										el.hide();
									}
								} else {
									el.hide();
								}
							}
						});
					}

					//copy code here

				}
			});
		} else {
			$(".itemname2").each(function() {
				if ($(this).attr("tab") == elid) {
					var el = $(this).parent().parent();
					if (onetime == "0" && month != "0") {
						el.find(".checkoutdiv_left_amt").text(onetime);
						el.find(".checkoutdiv_right_amt").text(month);
						el.find(".checkoutdiv_right_lnk").show();
						el.find(".checkoutdiv_left_lnk").hide();
						el.show();

					} else if (onetime != "0" && month == "0") {
						el.find(".checkoutdiv_left_amt").text(onetime);
						el.find(".checkoutdiv_right_amt").text(month);
						el.find(".checkoutdiv_right_lnk").hide();
						el.find(".checkoutdiv_left_lnk").show();
						el.show();
					} else if (onetime != "0" && month != "0") {
						el.find(".checkoutdiv_left_amt").text(onetime);
						el.find(".checkoutdiv_right_amt").text(month);
						el.find(".checkoutdiv_right_lnk").show();
						el.find(".checkoutdiv_left_lnk").show();
						el.show();
					} else {
						el.find(".checkoutdiv_left_amt").text(onetime);
						el.find(".checkoutdiv_right_amt").text(month);
						el.find(".checkoutdiv_right_lnk").hide();
						el.find(".checkoutdiv_left_lnk").hide();
						el.hide();
					}
				}
			});
		}

	}

	function removevaluesincheckout(elid, type, selopt, opt) {
		if (selopt == "") {
			if (type == "month") {
				$(".itemname").each(function() {
					if ($(this).attr("tab") == elid) {
						$(this).parent().parent().find(".txtamt1").attr("value", "");
						$(this).parent().parent().find(".don_linkbutton").trigger("click");
					}
				});
			} else {
				$(".itemname").each(function() {
					if ($(this).attr("tab") == elid) {
						$(this).parent().parent().find(".txtamt2").attr("value", "");
						$(this).parent().parent().find(".don_linkbutton").trigger("click");
					}
				});
			}
		} else {
			//alert(elid);
			if (elid == "tab5") {
				$("#sel_item").val(opt).trigger("change");
				if (type == "month") {
					$("#sel_item").parent().parent().find(".txtamt1").attr("value", "0");
				} else {
					$("#sel_item").parent().parent().find(".txtamt2").attr("value", "0");
				}
				$("#sel_item").parent().parent().find(".don_linkbutton").trigger("click");
			} else if ( elid = "tab6") {
				$("#sel_item1").val(opt).trigger("change");
				if (type == "month") {
					$("#sel_item1").parent().parent().find(".txtamt1").attr("value", "0");
				} else {
					$("#sel_item1").parent().parent().find(".txtamt2").attr("value", "0");
				}
				$("#sel_item1").parent().parent().find(".don_linkbutton").trigger("click");
			}

			/*
			 $("#sel_item1").val(opt).trigger("change");
			 if(type=="month")
			 {
			 $("#sel_item1").parent().parent().find(".txtamt1").attr("value","0");
			 }
			 else
			 {
			 $("#sel_item1").parent().parent().find(".txtamt2").attr("value","0");
			 }
			 $("#sel_item1").parent().parent().find(".don_linkbutton").trigger("click");
			 */
		}

	}

	function clickHandlerSetup() {
		$(".checkoutdiv_left_lnk").each(function() {
			$(this).live("click", function() {
				//alert("right");
				var ids = $(this).parent().parent().find(".itemname2").attr("tab");
				if ($(this).parent().parent().find(".itemname2").hasClass("itemname22")) {
					var opt = $(this).parent().parent().find(".itemname2").attr("option");
					removevaluesincheckout(ids, "month", "sel", opt);
				} else {
					removevaluesincheckout(ids, "month", "", "");
				}

			});
		});
		$(".checkoutdiv_right_lnk").each(function() {
			$(this).live("click", function() {
				var ids = $(this).parent().parent().find(".itemname2").attr("tab");
				if ($(this).parent().parent().find(".itemname2").hasClass("itemname22")) {
					var opt = $(this).parent().parent().find(".itemname2").attr("option");
					removevaluesincheckout(ids, "one", "sel", opt);
				} else {
					removevaluesincheckout(ids, "one", "", "");
				}
				//alert(ids);

			});
		});
	}

	function checkifnumber(txtstr) {
		var flag = false;
		var str = txtstr;
		var replaced = str.split(' ').join('');
		if (replaced.length != 0) {
			if (!isNaN(replaced)) {
				flag = true;
			}
		}
		return flag;
	}

	function setifnumber(txtstr) {
		var nm = "";
		var str = txtstr;
		var replaced = str.split(' ').join('');
		if (replaced.length != 0) {
			if (!isNaN(replaced)) {
				if (replaced.indexOf(".") >= 0) {
					replaced = replaced.substring(0, replaced.indexOf("."));
				}
				nm = replaced;
			}
		}
		return nm;
	}

	function cleanNumbers() {
		$(".txtamt1").focusout(function() {
			var vl = $(this).attr("value");
			if (checkifnumber(vl)) {
				$(this).attr("value", setifnumber(vl));
			} else {
				$(this).attr("value", "");
			}
		});
		$(".txtamt2").focusout(function() {
			var vl = $(this).attr("value");
			if (checkifnumber(vl)) {
				$(this).attr("value", setifnumber(vl));
			} else {
				$(this).attr("value", "");
			}
		});
	}

	function setupSel_item_changeHandler() {
		$("#sel_item").change(function() {
			var amts = $(this).find("option:selected").attr("amount");
			var arres = amts.split(",");
			$(this).parent().parent().find(".txtamt1").attr("value", arres[0]);
			$(this).parent().parent().find(".txtamt2").attr("value", arres[1]);
		});

		$("#sel_item1").change(function() {
			var amts = $(this).find("option:selected").attr("amount");
			var arres = amts.split(",");
			$(this).parent().parent().find(".txtamt1").attr("value", arres[0]);
			$(this).parent().parent().find(".txtamt2").attr("value", arres[1]);
		});
	}

	function setupSponsorButtonClick() {
		$(".sponsor-button").click(function() {

			var totalAmount = $("#spantotalamt0").text();
			var itemsInCart = [];

			var rs = $("#spantotalamt0").text().split(" ").join("");
			if (rs != "0") {
				//Create the Cookie first and then redirect the page
				document.cookie = "donate_val=" + totalAmount + ";path=/";

				//get all the items list
				var d_items_txt = "<div class='c11'>";
				$(".checkoutdiv").each(function() {
					//alert($(this).attr("style"));
					if ($(this).attr("style") == "") {
						//var nm = $(this).find(".itemname2").text();
						var nm =" ";
						var itemName = $(this).find(".itemname2").text().replace(/\t/g, '').replace(/\n/g, '');;
						var itemName1 = itemName.replace('\n/g', "");
						var itemName2 = itemName1.replace('\t/g', "");
						nm = itemName2;
						//alert(itemName2 + "From sponsor click");
						var amt1 = $(this).find(".checkoutdiv_left_amt").text();
						var amt2 = $(this).find(".checkoutdiv_right_amt").text();
						var f = $(this).find(".itemname2").attr("fund");
						var a = $(this).find(".itemname2").attr("appeal");

						d_items_txt = d_items_txt + "<div><b>" + nm + "</b><p>" + amt1 + "," + amt2 + "</p><i>" + f + "," + a + "</i></div>";
						itemsInCart[itemsInCart.length] = {
							"itemName" : nm,
							'oneTime' : amt1,
							'recurring' : amt2,
							'fund' : f,
							'appeal' : a
						};
					} else if ($(this).attr("style") == undefined) {
						d_items_txt = d_items_txt + "<div>" + $(this).find(".itemname2").text() + "</div>";
					}
				});
				d_items_txt = d_items_txt + "</div>";
                var donate_val_text = "donate_val_text=" + d_items_txt + ";path=/";
				document.cookie = donate_val_text;

				$.ajax({
					type : "POST",
					url : '/ci/AjaxCustom/storeCartData',
					data : "form=" + JSON.stringify({
						'total' : totalAmount,
						'items' : itemsInCart,
						'donateValCookieContent' : donate_val_text
					}),
					processData : false,
					success : function() {
						document.location.replace("/app/payment/billingContactDetails");
					},
					dataType : 'json'
				});
			}
		});
	}

	function matchAlreadySelectedCart() {

		if (getCookie("donate_single") == "1") {
			document.cookie = "donate_val_text=;path=/";
			document.cookie = "donate_val=0;path=/";
			document.cookie = "donate_single=;path=/";  
		}
	}

	function getCookie(cname) {
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i].trim();
			if (c.indexOf(name) == 0)
				return c.substring(name.length, c.length);
		}
		return "";
	}

	function parseCookieDonate_val() {
		var dval = getCookie("donate_val");
		var dval_text = getCookie("donate_val_text");
		if (dval.length != 0) {
			if (dval != "0") {
				$("#cookie_info").html(dval_text);
				//parse each and every element and fill the values
				//check against the names
				$("#cookie_info").find(".c11").find("div").each(function() {
					var name = $(this).find("b").text();
					var amt = $(this).find("p").text();
					var amt_arr = amt.split(",");
					//find in the first element
					var el1 = $("#sam_item").text();
					var flag = false;
					if (el1.indexOf(name) >= 0) {
						flag = true;
						$("#sam_item").parent().parent().parent().find(".txtamt1").attr("value", amt_arr[0]);
						$("#sam_item").parent().parent().parent().find(".txtamt2").attr("value", amt_arr[1]);
						$("#sam_item").parent().parent().parent().find(".don_linkbutton").trigger("click");
					} else {
						$(".donate-carousel .carousel-content").find(".itemname").each(function() {
							if ($(this).attr("name") != "menu") {
								//alert($(this).text())
								if ($(this).text().indexOf(name) >= 0) {
									flag = true;
									$(this).parent().parent().find(".txtamt1").attr("value", amt_arr[0]);
									$(this).parent().parent().find(".txtamt2").attr("value", amt_arr[1]);
									$(this).parent().parent().find(".don_linkbutton").trigger("click");
								}
							}
						});
					}

					if (flag == false) {
						$("#sel_item").find("option").each(function() {
							if ($(this).text().indexOf(name) >= 0) {
								var ind = $(this).attr("value");
								$("#sel_item").val(ind).trigger("change");
								$("#sel_item").parent().parent().find(".txtamt1").attr("value", amt_arr[0]);
								$("#sel_item").parent().parent().find(".txtamt2").attr("value", amt_arr[1]);
								$("#sel_item").parent().parent().find(".don_linkbutton").trigger("click");
								flag = true;
							}
						});

						if (flag == false) {
							$("#sel_item1").find("option").each(function() {
								if ($(this).text().indexOf(name) >= 0) {
									var ind = $(this).attr("value");
									$("#sel_item1").val(ind).trigger("change");
									$("#sel_item1").parent().parent().find(".txtamt1").attr("value", amt_arr[0]);
									$("#sel_item1").parent().parent().find(".txtamt2").attr("value", amt_arr[1]);
									$("#sel_item1").parent().parent().find(".don_linkbutton").trigger("click");
									flag = true;
								}
							});
						}
					}
					//alert(flag);
				});
			}
		}
	}

function loadCartDataFromServer(){
   
    $.ajax({
                    type : "POST",
                    url : '/ci/AjaxCustom/getCartData',
                    data : "form=" + JSON.stringify({
                        'total' : 'totalAmount',
                        'items' : 'itemsInCart',
                        'donateValCookieContent' : 'donate_val_text'
                    }),
                    processData : false,
                    success : function(data, message) {
                        document.cookie = data.data.donateValCookieContent;
                        document.cookie = "donate_val="+data.data.total+";path=/";
                        buildCartDisplay();
                        clickHandlerSetup();
                        cleanNumbers();
                        setupSel_item_changeHandler();
                        setupSponsorButtonClick();
                        matchAlreadySelectedCart();
                        parseCookieDonate_val();
                        document.cookie = "payment_message1=;path=/";
                        document.cookie = "payment_message=;path=/";
                    },
                    dataType : 'json'
                });
   
   
}

	$(document).ready(function() {
	    document.cookie = "donate_val_text=;path=/";
        document.cookie = "donate_val=0;path=/";
		loadCartDataFromServer();
	}); 
</script>
-->
<!--//<?
    //if(getUrlParm("member")!=null || getUrlParm("ChildID")!=null)
    //{
     //   ?>
     //       <script type="text/javascript">
    //            $(document).ready(function(){                   
     //               $(".checkoutdiv_right").first().hide();
     //           });
     //       </script>
     //   <?          
    //}   
//?>
<div id="cookie_info" style="display:none;"></div>-->