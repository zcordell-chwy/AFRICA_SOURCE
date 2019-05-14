<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_Subscription">
    <? if ($this->data['js']['subscriptionData']->items !== null) : ?>
        <button class="rn_subscriptionButton"  id="rn_<?=$this->instanceID;?>_SubscribeButton">
        <?=$this->data['js']['subscriptionID'] ? $this->data['attrs']['label_unsub_button'] : $this->data['attrs']['label_sub_button'];?></button>
    <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>