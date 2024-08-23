<rn:meta title="#rn:msg:ACCOUNT_SETTINGS_LBL#" template="standard.php" login_required="true" />
<!--Pivot team to remove inline style-->
<link href="https://www.jqueryscript.net/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
<style>

ol,ul{list-style:none}
blockquote,q{quotes:none}
blockquote:before,blockquote:after,q:before,q:after{content:none}


body{background:#F7F7F7;width:100%;height:100%;}
/*.container { margin:150px auto; max-width:600px;}*/
h2 { margin:30px auto;}
        .ProfilePage h3 {
            display: block;
            padding: 0.25em;
            border-radius: 3px;
            border: 2px solid lightgray;
            color: var(--theme-red);
            background-color: white;
        }

        .ProfilePage h3.rn_Collapsed:before {
            content: "\f067";
            font-family: 'FontAwesome';
            font-size: 16px;
            vertical-align: middle;
            padding: 0.5em;
            color: var(--theme-red);
        }

        .ProfilePage h3.rn_Expanded:before {
            content: "\f068";
            font-family: 'FontAwesome';
            font-size: 16px;
            vertical-align: middle;
            padding: 0.5em;
            color: var(--theme-red);
        }

        .ProfilePage .formGroup {
            padding: 0.5em;
            border: 1px solid lightgray;
            border-top: 0px;
            border-radius: 3px;
            background-color: white;
            display: none;
        }

        #personal_div {
            display: block;
        }

        .searchbox {
    border:1px solid #456879;
    border-radius:6px;
    height: 22px;
    width: 200p;
    margin-top: 5px;

    
}
.btn_sve{
        -webkit-transition: all 0.2s ease-in-out;
  -moz-transition: all 0.2s ease-in-out;
  transition: all 0.2s ease-in-out;
  background: var(--theme-red);
  border-radius: 0.1875em;
  color: #fff;
  font-size: 1em;
  padding: 0.5em 1em;
  border: 0;
  font-weight: 800;
  letter-spacing: 1.1px; 

    }
    </style>
