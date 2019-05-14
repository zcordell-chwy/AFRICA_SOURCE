<rn:meta title="#rn:msg:ACCOUNT_SETTINGS_LBL#" template="responsive.php" login_required="true" />

 <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />
      
    <?if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){?>
    <div id="rn_PageContent" class="rn_Profile">
        <div class="rn_Padding">
            <div class="rn_Required rn_LargeText">
                #rn:url_param_value:msg#
            </div>
            <form id="rn_CreateAccount" onsubmit="return false;">
                <div id="rn_ErrorLocation"></div>
                <h2>Personal</h2>
                <legend>
                    #rn:msg:CONTACT_INFO_LBL#
                </legend>
                <div class="formGroup">

                    <rn:widget path="input/ContactNameInput" required="true"/>
                    <rn:widget path="input/FormInput" name="contacts.c$spousefirstname" />
                    <rn:widget path="input/FormInput" name="contacts.c$orgname" />
                    <rn:widget path="input/FormInput" name="contacts.email" label_input="Email Address" required="true" validate_on_blur="true"/>
                    <rn:widget path="input/FormInput" name="contacts.ph_home"  label_input="Home Phone"/>
                    <rn:widget path="input/FormInput" name="contacts.ph_mobile" label_input ="Mobile Phone"/>
                    <rn:widget path="input/FormInput" name="contacts.ph_office" label_input="Office Phone" />
                    <rn:widget path="input/FormInput" name="contacts.c$bestphone" />
                </div>
                <h2>Address</h2>
                <div class="formGroup">

                    <rn:condition language_in="ja-JP,ko-KR,zh-CN,zh-HK,zh-TW">
                        <rn:widget path="input/FormInput" name="contacts.postal_code" />
                        <rn:widget path="input/FormInput" name="contacts.country_id" />
                        <rn:widget path="input/FormInput" name="contacts.prov_id" />
                        <rn:widget path="input/FormInput" name="contacts.city" />
                        <rn:widget path="input/FormInput" name="contacts.street" />
                        <rn:condition_else />
                        <rn:widget path="input/FormInput" name="contacts.street" />
                        <rn:widget path="input/FormInput" name="contacts.city" />
                        <rn:widget path="input/FormInput" name="contacts.country_id" />
                        <rn:widget path="input/FormInput" name="contacts.prov_id" />
                        <rn:widget path="input/FormInput" name="contacts.postal_code" />
                    </rn:condition>
                </div>
                <h2>Account</h2>
                <div class="formGroup">
                    <rn:widget path="input/FormInput" name="contacts.login" required="true" validate_on_blur="true" initial_focus="true"/>
                    <a href="/app/account/change_password#rn:session#">#rn:msg:CHG_YOUR_PASSWORD_CMD#</a>
                    <br/>
                    <br/>
                </div>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:SAVE_CHANGE_CMD#" on_success_url="/app/utils/submit/profile_updated" error_location="rn_ErrorLocation"/>
            </form>
            
            <h2><a class="rn_Questions" href="javascript: void(0)" style="cursor: default">Advocate Details</a></h2>
            <div class="rn_Questions">
                <rn:widget path="reports/Grid" report_id="100775"  label_caption="" />
            </div> 
        </div>
    </div>
    <?}else{   
            header('Location: /app/account/communications/c_id/'.$profile->c_id->value);
        }?>
</div>