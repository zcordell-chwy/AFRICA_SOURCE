<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_WellDark">
        <h4><?= $this->data['attrs']['label_user_contributions'] ?></h4>
        <rn:block id="preList"/>
        <ul>
        <? foreach($this->data['contributions'] as $type => $details): ?>
            <rn:block id="preItem"/>
            <li class="rn_<?= ucfirst($type) ?>">
                <?= sprintf($details['label'], $details['count']) ?>
            </li>
            <rn:block id="postItem"/>
        <? endforeach ?>
        </ul>
        <rn:block id="postList"/>
    </div>
    <rn:block id="bottom"/>
</div>
