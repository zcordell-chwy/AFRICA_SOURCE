<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
    <div class="rn_IntentQuestion">
        <rn:block id="question">
        <?= $this->data['question'] ?>
        </rn:block>
    </div>
    <div class="rn_MetaInfo">
    <? if($this->data['attrs']['label_category'] && $this->data['category']): ?>
        <span class="rn_Category">
            <span class="rn_Label">
                <rn:block id="categoryLabel">
                <?=$this->data['attrs']['label_category']?>
                </rn:block>
            </span>
            <rn:block id="category">
            <?=$this->data['category']?>
            </rn:block>
        </span>
    <? endif; ?>
    </div>
    <div class="rn_IntentAnswer">
        <rn:block id="answer">
        <?=$this->data['answer'];?>
        </rn:block>
    </div>
<rn:block id="bottom"/>
</div>
