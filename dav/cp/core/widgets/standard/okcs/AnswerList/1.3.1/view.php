<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="content">
    <div id="rn_<?= $this->instanceID ?>_Alert_NoResults" role="alert" class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_screen_reader_search_no_results_alert'] ?></div>
    <div id="rn_<?= $this->instanceID ?>_Alert_Results" role="alert" class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_screen_reader_search_success_alert'] ?></div>
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
