<?php /* Originating Release: May 2015 */?>
<div id="alertContainer" class="rn_Hidden"></div>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <rn:block id="postLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <rn:block id="topContent"/>
        <? if (is_array($this->data['reportData']['data']) && count($this->data['reportData']['data']) > 0): ?>
            <rn:block id="preResultList"/>
            <? if ($this->data['reportData']['row_num']): ?>
                <ol start="<?=$this->data['reportData']['start_num'];?>">
            <? else: ?>
                <ul>   
            <? endif; ?>
            <rn:block id="topResultList"/>
            <? $reportColumns = count($this->data['reportData']['headers']);
               $counter=0;
               foreach ($this->data['reportData']['data'] as $value):?>
                <rn:block id="resultListItem">
                <li>
                    <span class="rn_Element1"><?=$value[0];?></span>
                    <? if($value[1]): ?>
                        <span class="alertLink"><a id="rn_<?=$this->instanceID;?>_<?=$counter?>">more...</a></span>
                    <? endif; ?> 
                    <span class="rn_Hidden" id=rn_<?=$this->instanceID;?>_<?=$counter?>_Detail><?=$value[1];?></span>
                </li>
                </rn:block>
            <? $counter++;
                endforeach; ?>
            <rn:block id="bottomResultList"/>
            <? if ($this->data['reportData']['row_num']): ?>
                </ol>
            <? else: ?>
                </ul>
            <? endif; ?>
            <rn:block id="postResultList"/>
        <? else: ?>
           No results to display.
        <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>
</div>
