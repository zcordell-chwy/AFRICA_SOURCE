<rn:block id="listItemContent">

<div class="rn_AuthorAvatar">
    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($result->SocialSearch->author, array(
        'size' => $this->data['attrs']['avatar_size'],
        'target' => $this->data['attrs']['target'],
    ))) ?>
</div>
<div class="rn_<?= $result->type ?>_QuestionResult">
    <h3><a href="<?= $result->url ?>" target="<?= $this->data['attrs']['target'] ?>"><?= $this->helper->formatSummary($result->text, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['link_truncate_size']) ?></a></h3>

    <? if(!is_null($result->summary)): ?>
    <div class="rn_Summary">
        <?= $this->helper->formatSummary($result->summary, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['truncate_size']) ?>
    </div>
    <? endif; ?>

    <div class="rn_AdditionalInfo">
        <div class="rn_Counts">
            <div class="rn_CommentCount">
                <?= $result->SocialSearch->commentCount ?>
                <?= $result->SocialSearch->commentCount === 1 ? \RightNow\Utils\Config::getMessage(COMMENT_LC_LBL) : \RightNow\Utils\Config::getMessage(COMMENTS_LC_LBL) ?>
            </div>

            <? if ($result->SocialSearch->bestAnswerCount): ?>
            <div class="rn_BestAnswerCount">
                <?= $result->SocialSearch->bestAnswerCount ?>
                <?= $result->SocialSearch->bestAnswerCount === 1 ? \RightNow\Utils\Config::getMessage(BEST_ANS_LBL) : \RightNow\Utils\Config::getMessage(BEST_ANSWERS_LC_LBL) ?>
            </div>
            <? endif; ?>
        </div>

        <? if ($this->data['attrs']['show_dates']): ?>
        <div class="rn_Timestamps">
            <?= \RightNow\Utils\Config::getMessage(CREATED_LBL) ?> <time><?= $this->helper->formatDate($result->created) ?></time>

            <? if ($result->updated !== $result->created): ?>
            <?= \RightNow\Utils\Config::getMessage(UPDATED_LBL) ?> <time><?= $this->helper->formatDate($result->updated) ?></time>
            <? endif; ?>
        </div>
        <? endif; ?>
    </div>
</div>

</rn:block>
