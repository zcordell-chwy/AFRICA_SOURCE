<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
<? if($this->data['attrs']['label_title']):?>
    <rn:block id="title">
    <h2><?=$this->data['attrs']['label_title'];?></h2>
    </rn:block>
<? endif;?>
    <rn:block id="preList"/>
    <ul>
    <? for($i = 0; $i < count($this->data['previousAnswers']); $i++):?>
        <rn:block id="listItem">
        <li><a href="<?=$this->data['attrs']['url'].'/a_id/'.$this->data['previousAnswers'][$i][0] . $this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>" target="<?=$this->data['attrs']['target'];?>"><?=$this->data['previousAnswers'][$i][1];?></a></li>
        </rn:block>
    <? endfor;?>
    </ul>
    <rn:block id="postList"/>
<rn:block id="bottom"/>
</div>