<div id="rn_PageContent" class="rn_AccountOverviewPage" style="background-color:white;padding:1em;">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />

    <?
    // if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){
    ?>
    <div id="rn_PageContent" class="rn_Profile">
        <div class="rn_Padding ProfilePage">
            <div class="rn_Required rn_LargeText">
                #rn:url_param_value:msg#
            </div>
            <br />
            <form id="rn_CreateAccount" onsubmit="return false;">
                <div id="rn_ErrorLocation"></div>
                <h3 id='personal_h' class="rn_Expanded">Personal</h3>
                <div id="personal_div" class="formGroup">
                    <rn:widget path="input/ContactNameInput" required="true" />
                    <rn:widget path="input/FormInput" name="contacts.c$spousefirstname" />
                    <rn:widget path="input/FormInput" name="contacts.c$orgname" />
                    <rn:widget path="input/FormInput" name="contacts.email" label_input="Email Address" required="true" validate_on_blur="true" />
                    <rn:widget path="input/FormInput" name="contacts.ph_home" label_input="Home Phone" />
                    <rn:widget path="input/FormInput" name="contacts.ph_mobile" label_input="Mobile Phone" />
                    <rn:widget path="input/FormInput" name="contacts.ph_office" label_input="Office Phone" />
                    <rn:widget path="input/FormInput" name="contacts.c$bestphone" />
                    
                </div>
                <rn:widget path="navigation/Accordion" item_to_toggle="personal_div" toggle="personal_h" />
                <h3 id='address_h' class="rn_Collapsed">Address</h3>
                <div id="address_div" class="formGroup">
                    <rn:condition language_in="ja-JP,ko-KR,zh-CN,zh-HK,zh-TW">
                        <rn:widget path="input/FormInput" name="contacts.postal_code" />
                        <rn:widget path="input/FormInput" name="contacts.country_id" />
                        <rn:widget path="input/FormInput" name="contacts.prov_id" />
                        <rn:widget path="input/FormInput" name="contacts.city" />
                        <rn:widget path="input/FormInput" name="contacts.street" />
                        <rn:condition_else />
                        <rn:widget path="input/FormInput" name="contacts.street" label_input="#rn:msg:CUSTOM_MSG_STREET_LBL#"/>
                        <rn:widget path="input/FormInput" name="contacts.city" label_input="#rn:msg:CITY_LBL#" />
                        <rn:widget path="input/FormInput" name="contacts.country_id" label_input="#rn:msg:COUNTRY_LBL#" />
                        <rn:widget path="input/FormInput" name="contacts.prov_id" label_input="#rn:msg:STATE_PROV_LBL#" />
                        <rn:widget path="input/FormInput" name="contacts.postal_code" label_input="#rn:msg:POSTAL_CODE_LBL#" />
                        <!--label for="church1" id="rn_TextInput_charch_Label" class="rn_Label">
        Church                                </label>
                        <input type="textbox" id="__searchit1" class="searchbox"-->
                        <!--div id="__searchitWrapper1" style="display:none;vertical-align: top; overflow: hidden; border: 1px solid rgb(128, 128, 128); position: absolute;"-->
                        <div class="container">
                        <rn:widget path="input/SelectionInput" required="false" name="Contact.CustomFields.sponsorship.Church" label_input="Church" />
                        </div>
                        <div class="rn_TextInput rn_Input">
                        <p class="rn_Label">Church, Not In List 
                        <input type="checkbox" id="churchnotinlist" />
                        </p>
                        <p>
                        <input id="churchdetails" type="text" maxlength="50"  placeholder="Please provide Church Name, City and State"disabled="disabled" style="display: none;width: 100%;"> <input type="button" class="btn_sve" name="submit" value="Save Church" id="sub1" disabled="disabled" style="display: none;"/>
                        </p>
                        </div>
                    </rn:condition>
                </div>
                <rn:widget path="navigation/Accordion" item_to_toggle="address_div" toggle="address_h" />
                <h3 id="account_h" class="rn_Collapsed">Account</h3>
                <div id="account_div" class="formGroup">
                    <rn:widget path="input/FormInput" name="contacts.login" required="true" validate_on_blur="true" initial_focus="true" />
                    <a href="/app/account/change_password#rn:session#">#rn:msg:CHG_YOUR_PASSWORD_CMD#</a>
                    <br />
                    <br />
                </div>
                <rn:widget path="navigation/Accordion" item_to_toggle="account_div" toggle="account_h" />
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="/app/utils/submit/profile_updated" error_location="rn_ErrorLocation" />
            </form>

<!--             <h3 id="advocate_h" class="rn_Collapsed"><a class="rn_Questions" href="javascript: void(0)" style="cursor: default">Advocate Details</a></h3>
            <div id="advocate_div" class="formGroup rn_Questions">
                <rn:widget path="reports/Grid" report_id="101825" label_caption="" />
            </div>
            <rn:widget path="navigation/Accordion" item_to_toggle="advocate_div" toggle="advocate_h" /> -->
            <br />
            <br />
        </div>
    </div>
    <?
    // }else{   
    //         header('Location: /app/account/communications/c_id/'.$profile->c_id->value);
    //     }
    ?>
    
</div>

<script type="text/javascript" src="~/../serach_church.js">


</script>
<script>
jQuery(document).ready(function($) {
    $('[name="Contact.CustomFields.sponsorship.Church"]').attr("data-search", "true");
    $('[name="Contact.CustomFields.sponsorship.Church"]').attr("placeholder", "Search church");
	$('[name="Contact.CustomFields.sponsorship.Church"]').selectstyle({
		width  : 400,
		height : 300,
		theme  : 'google',
		onchange : function(val){}
	});
});
</script>