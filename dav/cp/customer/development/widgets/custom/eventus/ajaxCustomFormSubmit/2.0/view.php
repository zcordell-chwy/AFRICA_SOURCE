<?php /* Originating Release: November 2013 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preSubmit"/>
    <input type="submit" id="rn_<?= $this->instanceID ?>_Button" value="<?= $this->data['attrs']['label_button'] ?>"/>
    <rn:block id="postSubmit"/>
<? if ($this->data['attrs']['loading_icon_path']): ?>
    <rn:block id="preLoadingIcon"/>
    <img id="rn_<?= $this->instanceID ?>_LoadingIcon" class="rn_Hidden" alt="<?= \RightNow\Utils\Config::getMessage(LOADING_LBL) ?>" src="<?= $this->data['attrs']['loading_icon_path'] ?>"/>
    <rn:block id="postLoadingIcon"/>
<? endif; ?>
<? if ($this->data['attrs']['label_submitting_message']): ?>
    <rn:block id="preStatusMessage"/>
    <span id="rn_<?= $this->instanceID ?>_StatusMessage" class="rn_Hidden"><?= $this->data['attrs']['label_submitting_message'] ?></span>
    <rn:block id="postStatusMessage"/>
<? endif; ?>
    <span class="rn_Hidden">
        <input id="rn_<?= $this->instanceID ?>_Submission" type="checkbox" class="rn_Hidden"/>
        <label for="rn_<?= $this->instanceID ?>_Submission" class="rn_Hidden">&nbsp;</label>
    </span>
    <rn:block id="bottom"/>
</div>
