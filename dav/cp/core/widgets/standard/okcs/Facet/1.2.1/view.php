<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?=$this->classList;?>">
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <? if (count($this->data['facets']) > 0) : ?>
        <div id="rn_<?=$this->instanceID;?>_Title" class="rn_FacetsTitle"><?=$this->data['attrs']['label_filter'];?>
            <span class="rn_ClearContainer">[<a role="button" class="rn_ClearFacets" href="javascript:void(0)"><?=$this->data['attrs']['label_clear'];?><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_clear_screenreader'] ?></span></a>]</span>
        </div>
        <div class="rn_FacetsList">
            <rn:block id="top"/>
            <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
            <rn:block id="topContent"/>
            <ul> 
                <? foreach ($this->data['facets'] as $facet): ?>
                    <? if (count($facet->children)): ?>
                        <li>
                        <? if ($facet->desc): ?>
                            <?=$facet->desc;?><ul>
                        <? endif; ?>
                            <? $this->findChildren($facet, $facetHTML, $this->data['attrs']['max_sub_facet_size']); ?>
                        <? if ($facet->desc): ?>
                            </ul>
                        <? endif; ?>
                        </li>
                    <? endif; ?>
                <? endforeach; ?>
            </ul>
        </div>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>