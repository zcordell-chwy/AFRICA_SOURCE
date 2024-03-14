<!DOCTYPE html>
<html lang="#rn:language_code#">
<rn:meta clickstream="chat_landing" javascript_module="standard" />

<head>
    <meta charset="utf-8" />
    <title>
        <rn:page_title />
    </title>
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" /> -->
    <meta name="viewport" content="width=375, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <!--[if lt IE 9]><script src="/euf/core/static/html5.js"></script><![endif]-->
    <rn:widget path="search/BrowserSearchPlugin" pages="home, answers/list, answers/detail" />
    <rn:theme path="/euf/assets/themes/standard" css="site.css, pivot.css,          
            {YUI}/widget-stack/assets/skins/sam/widget-stack.css,
            {YUI}/widget-modality/assets/skins/sam/widget-modality.css,
            {YUI}/overlay/assets/overlay-core.css,
            {YUI}/panel/assets/skins/sam/panel.css" /> 
    <rn:head_content />
    <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png" />
    <rn:widget path="utils/ClickjackPrevention" />
    <rn:widget path="utils/AdvancedSecurityHeaders" />
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
</head>
<?php $page = $this->page;
if ($page == 'event_home')
    $page = 'home';
?>

<body class="yui-skin-sam yui3-skin-sam page-id-<?php echo $page; ?>" itemscope itemtype="https://schema.org/WebPage">
    <!--<rn:condition show_on_pages="account/letters, account/overview,account/transactions,account/pledges, payment/checkout">
        <rn:condition config_check="CUSTOM_CFG_inspectlet_enabled == true">
            <rn:widget path="custom/eventus/inspectlet" transaction="" />
        </rn:condition>
    </rn:condition>-->
    <a href="#rn_MainContent" class="rn_SkipNav rn_ScreenReaderOnly">#rn:msg:SKIP_NAVIGATION_CMD#</a>

    <header>
        <rn:widget path="utils/CapabilityDetector" />
        <div class="rn_Container">
            <div class="site-branding">
                <a href="https://africanewlife.org">
                    <img src="/euf/assets/themes/standard/images/ANLMLogo.svg" alt="Africa New Life logo" />
                </a>
            </div>
            <nav>
                <div class="rn_NavigationBar" aria-label="#rn:msg:MAIN_NAVIGATION_BAR_LBL#">
                    <input type="checkbox" id="rn_NavigationMenuButtonToggle" class="rn_ScreenReaderOnly" />
                    <label class="rn_NavigationMenuButton" for="rn_NavigationMenuButtonToggle">
                        #rn:msg:MENU_LWR_LBL#
                    </label>

                    <ul class="rn_NavigationMenu">
                        <li>
                            <rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_sponsor_child_nav_link_label#" link="/app/home" pages="home" />
                        </li>
                        <li>
                            <rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_gift_for_child_nav_link_label#" link="/app/give" pages="give" />
                        </li>
                        <li>
                            <rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:CUSTOM_MSG_cp_standard_template_support_a_program#" link="/app/donate" pages="donate" />
                        </li>
                        <!-- <li>
                            <rn:widget path="navigation/NavigationTab" label_tab="DONATE" link="/app/singleDonation/f_id/5" pages="singleDonation" />
                        </li>
                        <li>
                            <rn:widget path="navigation/NavigationTab" label_tab="WOMENS MINISTRY" link="/app/womens_ministry" pages="WOMENSMINISTRY" />
                        </li>   
						<li>
                            <rn:widget path="navigation/NavigationTab" label_tab="EVENT" link="/app/home/event/479" pages="EVENT" />
                        </li>                         -->
                    </ul>
                </div>
                <? $CI = & get_instance();?>
                <div class="rn_LoginStatus">
                    <!--rn:condition logged_in="false">
                        <rn:widget path="custom/login/AccountDropdown" open_login_providers="" label_open_login_intro="" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview" sub:input_Contact.Emails.PRIMARY.Address:label_input="#rn:msg:EMAIL_ADDR_LBL#" sub:input_Contact.Emails.PRIMARY.Address:required="true" sub:input_Contact.Emails.PRIMARY.Address:validate_on_blur="false" sub:input_Contact.Login:label_input="#rn:msg:USERNAME_LBL#" sub:input_Contact.Login:required="true" sub:input_Contact.Login:validate_on_blur="false" sub:input_Contact.Name.First:required="true" sub:input_Contact.Name.First:label_input="#rn:msg:FIRST_NAME_LBL#" sub:input_Contact.Name.Last:required="true" sub:input_Contact.Name.Last:label_input="#rn:msg:LAST_NAME_LBL#" sub:input_SocialUser.DisplayName:label_input="#rn:msg:DISPLAY_NAME_LBL#" sub:input_Contact.NewPassword:label_input="#rn:msg:PASSWORD_LBL#" />
                        <rn:condition_else />
                            <rn:widget path="custom/login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview,  #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile" />
                    </rn:condition-->
                   
                <rn:condition logged_in="true">
                <!--strong><rn:field name="Contact.LookupName"/><rn:condition language_in="ja-JP">#rn:msg:NAME_SUFFIX_LBL#</rn:condition></strong>&nbsp;|
                <rn:widget path="login/BasicLogoutLink" label="#rn:msg:LOGOUT_CMD#"/>&nbsp;|
                <a href="/app/account/overview#rn:session#">#rn:msg:YOUR_ACCOUNT_LBL#</a-->
                <div  style="float:right">
                <!--rn:widget path="login/LogoutLink" /-->
                
                <rn:widget path="login/AccountDropdn" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview,  #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile" /-->
           
                </div>
                <rn:condition_else />
                <rn:widget path="custom/login/AccountDropdown" open_login_providers="" label_open_login_intro="" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview" sub:input_Contact.Emails.PRIMARY.Address:label_input="#rn:msg:EMAIL_ADDR_LBL#" sub:input_Contact.Emails.PRIMARY.Address:required="true" sub:input_Contact.Emails.PRIMARY.Address:validate_on_blur="false" sub:input_Contact.Login:label_input="#rn:msg:USERNAME_LBL#" sub:input_Contact.Login:required="true" sub:input_Contact.Login:validate_on_blur="false" sub:input_Contact.Name.First:required="true" sub:input_Contact.Name.First:label_input="#rn:msg:FIRST_NAME_LBL#" sub:input_Contact.Name.Last:required="true" sub:input_Contact.Name.Last:label_input="#rn:msg:LAST_NAME_LBL#" sub:input_SocialUser.DisplayName:label_input="#rn:msg:DISPLAY_NAME_LBL#" sub:input_Contact.NewPassword:label_input="#rn:msg:PASSWORD_LBL#" />
                         </rn:condition>
                </div>
            </nav>
        </div>
    </header>

    <div class="rn_Body">
        <div class="rn_container">
            <div class="rn_MainColumn" role="main">
                <a id="rn_MainContent"></a>
                <rn:page_content />
            </div>
        </div>
    </div>
    <rn:condition show_on_pages="home">
        <div class="footer_sponsor">
            <div class="panel1">
                HOW SPONSORSHIP WORKS
            </div>
            <div class="panel2">
                #rn:msg:CUSTOM_MSG_CP_HOW_SPONSORSHIP_WORKS#
            </div>
            <div class="panel3">
                <figure class="giftImg">
                    <img src="/euf/assets/sponsor/home_page_how_sponsorship_works.png">
                </figure>
            </div>
        </div>
    </rn:condition>
    <footer id="rn_Footer">
        <div class="top">
            <div class="rn_Container">
                <div class="widgets">
                    <div class="widget widget-1">
                        <h3 class="widget-title">Finances</h3>
                        <h4>ECFA Member</h4>
                        <p>Africa New Life is accredited by the ECFA, and is committed to financial transparency, integrity in fundraising, and the proper use of charity resources.</p>
                        <img src="../../themes/standard/images/ECFA-logo.png" alt="ECFA" />
                    </div>
                    <div class="widget widget-2">
                        <h3 class="widget-title">Keep in Touch</h3>
                        <!-- <h4>Phone</h4>
                        <p>866.979.0393</p>
                        <h4>Email</h4> -->
                        <!-- <p>info@africanewlife.org</p> -->
                        <!-- <h4>Facebook</h4> -->
                        <!-- <p>facebook.com/africanewlife</p> -->
                        <div class="footerlinks">
                            <div>
                                <a class = "footerlink" href="mailto:info@africanewlife.org" target="_blank"><i class="fa fa-envelope"></i></a>
                            </div>

                            <div>
                                <a class = "footerlink" href="https://facebook.com/africanewlife" target="_blank"><i class="fa fa-facebook"></i></a>
                            </div>

                            <div>
                                <a class = "footerlink" href="https://www.youtube.com/channel/UCLfB9JKKiOn1F6Lw1xAKI6g/videos" target="_blank"><i class="fa fa-youtube"></i></a>
                            </div>

                            <div>
                                <a class = "footerlink" href="https://www.instagram.com/africanewlife/" target="_blank"><i class="fa fa-instagram"></i></a>
                            </div>
                        </div>

                    </div>
                    <div class="widget widget-3">
                        <!-- <img src="../../themes/standard/pivot/images/Office location map.jpg" alt="map" /> -->
                        <div class="visit">
                            <h3 class="widget-title">Visit Us</h3>
                            <h4>Address</h4>
                            <!-- <p>7405 SW Tech Center Dr. #144 Portland, OR 97223</p>  -->
                            <a href="https://goo.gl/maps/Q5KYCGDDCnaYHNdP8 " target="_blank">7405 SW Tech Center Dr. #144 Portland, OR 97223</a>                           
                            <h4>Monday - Thursday</h4>
                            <p>#rn:msg:CUSTOM_MSG_CP_FOOTER_TIMINGS_1#</p>
                            <h4>Friday</h4>
                            <p>#rn:msg:CUSTOM_MSG_CP_FOOTER_TIMINGS_2#</p>
                        </div>
                    </div>
                </div>
                <rn:widget path="search/ProductCategoryList" report_page_url="/app/products/detail" />
                <div class="rn_Misc">
                    <rn:widget path="utils/PageSetSelector" />
                    <!-- <rn:widget path="utils/OracleLogo" /> -->
                </div>
            </div>
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
                        <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/826427818/?label=hbtZCIet8X4QqpOJigM&amp;guid=ON&amp;script=0" />
                    </div>
                </noscript>
            <?php } ?>
        </div>
        <div class="bottom">
            <div class="rn_Container">
                &copy;<?php echo date('Y'); ?> Africa New Life Ministries 501(c)(3). All Rights Reserved.
            </div>
        </div>
    </footer>
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
</body>

</html>