<!--
<rn:block id='AccountDropdown-preAccountDropdown'>

</rn:block>
-->
<?php /* Originating Release: May 2021 */?>
<div id="rn_<?=$this->instanceID?>" class="<?=$this->classList?>">
    <rn:block id="preAccountDropdown"/>
    <? if ($this->data['js']['isLoggedIn']): ?>
    <div class="rn_AccountDropdownParent">
        <rn:block id="preDropdownTrigger"/>
        <a class="rn_LoggedInUser rn_AccountDropdownTrigger" href="javascript:void(0);" id="rn_<?=$this->instanceID?>_DropdownButton" role="button" aria-expanded="false" aria-controls="rn_<?=$this->instanceID?>_SubNavigation">
            <? if ($this->data['currentSocialUser']): ?>
                <span class="rn_AvatarHolder">
                	<span class="rn_NoSocialUserIcon"></span>
                </span>
            <? else: ?>
                <span class="rn_NoSocialUserIcon"></span>
            <? endif; ?>
            <span class="rn_DisplayName"><?= $this->data['nameToDisplay'] ?><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_menu_accessibility']?></span></span>
        </a>
        <rn:block id="postDropdownTrigger"/>

        <div id="rn_<?=$this->instanceID?>_SubNavigationParent" tabindex="-1">
            <ul id="rn_<?=$this->instanceID?>_SubNavigation" class="rn_SubNavigation rn_Hidden" role="menu">
                <rn:block id="preDropdownLoggedInList"/>
                <? foreach ($this->data['subpages'] as $subpage): ?>
                    <rn:block id="subNavigationLink">
                    <li role="menuitem"><a href="/app/<?=$subpage['href']?>#rn:session#"><?=$subpage['title']?></a></li>
                    </rn:block>
                <? endforeach; ?>
                <li role="menuitem" class="rn_LogoutLink"><rn:widget path="login/LogoutLink" id="logout"/></li>
                <rn:block id="postDropdownLoggedInList"/>
            </ul>
        </div>
    </div>
        <? if (!$this->data['js']['socialUserId']): ?>
        <rn:widget path="user/UserInfoDialog" id="userinfo"/>
        <? endif; ?>
    <? elseif (\RightNow\Utils\Config::getConfig(PTA_ENABLED)): ?>
        <? if (\RightNow\Utils\Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD)): ?>
        <div class="rn_AccountDropdownParent">
            <a href="javascript:void(0);" id="rn_LoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? elseif (\RightNow\Utils\Config::getConfig(PTA_EXTERNAL_LOGIN_URL) !== ""): ?>
        <div class="rn_AccountDropdownParent">
            <a href="<?=\RightNow\Utils\Url::replaceExternalLoginVariables(0, $_SERVER['REQUEST_URI'])?>" id="rn_DisabledLoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? else: ?>
        <div class="rn_AccountDropdownParent">
            <a href="javascript:void(0);" id="rn_DisabledLoginLink"><?=$this->data['attrs']['label_login']?></a>
        </div>
        <? endif; ?>
    <? else: ?>
    <div class="rn_AccountDropdownParent">
        <a href="javascript:void(0);" role="button" id="rn_LoginLink"><?=$this->data['attrs']['label_login']?></a>
    </div>
    <? endif; ?>
    <rn:widget path="login/LoginDialog" trigger_element="rn_LoginLink" sub_id='login' sub:input_Contact.Address.StateOrProvince:label_input="#rn:msg:STATE_PROV_LBL#" sub:input_Contact.Address.PostalCode:label_input="#rn:msg:POSTAL_CODE_LBL#" sub:input_Contact.Phones.HOME.Number:label_input="#rn:msg:PHONE_NUMBER_LBL#" create_account_fields="Contact.Emails.PRIMARY.Address;Contact.Login;Contact.NewPassword;Contact.Address.Street;Contact.Address.City;Contact.Address.Country;Contact.Address.StateOrProvince;Contact.Address.PostalCode;Contact.Name.First;Contact.Name.Last;Contact.Phones.HOME.Number;Contact.CustomFields.CO.how_did_you_hear;Contact.CustomFields.c.contacttype;Contact.CustomFields.c.anonymous"/>
    <rn:block id="postAccountDropdown"/>
</div>
<!--
<rn:block id='AccountDropdown-preDropdownTrigger'>

</rn:block>
-->

