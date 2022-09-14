<?php /* Originating Release: February 2019 */?>
<rn:block id="top"/>
<rn:block id="value">
<? if (\RightNow\Utils\Config::getConfig(intl_nameorder)): ?>
    <? if ($this->data['attrs']['display_labels']): ?>
        <rn:widget path="output/FieldDisplay" name="Contact.Name.Last" label="#rn:msg:LAST_NAME_LBL#" />
    <? else: ?>
        <rn:field name="Contact.Name.Last" />
    <? endif; ?>
    <? if (!$this->data['attrs']['short_name']): ?>
        <? if ($this->data['attrs']['display_labels']): ?>
            <rn:widget path="output/FieldDisplay" name="Contact.Name.First" label="#rn:msg:FIRST_NAME_LBL#" />
        <? else: ?>
            <rn:field name="Contact.Name.First" />
        <? endif; ?>
    <? endif; ?>
<? else: ?>
    <? if ($this->data['attrs']['display_labels']): ?>
        <rn:widget path="output/FieldDisplay" name="Contact.Name.First" label="#rn:msg:FIRST_NAME_LBL#" />
    <? else: ?>
        <rn:field name="Contact.Name.First" />
    <? endif; ?>
    <? if (!$this->data['attrs']['short_name']): ?>
        <? if ($this->data['attrs']['display_labels']): ?>
            <rn:widget path="output/FieldDisplay" name="Contact.Name.Last" label="#rn:msg:LAST_NAME_LBL#" />
        <? else: ?>
            <rn:field name="Contact.Name.Last" />
        <? endif; ?>
    <? endif; ?>
<? endif;?>
</rn:block>
<? if (strtolower(\RightNow\Utils\Text::getLanguageCode()) === 'ja-jp'):?>
    <div class="rn_FieldDisplay">
        <? if ($this->data['attrs']['display_labels']): ?>
            <span class="rn_DataLabel"><?=\RightNow\Utils\Config::getMessage(NAME_SUFFIX_LBL);?> </span>
        <? endif; ?>
        <div class="rn_DataValue<?=$this->data['attrs']['left_justify'] ? ' rn_LeftJustify' : '';?>">
            <?=\RightNow\Utils\Config::getMessage(NAME_SUFFIX_LBL);?>
        </div>
    </div>
<? endif;?>
<rn:block id="bottom"/>
