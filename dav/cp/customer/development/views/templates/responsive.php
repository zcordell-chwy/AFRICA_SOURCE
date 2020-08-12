<!DOCTYPE html>
<html lang="#rn:language_code#">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<meta http-equiv="X-Frame-Options" content="allow">
		<title>
			<rn:page_title/>
		</title>
		<link rel="icon" href="/euf/assets/images/favicon.ico" type="image/x-icon" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<!--[if lt IE 9]><script src="/euf/core/static/html5.js"></script><![endif]-->
		<rn:widget path="search/BrowserSearchPlugin" pages="home, answers/list, answers/detail" />
		<rn:theme path="/euf/assets/themes/responsive" css="site.css,
			{YUI}/widget-stack/assets/skins/sam/widget-stack.css,
			{YUI}/widget-modality/assets/skins/sam/widget-modality.css,
			{YUI}/overlay/assets/overlay-core.css,
			{YUI}/panel/assets/skins/sam/panel.css" />
		<rn:head_content/>
		
		<!-- jQuery -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<!-- jQuery UI -->
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
		<!-- jQuery UI Stylesheet -->
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<!-- Magnific Popup core CSS file -->
		<link rel="stylesheet" href="/euf/assets/javascript/magnific-popup/magnific-popup.css">
		<!-- Magnific Popup core JS file -->
		<script src="/euf/assets/javascript/magnific-popup/jquery.magnific-popup.js"></script>
		<!-- Font Awesome -->
		<link rel="stylesheet" href="/euf/assets/css/font-awesome/css/font-awesome.min.css">
		<!--[if lt IE 7]>
		<style>
			.rn_SiteContainer{
				height:100%; }
		</style>
		<![endif]-->
	</head>
	<body class="yui-skin-sam yui3-skin-sam">
	    
	    <rn:condition show_on_pages="payment/checkout">
            <rn:condition config_check="CUSTOM_CFG_inspectlet_enabled == true">
               <rn:widget path="custom/eventus/inspectlet" transaction="" />
            </rn:condition>
        </rn:condition>
        
        
		<div class="rn_SiteContainer">
			<header class="rn_SiteHeaderContainer">
				<div class="rn_SiteHeader rn_Container">
					<div id="rn_LoginStatus" class="ResponsiveRow">
						<div class="ResponsiveCol-12">
							<rn:condition logged_in="true">
								#rn:msg:WELCOME_BACK_LBL#
								<strong>
									<rn:field name="contacts.full_name"/>
								</strong>
								<div>
									<rn:field name="contacts.organization_name"/>
								</div>
								<rn:widget path="login/LogoutLink2"/>
								<rn:condition_else />
								<rn:condition config_check="RNW_UI:PTA_EXTERNAL_LOGIN_URL != null">
									<a href="/app/#rn:config:CP_LOGIN_URL#" id="">#rn:msg:LOG_IN_LBL#</a>&nbsp;|&nbsp;<a href="javascript:void(0);">#rn:msg:SIGN_UP_LBL#</a>
								<rn:condition_else>
									<a href="/app/#rn:config:CP_LOGIN_URL#" id="">#rn:msg:LOG_IN_LBL#</a>&nbsp;|&nbsp;<a href="/app/utils/create_account#rn:session#">#rn:msg:SIGN_UP_LBL#</a>
									<!--								<rn:condition hide_on_pages="utils/create_account, utils/login_form, utils/account_assistance">
										<rn:widget path="login/LoginDialog2" trigger_element="rn_LoginLink" open_login_url="/app/#rn:config:CP_LOGIN_URL#" label_open_login_link="#rn:msg:LOG_EXISTING_ACCOUNTS_LBL# <span class='rn_ScreenReaderOnly'>(Facebook, Twitter, Google, OpenID) #rn:msg:CONTINUE_FOLLOWING_FORM_LOG_CMD#</span>"/>
										</rn:condition>
										<rn:condition show_on_pages="utils/create_account, utils/login_form, utils/account_assistance">
										<rn:widget path="login/LoginDialog2" trigger_element="rn_LoginLink" redirect_url="/app/home" open_login_url="/app/#rn:config:CP_LOGIN_URL#" label_open_login_link="#rn:msg:LOG_EXISTING_ACCOUNTS_LBL# <span class='rn_ScreenReaderOnly'>(Facebook, Twitter, Google, OpenID) #rn:msg:CONTINUE_FOLLOWING_FORM_LOG_CMD#</span>"/>
										</rn:condition>-->
								</rn:condition>
							</rn:condition>
						</div>
					</div>
					<div class="ResponsiveRow">
						<div class="ResponsiveCol-12 no-padding">
							<a id="rn_HeaderLogoLink" href="http://www.africanewlife.org">
								<img src="/euf/assets/images/afnl-header-logo.png" height="38" width="163" title="Africa New Life" alt="Africa New Life" />
							</a>
						</div>
					</div>
					<?
						$this -> CI = get_instance();
						$profile = $this -> CI -> session -> getProfile();
						?>
					<div id="nav-menu" class="ResponsiveRow">
						<div class="ResponsiveCol-12 NavigationCol">
							<rn:condition hide_on_pages="utils/help_search">
								<ul class="nav-menu-list"> 
									<li>
										<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_sponsor_child_nav_link_label#" link="/app/home" pages="home "/>
									</li>
									<li>
										<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_gift_for_child_nav_link_label#" link="/app/give" pages="give, "/>
									</li>
									<li>
										<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_make_donation_nav_link_label#" link="/app/donate" pages="donate, "/>
									</li>
									
									<li>
										<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:YOUR_ACCOUNT_LBL#" link="/app/account/overview/c_id/#rn:php:$profile->c_id->value#" pages="utils/account_assistance, account/overview, account/pledges, account/transactions, account/communications, account/profile, account/notif, account/change_password, account/questions/list, account/questions/detail, account/notif/list, utils/login_form, utils/create_account, utils/submit/password_changed, utils/submit/profile_updated"/>
									</li>
									
								</ul>
								<!-- Mobile nav menu list anchor -->
		                        <div class="nav-menu-list-m-anchor">
		                            <a id="nav-menu-list-m-anchor-button-inactive" class="nav-menu-list-m-anchor-button-inactive" onclick="toggleMobileNavMenu()">&#9776;</a>
		                            <a id="nav-menu-list-m-anchor-button-active" class="nav-menu-list-m-anchor-button-active" onclick="toggleMobileNavMenu()">&#x02A2F;</a>
		                        </div>
		                        <!-- End mobile nav menu list anchor -->
							</rn:condition>
						</div>
					</div>
				</div>
			</header>
			<!-- Start mobile nav menu -->
            <div id="nav-menu-m" class="row nav-menu-m">
                <div class="row">
                    <div class="col-12 col-m-12 no-padding">
                        <ul class="nav-menu-list-m">
                            <li>
                                <rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_sponsor_child_nav_link_label#" link="/app/home" pages="home "/>
                            </li>
                            <li>
                                <rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_gift_for_child_nav_link_label#" link="/app/give" pages="give, "/>
                            </li>
                            <li>
                                <rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_make_donation_nav_link_label#" link="/app/donate" pages="donate, "/>
                            </li>
                            <li>
                                <rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:YOUR_ACCOUNT_LBL#" link="/app/account/overview/c_id/#rn:php:$profile->c_id->value#" pages="utils/account_assistance, account/overview, account/pledges, account/transactions, account/communications, account/profile, account/notif, account/change_password, account/questions/list, account/questions/detail, account/notif/list, utils/login_form, utils/create_account, utils/submit/password_changed, utils/submit/profile_updated"/>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- End mobile nav menu -->
			<!-- End rn_SiteHeader -->
			<div class="rn_PageContentContainer rn_Container ResponsiveContainer">
				<rn:page_content/>
			</div>
			<!-- End rn_Container -->
			<div id="rn_Footer" class="rn_PageFooter ResponsiveContainer" role="contentinfo">
				<div id="rn_FooterContents ResponsiveRow">
					<div class="footer-left ResponsiveCol-6">
						<div class="footer-content-left">
							#rn:msg:CUSTOM_MSG_cp_standard_template_footer_finances_section_HTML_content#
						</div>
					</div>
					<div class="footer-right ResponsiveCol-6">
						<div class="footer-content-right">
							#rn:msg:CUSTOM_MSG_cp_standard_template_footer_contact_us_section_HTML_content#
						</div>
					</div>
				</div>
				<div class="footer-copyright ResponsiveRow">
					#rn:msg:CUSTOM_MSG_cp_standard_template_footer_copyright_content#
				</div>
			</div> <!-- End rn_Footer -->
		</div> <!-- End rn_SiteContainer -->
		<script>
            function toggleMobileNavMenu(){
                var mobileNavMenu = document.getElementById("nav-menu-m"),
                    mobileNavMenuAnchorButtonInactive = document.getElementById("nav-menu-list-m-anchor-button-inactive"),
                    mobileNavMenuAnchorButtonActive = document.getElementById("nav-menu-list-m-anchor-button-active");
                if (mobileNavMenu.className === "row nav-menu-m") {
                    mobileNavMenu.className = "row nav-menu-m nav-menu-m-active";
                    mobileNavMenuAnchorButtonInactive.style.display = "none";
                    mobileNavMenuAnchorButtonActive.style.display = "inline";
                } else {
                    mobileNavMenu.className = "row nav-menu-m";
                    mobileNavMenuAnchorButtonInactive.style.display = "inline";
                    mobileNavMenuAnchorButtonActive.style.display = "none";
                }
            }
        </script>
        <?php if (false !== strpos($_SERVER['REQUEST_URI'], '/app/home/')) { ?>
            <!-- Google Code for Donation Page Landing Conversion Page -->
            <script type="text/javascript">
            /* <![CDATA[ */
            var google_conversion_id = 826427818;
            var google_conversion_label = "hbtZCIet8X4QqpOJigM";
            var google_remarketing_only = false;
            /* ]]> */
            </script>
            <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
            </script>
            <noscript>
            <div style="display:inline;">
            <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/826427818/?label=hbtZCIet8X4QqpOJigM&amp;guid=ON&amp;script=0"/>
            </div>
            </noscript>
		<?php } ?>
		
	</body>
</html>