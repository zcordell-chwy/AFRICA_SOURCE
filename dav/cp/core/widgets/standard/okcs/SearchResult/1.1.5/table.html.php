<table>
    <rn:block id="topResultList"/>
        <? $rowNum = 1; ?>
        <? foreach ($this->data['results'] as $value): ?>
            <rn:block id="resultListItem">
            <tr>
                <td class="rn_SearchResultAnswer">
                    <? $fileCss = 'rn_ResultIcon rn_File_' . str_replace('-', '_', strtolower($value->fileType)) ?>
                    <div class="rn_ResultElement">
                        <span class="<?= $fileCss ?>"></span>
                        <span class="rn_Element1">
                            <? $typeOfFile = str_replace('-', '_', strtolower($value->fileType)); ?>
                            <? if ($value->type === 'template') : ?>
                                <? $value->fileType = 'intent'?>
                                <? $typeOfFile = 'intent'?>
                            <? endif; ?>
                            <?= $this->render('link', array('answer' => $value, 'title' => \RightNow\Utils\Text::truncateText($value->title, $this->data['attrs']['truncate_size']), 'fileType' => $this->data['fileDescription'][$typeOfFile])) ?>
                        </span>
                        <? if($value->textElements && count($value->textElements) > 0) : ?>
                            <div class="rn_SearchResultExcerpt" >
                                <? $excerptElement = ''; ?>
                                <? foreach ($value->textElements as $excerptSnippet) : ?>
                                    <? foreach ($excerptSnippet->snippets as $snippet) : ?>
                                        <? if ($excerptSnippet->type === 'HTML') : ?>
                                            <? $excerptElement .= '<span class="rn_SnippetLevel' . $snippet->level . '">' . $snippet->text . '</span>' ?>
                                        <? else : ?>
                                            <? $excerptElement .= '<span class="rn_SnippetLevel' . $snippet->level . '">' . htmlspecialchars($snippet->text) . '</span>' ?>
                                        <? endif; ?>
                                    <? endforeach; ?>
                                <? endforeach; ?>
                                <?= $excerptElement ?>
                            </div>
                        <? endif; ?>
                    </div>
                </td>
            </tr>
            </rn:block>
            <? $rowNum++; ?>
        <? endforeach; ?>
    <rn:block id="bottomResultList"/>
</table>