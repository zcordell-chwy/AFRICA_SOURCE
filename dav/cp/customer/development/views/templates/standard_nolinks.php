<!DOCTYPE html>
<html lang="#rn:language_code#">
	<head>
		<meta charset="utf-8"/>
		<title>
			<rn:page_title/>
		</title>
		<link rel="icon" href="/euf/assets/images/favicon.ico" type="image/x-icon" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<!--[if lt IE 9]><script src="/euf/core/static/html5.js"></script><![endif]-->
		<rn:widget path="search/BrowserSearchPlugin" pages="home, answers/list, answers/detail" />
		<rn:theme path="/euf/assets/themes/africa" css="site.css,
			{YUI}/widget-stack/assets/skins/sam/widget-stack.css,
			{YUI}/widget-modality/assets/skins/sam/widget-modality.css,
			{YUI}/overlay/assets/overlay-core.css,
			{YUI}/panel/assets/skins/sam/panel.css" />
		<rn:head_content/>
		<rn:widget path="utils/ClickjackPrevention"/>
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
		<style>
			#rn_Navigation{
				visibility: hidden;
			}
			#rn_Footer a{
				pointer-events: none;
   				cursor: default;
			}
		</style>
	</head>
	<body class="yui-skin-sam yui3-skin-sam">
		<div class="rn_SiteContainer">
			<header class="rn_SiteHeaderContainer">
				<div class="rn_SiteHeader rn_Container">
					<div id="rn_LoginStatus">
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
					<a id="rn_HeaderLogoLink" href="https://www.africanewlife.org">
						<img src="/euf/assets/images/afnl-header-logo.png" height="38" width="163" title="Africa New Life" alt="Africa New Life" />
					</a>
					<?
						$this -> CI = get_instance();
						$profile = $this -> CI -> session -> getProfile();
						?>
					<div id="rn_Navigation">
						<rn:condition hide_on_pages="utils/help_search">
							<ul>
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
									<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:YOUR_ACCOUNT_LBL#" link="/app/account/overview/c_id/#rn:php:$profile->c_id->value#" pages="utils/account_assistance, account/overview, account/profile, account/notif, account/change_password, account/questions/list, account/questions/detail, account/notif/list, utils/login_form, utils/create_account, utils/submit/password_changed, utils/submit/profile_updated"/>
								</li>
								<rn:condition config_check="RNW:COMMUNITY_ENABLED == true">
									<li>
										<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:COMMUNITY_LBL#" link="#rn:config:COMMUNITY_HOME_URL:RNW##rn:community_token:?#" external="true"/>
									</li>
								</rn:condition>
							</ul>
						</rn:condition>
					</div>
				</div>
			</header>
			<!-- End rn_SiteHeader -->
			<div class="rn_PageContentContainer rn_Container">
				<rn:page_content/>
			</div>
			<!-- End rn_Container -->
			<div id="rn_Footer" class="rn_PageFooter" role="contentinfo">
				<div id="rn_FooterContents">
					<div class="footer-left">
						<div class="footer-content-left">
							#rn:msg:CUSTOM_MSG_cp_standard_template_footer_finances_section_HTML_content#
						</div>
					</div>
					<div class="footer-right">
						<div class="footer-content-right">
							#rn:msg:CUSTOM_MSG_cp_standard_template_footer_contact_us_section_HTML_content#
						</div>
					</div>
					<div class="footer-copyright">
						#rn:msg:CUSTOM_MSG_cp_standard_template_footer_copyright_content#
					</div>
				</div>
			</div> <!-- End rn_Footer -->
		</div> <!-- End rn_SiteContainer -->
	</body>
</html>