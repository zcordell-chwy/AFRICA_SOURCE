<rn:meta javascript_module="mobile"/>
<!DOCTYPE html>
<html lang="#rn:language_code#">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta charset="utf-8"/>
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/mobile" css="site.css, okcs.css"/>
        <rn:head_content/>
        <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png">
        <rn:widget path="utils/ClickjackPrevention"/>
    </head>
    <body>
        <header>
            <rn:widget path="utils/CapabilityDetector" pass_page_set="mobile"/>

            <nav>
                <div class="rn_NavigationBar">
                    <label class="rn_NavigationMenuButton" for="rn_NavigationMenuButtonToggle">#rn:msg:MENU_LBL#</label>
                    <input type="checkbox" id="rn_NavigationMenuButtonToggle" />

                    <ul class="rn_NavigationMenu">
                        <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:SUPPORT_HOME_TAB_HDG#" link="/app/#rn:config:CP_HOME_URL#" pages="home"/></li>
                        <rn:condition config_check="OKCS_ENABLED == true">
                            <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:ANSWERS_HDG#" link="/app/browse" pages="browse, answers/list, answers/detail, answers/intent, answers/answer_view, search"/></li>
                        <rn:condition_else>
                           <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:ANSWERS_HDG#" link="/app/results" pages="answers/list, answers/detail, answers/intent, answers/answer_view, search"/></li>
                       </rn:condition>
                       <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:ASK_QUESTION_HDG#" link="/app/ask" pages="ask, ask_confirm"/></li>
                    </ul>
                </div>

                <div class="rn_LoginStatus">
                    <rn:condition logged_in="false">
                        <rn:widget path="login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview"
                        sub:input_Contact.Emails.PRIMARY.Address:label_input="#rn:msg:EMAIL_ADDR_LBL#"
                        sub:input_Contact.Emails.PRIMARY.Address:required="true"
                        sub:input_Contact.Emails.PRIMARY.Address:validate_on_blur="true"
                        sub:input_Contact.Login:label_input="#rn:msg:USERNAME_LBL#"
                        sub:input_Contact.Login:required="true"
                        sub:input_Contact.Login:validate_on_blur="true"
                        sub:input_Contact.Name.First:required="true"
                        sub:input_Contact.Name.First:label_input="#rn:msg:FIRST_NAME_LBL#"
                        sub:input_Contact.Name.Last:required="true"
                        sub:input_Contact.Name.Last:label_input="#rn:msg:LAST_NAME_LBL#"
                        sub:input_SocialUser.DisplayName:label_input="#rn:msg:DISPLAY_NAME_LBL#"
                        sub:input_Contact.NewPassword:label_input="#rn:msg:PASSWORD_LBL#"
                        sub:login:create_account_fields="Contact.Emails.PRIMARY.Address;Contact.Login;Contact.NewPassword;Contact.FullName"
                        />
                    <rn:condition_else/>
                        <rn:condition is_active_social_user="true">
                            <rn:widget path="login/AccountDropdown" subpages="#rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile, #rn:msg:PUBLIC_PROFILE_LBL# > #rn:config:CP_PUBLIC_PROFILE_URL#/user/#rn:profile:socialUserID#"/>
                        <rn:condition_else/>
                            <rn:widget path="login/AccountDropdown" subpages="#rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile"/>
                        </rn:condition>
                    </rn:condition>
                </div>

                <rn:condition hide_on_pages="home, results, answers/list, social/questions/list, account/profile, utils/create_account, utils/account_assistance, utils/login_form">
                    <div class="rn_SearchBar">
                        <rn:widget path="okcs/OkcsSimpleSearch" icon_path="" report_page_url="/app/results"/>
                    </div>
                </rn:condition>
            </nav>
        </header>

        <div class="rn_Body">
            <div class="rn_MainColumn" role="main">
            <rn:page_content/>
            </div>
        </div>

        <footer class="rn_Footer" role="contentinfo">
            <div class="rn_Container">
                <rn:widget path="feedback/SiteFeedback"/>
                <rn:widget path="utils/PageSetSelector"/>
                <a href="javascript:void(0);" class="rn_ScrollToTop" onClick="window.scroll(0, 0);">#rn:msg:TOP_LBL#</a>
                <div class="rn_Credit">
                    <rn:widget path="utils/OracleLogo"/>
                </div>
            </div>
        </footer>
    </body>
</html>
