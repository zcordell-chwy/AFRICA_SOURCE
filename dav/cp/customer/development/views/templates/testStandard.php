<!DOCTYPE html>
<html lang="#rn:language_code#">

	<head>
		<meta charset="utf-8"/>
		<title>
			<rn:page_title/>
		</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<!--[if lt IE 9]><script src="/euf/core/static/html5.js"></script><![endif]-->
		<rn:widget path="search/BrowserSearchPlugin" pages="home, answers/list, answers/detail" />
		
		<rn:theme path="/euf/assets/themes/africa" css="site.css,
		{YUI}/widget-stack/assets/skins/sam/widget-stack.css,
		{YUI}/widget-modality/assets/skins/sam/widget-modality.css,
		{YUI}/overlay/assets/overlay-core.css,
		{YUI}/panel/assets/skins/sam/panel.css" />
		
		<rn:head_content/>
		<link rel="icon" href="images/favicon.png" type="image/png"/>
		<rn:widget path="utils/ClickjackPrevention"/>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	</head>
<body class="yui-skin-sam yui3-skin-sam">
    		<div id="rn_SkipNav">
			<a href="#rn_MainContent">#rn:msg:SKIP_NAVIGATION_CMD#</a>
		</div>
		<div id="rn_Header" role="banner">
			<div id="rn_Header-Inner">
				<noscript>
					<h1>#rn:msg:SCRIPTING_ENABLED_SITE_MSG#</h1>
				</noscript>
				<div id="rn_Logo">
					<a href="https://www.africanewlife.org"><img src="/euf/assets/themes/africa/images/anlm-header-logo.png" height="60" width="180" title="Africa New Life" alt="Africa New Life" /></a>
				</div>

				<div id="Site-title" style="position: absolute; top:30px; left:370px;">
					<h2>MY AFRICA NEW LIFE</h2>
				</div>

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
							<a href="javascript:void(0);" id="rn_LoginLink">#rn:msg:LOG_IN_LBL#</a>&nbsp;|&nbsp;<a href="javascript:void(0);">#rn:msg:SIGN_UP_LBL#</a>
							<rn:condition_else>
								<a href="javascript:void(0);" id="rn_LoginLink">#rn:msg:LOG_IN_LBL#</a>&nbsp;|&nbsp;<a href="/app/utils/create_account#rn:session#">#rn:msg:SIGN_UP_LBL#</a>
								<rn:condition hide_on_pages="utils/create_account, utils/login_form, utils/account_assistance">
									<rn:widget path="login/LoginDialog2" trigger_element="rn_LoginLink" open_login_url="/app/#rn:config:CP_LOGIN_URL#" label_open_login_link="#rn:msg:LOG_EXISTING_ACCOUNTS_LBL# <span class='rn_ScreenReaderOnly'>(Facebook, Twitter, Google, OpenID) #rn:msg:CONTINUE_FOLLOWING_FORM_LOG_CMD#</span>"/>
								</rn:condition>
								<rn:condition show_on_pages="utils/create_account, utils/login_form, utils/account_assistance">
									<rn:widget path="login/LoginDialog2" trigger_element="rn_LoginLink" redirect_url="/app/home" open_login_url="/app/#rn:config:CP_LOGIN_URL#" label_open_login_link="#rn:msg:LOG_EXISTING_ACCOUNTS_LBL# <span class='rn_ScreenReaderOnly'>(Facebook, Twitter, Google, OpenID) #rn:msg:CONTINUE_FOLLOWING_FORM_LOG_CMD#</span>"/>
								</rn:condition>
						</rn:condition>
					</rn:condition>
				</div>
			</div><!-- end Header-Inner -->
		</div>
		<div id="rn_Navigation">
			<rn:condition hide_on_pages="utils/help_search">
				<div id="rn_NavigationBar" role="navigation">
					<ul style="float:left;padding-left:100px;">
						<!--<li><rn:widget path="navigation/NavigationTab2" label_tab="Portal Home" link="/app/#rn:config:CP_HOME_URL#" pages="home, "/></li>-->

						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="Sponsor a child" link="/app/home" pages="home "/>
						</li>
						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="Gift for Child" link="/app/give" pages="give, "/>
						</li>
						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="Make a donation" link="/app/donate" pages="donate, "/>
						</li>

						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:ANSWERS_HDG#" link="/app/answers/list" pages="answers/list, answers/detail, answers/intent"/>
						</li>
						<rn:condition config_check="RNW:COMMUNITY_ENABLED == true">
							<li>
								<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:COMMUNITY_LBL#" link="#rn:config:COMMUNITY_HOME_URL:RNW##rn:community_token:?#" external="true"/>
							</li>
						</rn:condition>
						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:ASK_QUESTION_HDG#" link="/app/ask" pages="ask, ask_confirm"/>
						</li>
						<li>
							<rn:widget path="navigation/NavigationTab2" label_tab="#rn:msg:YOUR_ACCOUNT_LBL#" link="/app/account/overview" pages="utils/account_assistance, account/overview, account/profile, account/notif, account/change_password, account/questions/list, account/questions/detail, account/notif/list, utils/login_form, utils/create_account, utils/submit/password_changed, utils/submit/profile_updated"
							subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > /app/account/overview, Account Details > /app/account/profile, #rn:msg:SUPPORT_HISTORY_LBL# > /app/account/questions/list"/>
						</li>
					</ul>
				</div>
			</rn:condition>
		</div>

		<div id="rn_Container" >

			<div id="rn_Body">
				<div id="rn_MainColumn" role="main">
					<a id="rn_MainContent"></a>
					<rn:page_content/>
				</div>

			</div>
		</div>
		</div><!-- end container -->
		<div id="rn_Footer" role="contentinfo">
			<div id="rn_FooterContents">
				<div class="footer-left">

					<h2>Finances</h2>
					<h3>ECFA Member</h3>
					<p>
						Africa New Life is accredited by the ECFA, and is committed to financial transparency, integrity in fundraising, and the proper use of charity resources.
					</p>
					<p>
						<a href="https://www.africanewlife.org/about-us/financial-accountability/"> <img src="/euf/assets/themes/africa/images/ECFA-logo.jpg" /> </a>
					</p>

				</div>

				<div class="footer-right">
					<h2>Contact Us</h2>
					<h3>Keep in Touch</h3>
					<h4>866.979.0393</h4>
					<ul>
						<li>
							<b>Email us to learn more:</b>
						</li>
						<li>
							<a href="mailto:info@africanewlife.org">info@africanewlife.org</a>
						</li>
					</ul>
					<ul>
						<li>
							<b>Find us on Facebook</b>
						</li>
						<li>
							<a href="https://facebook.com/africanewlife">facebook.com/africanewlife</a>
						</li>
					</ul>
					<ul>
						<li>
							<b>visit us at our office:</b>
						</li>
						<li>
							7145 SW Varns St. Suite 201
						</li>
						<li>
							Portland, OR 97223
						</li>
					</ul>

				</div>

			</div>
		</div>
	</body>
</html>