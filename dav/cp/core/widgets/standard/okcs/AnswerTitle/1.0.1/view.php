<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <? if ($this->data['docID'] !== null && $this->data['docType'] !== 'HTML') : ?>
            <div class="rn_AnswerDetail rn_AnswerHeader">
                <h1 id="rn_Summary"><?=$this->data['title']?></h1>
            </div>
        <? endif ?>
    <rn:block id="bottom"/>
</div>