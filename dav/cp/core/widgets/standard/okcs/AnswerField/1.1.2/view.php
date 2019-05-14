<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>" >
    <? if($this->data['attrs']['answer_key'] !== '') : ?>
        <?= $this->render('metadata', $this->data['fieldData']) ?>
    <? else : ?>
        <?= $this->render('attribute', $this->data['fieldData'], $index) ?>
        <? if($this->data['attrs']['type'] !== 'NODE') : ?>
            <?= $this->render('attributeValue', $this->data['fieldData']) ?>
        <? endif; ?>
    <? endif; ?>
</div>