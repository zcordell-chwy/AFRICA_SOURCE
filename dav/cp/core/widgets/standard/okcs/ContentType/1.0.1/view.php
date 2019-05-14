<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <ul class="<?= $this->data['attrs']['list_display'] === 'horizontal' ? 'rn_Horizontal' : '' ?>">
            <? $defaultContentType = \RightNow\Utils\Text::escapeHtml($this->data['defaultContentType']);?>
            <? if($this->data['contentTypes'] === null): ?>
                <div class="rn_NoChannelsMsg"><?= $this->data['attrs']['label_no_content_type'] ?></div>
            <? else: ?>
            <?foreach ($this->data['contentTypes'] as $item):?>
                <? if(strlen($defaultContentType) === 0) { ?>
                    <? $defaultContentType = \RightNow\Utils\Text::escapeHtml($item->name);?>
                <? } ?>
                <?
                    $isContentTypeSelected = \RightNow\Utils\Text::escapeHtml(strtoupper($item->referenceKey)) === strtoupper($defaultContentType);
                    $selectedStyleClass = $isContentTypeSelected ? 'rn_Selected' : '';
                ?>
                <li><a id="rn_<?= $this->instanceID ?>_<?=$item->referenceKey;?>" data-id="<?=$item->referenceKey;?>" class="<?= $selectedStyleClass ?>" href="javascript:void(0)"><?= \RightNow\Utils\Text::escapeHtml($item->name); ?></a></li>
            <?endforeach;?>
            <? endif; ?>
        </ul>
    <rn:block id="bottom"/>
</div>