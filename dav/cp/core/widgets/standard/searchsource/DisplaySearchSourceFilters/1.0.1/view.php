<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <span class="rn_Label">
        <?=$this->data['attrs']['label_input'];?>
    </span>

    <div id="rn_<?=$this->instanceID;?>_FilterContainer">
    <? foreach($this->data['js']['filters'] as $filter):?>
        <div id="rn_<?=$this->instanceID . '_Filter_' . $filter['type'];?>" class="rn_Filter">
            <div class="rn_Label">
                <?=$filter['label'];?>
                <a id="rn_<?=$this->instanceID .'_Remove_' . $i?>" title="<?=$this->data['attrs']['label_filter_remove'];?>" data-type="<?= $filter['type']; ?>" data-value="<?= $filter['value']; ?>" href="javascript:void(0);">
                <? if($this->data['attrs']['show_icon']):?>
                    <span class="rn_Remove"></span>
                <? else:?>
                    <?=$this->data['attrs']['label_filter_remove'];?>
                <? endif;?>
                </a>
            </div>
        </div>
    <? endforeach;?>
    </div>
    <rn:block id="bottom"/>
</div>
