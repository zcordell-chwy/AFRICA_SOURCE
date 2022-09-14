<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preItemContainer"/>
    <div class="rn_Items">
        <div id="rn_<?= $this->instanceID ?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
        <rn:block id="itemContainerTop"/>
        <div class="rn_NavigationArea rn_Hidden">
            <? if (!$this->data['attrs']['display_breadcrumbs'] && $this->data['attrs']['label_title']): ?>
            <span class="rn_Title"><?= $this->data['attrs']['label_title'] ?></span>
            <? endif; ?>
            <? if ($this->data['attrs']['display_breadcrumbs']): ?>
            <rn:block id="preBreadCrumb"/>
            <span class="rn_BreadCrumb" role="navigation"></span>
            <rn:block id="postBreadCrumb"/>
            <? endif; ?>
        </div>
        <? /* Item groups inserted here */ ?>
        <rn:block id="itemContainerBottom"/>
    </div>
    <rn:block id="postItemContainer"/>
    <? if ($this->data['attrs']['per_page'] && count($this->data['js']['items']) > $this->data['attrs']['per_page']): ?>
        <rn:block id="paginationSection">
            <div class="rn_ItemPagination">
                <a href="javascript:void(0);" title="<?=$this->data['attrs']['label_screen_reader_previous_page']?>" class="rn_PreviousPage"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_screen_reader_previous_page'] ?></span></a>
                <a href="javascript:void(0);" title="<?=$this->data['attrs']['label_screen_reader_forward_page']?>" class="rn_ForwardPage"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_screen_reader_forward_page'] ?></span></a>
            </div>
        </rn:block>
    <? endif ?>
    <rn:block id="bottom"/>
</div>
