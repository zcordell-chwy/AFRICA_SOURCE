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
    <!-- Contact.Login; -->
    <rn:widget path="custom/login/CustomLoginDialog" trigger_element="rn_LoginLink" sub_id='login' sub:input_Contact.Address.StateOrProvince:label_input="#rn:msg:STATE_PROV_LBL#" sub:input_Contact.Address.PostalCode:label_input="#rn:msg:POSTAL_CODE_LBL#" sub:input_Contact.Phones.HOME.Number:label_input="#rn:msg:PHONE_NUMBER_LBL#" create_account_fields="Contact.Emails.PRIMARY.Address;Contact.NewPassword;Contact.Address.Street;Contact.Address.City;Contact.Address.Country;Contact.Address.StateOrProvince;Contact.Address.PostalCode;Contact.Name.First;Contact.Name.Last;Contact.Phones.HOME.Number;Contact.CustomFields.CO.how_did_you_hear;Contact.CustomFields.c.contacttype;Contact.CustomFields.c.anonymous"/>
    <rn:block id="postAccountDropdown"/>
</div>