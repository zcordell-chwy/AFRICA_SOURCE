<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_UsersView">
        <?= $this->render($this->data['attrs']['content_display_type']); ?>
    </div>
    <rn:block id="bottom"/>
</div>