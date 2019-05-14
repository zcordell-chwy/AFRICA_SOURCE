<rn:block id="preBestAnswerGivenToUser"/>
<div class="rn_BestAnswerGivenToUserActivity">
    <rn:block id="topBestAnswerGivenToUser"/>
    <rn:block id="bestAnswerGivenToUserActivityContent">
    <div class="rn_ActivityContent">
        <div class="rn_ParentContent">
            <?= $this->render('activityUser', array('user' => $action->SocialQuestion->CreatedBySocialUser)) ?>

            <div class="rn_Info">
                <a itemprop="url" href="<?= \RightNow\Utils\Url::defaultQuestionUrl($action->SocialQuestion->ID) ?>">
                    <?= $this->helper->formatPostContent($action->SocialQuestion->Subject, $this->data['attrs']['truncate_size']) ?>
                </a>
                <rn:block id="activityTimestamp">
                <span class="rn_Time">
                    <?= $this->helper->formatTimestamp($action->SocialQuestion->CreatedTime) ?>
                </span>
                </rn:block>
            </div>
        </div>
    </div>
    </rn:block>
    <rn:block id="bottomBestAnswerGivenToUser"/>
</div>
<rn:block id="postBestAnswerGivenToUser"/>
