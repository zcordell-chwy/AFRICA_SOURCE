<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="content">
    <? if (strtolower($this->data['js']['viewType']) === 'table'): ?> 
        <div class="yui3-datatable-caption <?=$this->data['js']['showHeaders'] ? '' : 'rn_ScreenReaderOnly';?>"><?= ($this->data['attrs']['type'] === 'browse' && $this->data['attrs']['label_browse_list_title'] === '') ? $this->data['attrs']['label_table_title'] : $this->data['attrs']['label_' . $this->data['attrs']['type'] . '_list_title'] ?></div>
    <? endif; ?> 
    <div id="rn_<?= $this->instanceID ?>_Content" class="rn_Content">
        <? if (strtolower($this->data['js']['viewType']) === 'table'): ?>
            <?= $this->render('table', array('data' => $this->data['articles'], 'type' => $type, 'fields' => $this->data['fields'], 'attrs' => $this->data['attrs'], 'url' => $this->data['js']['answerUrl'])) ?>
        <? else: ?>
            <?= $this->render('list', array('data' => $this->data['articles'], 'type' => $type, 'attrs' => $this->data['attrs'], 'url' => $this->data['js']['answerUrl'])) ?>
        <? endif; ?>
    </div>
    </rn:block>
    <rn:block id="bottom"/>
</div>
