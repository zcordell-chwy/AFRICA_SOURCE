<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_NoSearchResult" class="rn_NoSearchResultMsg rn_Hidden"><?= $this->data['attrs']['label_no_results'] ?></div>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <rn:block id="postLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_SearchResultContent">
        <? if ($this->data['results'] !== null) : ?>
            <div class="rn_SearchResultTitle"><div class="rn_SearchResultTitleAnswer"><?= $this->data['attrs']['label_results'] ?></div></div>
        <? elseif(!$this->data['attrs']['hide_when_no_results']): ?>
            <div class="rn_NoSearchResultMsg"><?= $this->data['attrs']['label_no_results'] ?></div>
        <? endif; ?>
        <rn:block id="topContent"/>
        <? if (is_array($this->data['results']) && count($this->data['results']) > 0) : ?>
        <rn:block id="preResultList"/>
        <table>
        <rn:block id="topResultList"/>
        <? $rowNum = 1; ?>
        <? foreach ($this->data['results'] as $value): ?>
            <rn:block id="resultListItem">
            <tr>
                <td class="rn_SearchResultAnswer">
                    <span class="rn_Element1">
                        <? $typeOfFile = str_replace('-', '_', strtolower($value->fileType)); ?>
                        <?= $this->render('link', array('answer' => $value, 'title' => \RightNow\Utils\Text::truncateText($value->title, $this->data['attrs']['truncate_size']), 'fileType' => $this->data['fileDescription'][$typeOfFile])) ?>
                    </span>
                    <? if($value->textElements && count($value->textElements) > 0) : ?>
                        <div class="rn_SearchResultExcerpt"> 
                            <? foreach ($value->textElements as $excerptSnippet) : ?>
                                <? foreach ($excerptSnippet->snippets as $snippet) : ?>
                                    <span class="rn_SnippetLevel<?= $snippet->level ?>"><?= htmlspecialchars($snippet->text)?></span>
                                <? endforeach; ?>
                            <? endforeach; ?>
                        </div>
                    <? endif; ?>
                </td>
            </tr>
            </rn:block>
            <? $rowNum++; ?>
        <? endforeach; ?>
        <rn:block id="bottomResultList"/>
        </table>
        <rn:block id="postResultList"/>
        <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>
</div>