<!--
<rn:block id='AccountDropdown-postDropdownTrigger'>

</rn:block>
-->

<!--
<rn:block id='AccountDropdown-preDropdownLoggedInList'>

</rn:block>
-->

<!--
<rn:block id='AccountDropdown-subNavigationLink'>

</rn:block>
-->

<!--
<rn:block id='AccountDropdown-postDropdownLoggedInList'>

</rn:block>
-->

<!--
<rn:block id='AccountDropdown-postAccountDropdown'>

</rn:block>
-->

<!--
<rn:block id='LogoutLink-top'>

</rn:block>
-->

<!--
<rn:block id='LogoutLink-bottom'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-top'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-preUserInfoErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-postUserInfoErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-preDisplayName'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-postDisplayName'>

</rn:block>
-->

<!--
<rn:block id='UserInfoDialog-bottom'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-top'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-openLoginLink'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-preFormTypeLabel'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postFormTypeLabel'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-orLabel'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-preSignUpLink'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postSignUpLink'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-preLoginErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postLoginErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-preUsername'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postUsername'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-prePassword'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postPassword'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-preSignUpErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-postSignUpErrorMessage'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-assistanceLink'>

</rn:block>
-->

<!--
<rn:block id='LoginDialog-bottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-top'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formTop'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formBottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-bottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-top'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formTop'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formBottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-bottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-top'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formTop'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formBottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-bottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-top'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postServiceButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formTop'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postOpenIDInput'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButton'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-preSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postSubmitButtonLabel'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-formBottom'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-postForm'>

</rn:block>
-->

<!--
<rn:block id='OpenLogin-bottom'>

</rn:block>
-->

<!--
<rn:block id='FormInput-top'>

</rn:block>
-->

<!--
<rn:block id='FormInput-bottom'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-top'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preLabel'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preRequired'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postRequired'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postLabel'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preInput'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-inputTop'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-inputBottom'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postInput'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preHint'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postHint'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preRadioInput'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postRadioInput'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-preRadioLabel'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-postRadioLabel'>

</rn:block>
-->

<!--
<rn:block id='SelectionInput-bottom'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-top'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-label'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-value'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-bottom'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='DateInput-top'>

</rn:block>
-->

<!--
<rn:block id='DateInput-legendTop'>

</rn:block>
-->

<!--
<rn:block id='DateInput-legendBottom'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preYearSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-topYearSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottomYearSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postYearSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preMonthSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-topMonthSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottomMonthSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postMonthSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preDaySelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-topDaySelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottomDaySelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postDaySelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preHourSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-topHourSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottomHourSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postHourSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preMinuteSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-topMinuteSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottomMinuteSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postMinuteSelect'>

</rn:block>
-->

<!--
<rn:block id='DateInput-preHint'>

</rn:block>
-->

<!--
<rn:block id='DateInput-postHint'>

</rn:block>
-->

<!--
<rn:block id='DateInput-bottom'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-top'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-label'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-value'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-bottom'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-top'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preCurrentInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preCurrentLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postCurrentLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postCurrentInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preRequired'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postRequired'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preValidateLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preValidateRequired'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postValidateRequired'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postValidateLabel'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-preValidateInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-postValidateInput'>

</rn:block>
-->

<!--
<rn:block id='PasswordInput-bottom'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postReadOnlyField'>

</rn:block>
-->

<!--
<rn:block id='TextInput-top'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preLabel'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preRequired'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postRequired'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postLabel'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preInput'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postInput'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preHint'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postHint'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preValidateLabel'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preValidateRequired'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postValidateRequired'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postValidateLabel'>

</rn:block>
-->

<!--
<rn:block id='TextInput-preValidateInput'>

</rn:block>
-->

<!--
<rn:block id='TextInput-postValidateInput'>

</rn:block>
-->

<!--
<rn:block id='TextInput-bottom'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-top'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-label'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-value'>

</rn:block>
-->

<!--
<rn:block id='FieldDisplay-bottom'>

</rn:block>
-->

<!--
<rn:block id='FormSubmit-top'>

</rn:block>
-->

<!--
<rn:block id='FormSubmit-preSubmit'>

</rn:block>
-->

<!--
<rn:block id='FormSubmit-postSubmit'>

</rn:block>
-->

<!--
<rn:block id='FormSubmit-bottom'>

</rn:block>
-->

