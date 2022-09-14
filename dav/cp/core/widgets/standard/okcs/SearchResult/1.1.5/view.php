<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_NoSearchResult" class="rn_NoSearchResultMsg rn_Hidden">
        <? $noResultsLabel = \RightNow\Utils\Url::getParameter('filterName') !== null ? $this->data['attrs']['label_no_results_in'] . ' ' . \RightNow\Utils\Url::getParameter('filterName') . '.' : $this->data['attrs']['label_no_results']; ?>
        <?= $this->data['attrs']['label_no_results'] ?> <?= $this->data['attrs']['label_expand_query'] ?>
        <span class="rn_ClearFilterMsg"><?= $this->data['attrs']['label_or'] ?> <a role="button" class="rn_ClearResultFilters" href="javascript:void(0)"><?= $this->data['attrs']['label_clear'] ?></a> <?= $this->data['attrs']['label_applied_filters'] ?></span>
    </div>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <rn:block id="postLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_SearchResultContent">
        <? if ($this->data['results'] !== null) : ?>
            <div class="rn_SearchResultTitle"><div class="rn_SearchResultTitleAnswer"><?= $this->data['attrs']['label_results'] ?></div></div>
        <? elseif($this->data['errors'] !== null) : ?>
            <div class="rn_NoSearchResultMsg"><?= $this->data['errors'] ?></div>
        <? elseif(!$this->data['attrs']['hide_when_no_results']): ?>
            <div class="rn_NoSearchResultMsg"><?= $noResultsLabel ?> <?= $this->data['attrs']['label_expand_query'] ?>
            <? if(\RightNow\Utils\Url::getParameter('facet') !== null) : ?>
                <?= $this->data['attrs']['label_or'] ?> <a role="button" class="rn_ClearResultFilters" href="javascript:void(0)"><?= $this->data['attrs']['label_clear'] ?></a> <?= $this->data['attrs']['label_applied_filters'] ?>
            <? endif; ?>
            </div>
        <? endif; ?>
        <rn:block id="topContent"/>
        <? if (is_array($this->data['results']) && count($this->data['results']) > 0) : ?>
        <rn:block id="preResultList"/>
            <?= $this->render('table', $this->data['results']) ?>
        <rn:block id="postResultList"/>
        <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>

    <? if($this->data['historyData']): ?>
        <script type="text/json" <?= 'id="rn_' . $this->instanceID . '_HistoryData"'?>><?= $this->data['historyData'] ?></script>
    <? endif; ?>
</div>
