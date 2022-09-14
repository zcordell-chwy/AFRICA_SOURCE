<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['docID'] !== null && $this->data['docType'] !== 'HTML') : ?>
        <div id="content">
            <? foreach ($this->data['data'] as $data): ?>
                <? $previousDepth = $currentIndex = 1;?>
                <? if ($data['type'] === 'META'): ?>
                    <div class="rn_SectionTitle"></div>
                <? endif; ?>
                <? foreach ($data['content'] as $attribute): ?>
                    <? if ($attribute['depth'] > 0) : ?>
                        <? $value = $attribute['value']; ?>
                        <? if ($attribute['type'] === 'FILE') : ?>
                            <? $value = $attribute['position'] . '#' . $attribute['value']; ?>
                        <? endif; ?>
                        <? if($attribute['depth'] > $previousDepth ) : ?>
                            <div class="rn_Indent">
                        <? endif; ?>
                        <? if($attribute['depth'] < $previousDepth) : ?>
                            <? $diff = $previousDepth - $attribute['depth']; ?>
                            <? for($i = $diff; $i > 0; $i--) : ?>
                                </div>
                            <? endfor; ?>
                        <? endif; ?>
                        <rn:widget path="okcs/AnswerField" type="#rn:php:$attribute['type']#" value="#rn:php:$value#" label="#rn:php:$attribute['name']#" path="#rn:php:$attribute['xPath']#" sub_id="AnswerField">
                        <? if (count($data['content']) === $currentIndex) : ?>
                            <? for($i = $attribute['depth']; $i > 1; $i--) : ?>
                                </div>
                            <? endfor; ?>
                        <? endif; ?>
                        <? $previousDepth = $attribute['depth']; ?>
                    <? endif; ?>
                    <? $currentIndex++; ?>
                <? endforeach; ?>
            <? endforeach; ?>
        </div>
    <? elseif ($this->data['content'] !== null) : ?>
        <div class="rn_ExternalContent"><?= $this->data['content'] ?></div>
    <? else : ?>
        <iframe class="rn_ExternalContent" src="<?= $this->data['docUrl'] ?>"></iframe>
    <? endif ?>
    <rn:block id="bottom"/>
</div>