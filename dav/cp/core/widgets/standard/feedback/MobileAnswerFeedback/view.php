<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if ($this->data['attrs']['label_title']): ?>
    <div class="rn_Title"><?=$this->data['attrs']['label_title']?></div>
<? endif;?>
    <rn:block id="preRating"/>
    <div id="rn_<?=$this->instanceID?>_MobileAnswerFeedbackControl" class="rn_MobileAnswerFeedbackControl">
        <div id="rn_<?=$this->instanceID?>_RatingButtons">
            <rn:block id="preYesButton"/>
            <button id="rn_<?=$this->instanceID?>_RatingYesButton"><?=$this->data['attrs']['label_yes_button']?></button>
            <rn:block id="postYesButton"/>
            <rn:block id="preNoButton"/>
            <button id="rn_<?=$this->instanceID?>_RatingNoButton"><?=$this->data['attrs']['label_no_button']?></button>
            <rn:block id="postNoButton"/>
        </div>
        <span id="rn_<?=$this->instanceID?>_ThanksLabel" class="rn_ThanksLabel rn_Hidden">&nbsp;</span>
    </div>
    <rn:block id="postRating"/>
    <rn:block id="preForm"/>
    <form id="rn_<?=$this->instanceID?>_Form" class="rn_MobileAnswerFeedbackForm rn_Hidden" onsubmit="return false;">
        <rn:block id="preDialogPrompt"/>
        <div class="rn_DialogPrompt"><?=$this->data['attrs']['label_dialog_prompt'];?></div>
        <rn:block id="postDialogPrompt"/>
        <div id="rn_<?=$this->instanceID;?>_ErrorMessage"></div>
        <? if (!$this->data['js']['profile']): ?>
            <rn:block id="preEmailLabel"/>
            <label for="rn_<?=$this->instanceID?>_Email"><?=$this->data['attrs']['label_email_address'];?><span class="rn_Required"> <?=\RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL);?></span><span class="rn_ScreenReaderOnly"><?=\RightNow\Utils\Config::getMessage(REQUIRED_LBL)?></span></label>
            <rn:block id="postEmailLabel"/>
            <rn:block id="preEmailInput"/>
            <input id="rn_<?=$this->instanceID?>_Email" class="rn_EmailField" type="email" value="<?= $this->data['js']['email'] ?>"/>
            <rn:block id="postEmailInput"/>
        <? endif;?>
        <rn:block id="preFeedbackLabel"/>
        <label for="rn_<?=$this->instanceID?>_FeedbackText"><?=$this->data['attrs']['label_comment_box'];?><span class="rn_Required"> <?=\RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL);?></span><span class="rn_ScreenReaderOnly"><?=\RightNow\Utils\Config::getMessage(REQUIRED_LBL)?></span></label>
        <rn:block id="postFeedbackLabel"/>
        <rn:block id="preFeedbackInput"/>
        <textarea id="rn_<?=$this->instanceID?>_FeedbackText" rows="4" cols="60"></textarea>
        <rn:block id="postFeedbackInput"/>
    </form>
    <rn:block id="postForm"/>
    <rn:block id="bottom"/>
</div>
