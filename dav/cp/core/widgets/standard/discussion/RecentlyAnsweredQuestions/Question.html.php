<rn:block id="preQuestionInfo"/>
<div class="rn_QuestionInfo">
    <rn:block id="questionAvatar">
    <div class="rn_Author" itemprop="author" itemscope itemtype="http://schema.org/Person">
        <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($question->CreatedBySocialUser, array(
            'size' => $this->data['attrs']['avatar_size'],
        ))) ?>
    </div>
    </rn:block>

    <rn:block id="questionSubject">
    <div class="rn_QuestionSubject">
        <h3><a href="<?= $link ?>" class="rn_Content<?= $question->Attributes->ContentLocked ? 'Locked' : 'Unlocked' ?>"><?= $question->Subject ?></a></h3>
        <? if($excerpt): ?>
            <div class="rn_Excerpt"><?= $excerpt ?></div>
        <? endif; ?>
    </div>
    </rn:block>
</div>
<rn:block id="postQuestionInfo"/>
