<rn:meta javascript_module="mobile_may_10"/>
<!DOCTYPE html>
<html lang="#rn:language_code#">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta charset="utf-8"/>
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/mobile" css="site.css"/>
        <rn:head_content/>
        <link rel="icon" href="images/favicon.png" type="image/png">
    </head>
    <body>
        <noscript><h1>#rn:msg:SCRIPTING_ENABLED_SITE_MSG#</h1></noscript>
        <header role="banner">
            <nav id="rn_Navigation" role="navigation">
                <span class="rn_FloatLeft">
                    <rn:widget path="navigation/MobileNavigationMenu" submenu="rn_MenuList"/>
                </span>
                <ul id="rn_MenuList" class="rn_Hidden">
                    <li>
                        <a href="/app/#rn:config:CP_HOME_URL##rn:session#">#rn:msg:HOME_LBL#</a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="rn_ParentMenu">#rn:msg:CONTACT_US_LBL#</a>
                        <ul class="rn_Submenu rn_Hidden">
                            <rn:condition config_check="COMMON:MOD_CHAT_ENABLED == true">
                                <li><a href="/app/chat/chat_launch#rn:session#">#rn:msg:CHAT_LBL#</a></li>
                            </rn:condition>
                            <li><a href="/app/ask#rn:session#">#rn:msg:EMAIL_US_LBL#</a></li>
                            <li><a href="javascript:void(0);">#rn:msg:CALL_US_DIRECTLY_LBL#</a></li>
                            <rn:condition config_check="RNW:COMMUNITY_ENABLED == true">
                                <li><a href="javascript:void(0);">#rn:msg:ASK_THE_COMMUNITY_LBL#</a></li>
                            </rn:condition>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="rn_ParentMenu">#rn:msg:YOUR_ACCOUNT_LBL#</a>
                        <ul class="rn_Submenu rn_Hidden">
                            <rn:condition logged_in="false">
                                <rn:condition config_check="RNW_UI:PTA_EXTERNAL_LOGIN_URL != null">
                                    <li><a href="javascript:void(0);">#rn:msg:SIGN_UP_LBL#</a></li>
                                    <li><a href="javascript:void(0);">#rn:msg:LOG_IN_LBL#</a></li>
                                <rn:condition_else>
                                    <li><a href="/app/utils/create_account#rn:session#">#rn:msg:SIGN_UP_LBL#</a></li>
                                    <li><a href="/app/#rn:config:CP_LOGIN_URL##rn:session#">#rn:msg:LOG_IN_LBL#</a></li>
                                </rn:condition>
                                <li><a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:ACCOUNT_ASSISTANCE_LBL#</a></li>
                            </rn:condition>
                            <li><a href="/app/account/questions/list#rn:session#">#rn:msg:VIEW_YOUR_SUPPORT_HISTORY_CMD#</a></li>
                            <li><a href="/app/account/profile#rn:session#">#rn:msg:CHANGE_YOUR_ACCOUNT_SETTINGS_CMD#</a></li>
                        </ul>
                    </li>
                </ul>
                <span class="rn_FloatRight rn_Search" role="search">
                    <rn:widget path="navigation/MobileNavigationMenu" label_button="#rn:msg:SEARCH_LBL#<img src='images/search.png' alt='#rn:msg:SEARCH_LBL#'/>" submenu="rn_SearchForm"/>
                </span>
                <div id="rn_SearchForm" class="rn_Hidden">
                    
                </div>
            </nav>
        </header>

        <section role="main">
            <rn:page_content/>
        </section>

        <footer role="contentinfo">
            <div>
                <rn:condition logged_in="true">
                    <rn:field name="contacts.email"/><rn:widget path="login/LogoutLink2"/>
                <rn:condition_else />
                    <rn:condition config_check="RNW_UI:PTA_EXTERNAL_LOGIN_URL != null">
                        <a href="javascript:void(0);">#rn:msg:LOG_IN_LBL#</a>
                    <rn:condition_else/>
                        <a href="/app/#rn:config:CP_LOGIN_URL##rn:session#">#rn:msg:LOG_IN_LBL#</a>
                    </rn:condition>
                </rn:condition>
                <br/><br/>
            </div>
            <rn:condition hide_on_pages="utils/guided_assistant">
                <rn:widget path="utils/PageSetSelector"/>
            </rn:condition>
            <div class="rn_FloatLeft"><a href="javascript:window.scrollTo(0, 0);">#rn:msg:ARR_BACK_TO_TOP_LBL#</a></div>
            <!-- <div class="rn_FloatRight">Powered by <a href="https://www.rightnow.com">RightNow</a></div> -->
            <br/><br/>
        </footer>
    </body>
</html>
