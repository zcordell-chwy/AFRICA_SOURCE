<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>" >
    <rn:block id="top"/>
    <fieldset>
        <rn:block id="preLegend"/>
        <legend><?= $this->data['attrs']['label_header'] ?></legend>
        <rn:block id="postLegend"/>
        <? foreach($this->data['channelData'] as $channel => $channelData): ?>
            <rn:block id="preChannel"/>
            <?= $this->render($channelData['view'], array('channel' => $channel, 'channelData' => $channelData)) ?>
            <rn:block id="postChannel"/>
        <? endforeach ?>
    </fieldset>
    <rn:block id="bottom"/>
</div>
