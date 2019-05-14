<? if (count($data) > 0): ?>
    <? $widgetTitle = 'label_' . $attrs['type'] . '_list_title';
    if ($attrs[$widgetTitle] !== '' && $attrs['show_headers']): ?>
        <h2><?= $attrs[$widgetTitle] ?></h2>
    <? endif; ?>
    <ul class="rn_List">
        <? foreach($data as $article): ?>
            <li>
                <?
                    $href = $url . '/a_id/' . $article->answerId;
                    if(!is_null($document->published)) $href .= '/s/d';
                ?>
                <a href="<?= $href ?>" class="rn_Title" target="<?= $attrs['target'] ?>"><?= \RightNow\Utils\Text::escapeHtml($article->title) ?></a>
            </li>
        <? endforeach; ?>
    </ul>
<? else: ?>
    <?=$attrs['label_no_results'];?>
<? endif; ?>