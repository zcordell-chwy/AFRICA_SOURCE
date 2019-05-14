<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?=$this->classList;?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <rn:block id="topContent"/>
        <? if (count($this->data['ratingData']['questions']) > 0) : ?>
                <div class="rn_SectionTitle"></div>
                <? foreach ($this->data['ratingData']['questions'] as $question): ?>
                    <fieldset>
                        <legend class="rn_Bold"><?= $this->data['attrs']['label_title'] ?></legend>
                        <div class="rn_RatingTitle"><?=$question->name;?></div>
                        <?
                            $answerLength = count($question->answers);
                            $index = 1;
                            $answers = $this->sortAnswer($question->answers); 
                        ?>
                        <div>
                            <? foreach ($answers as $answer): ?>
                                <? if ($answerLength === $this->data['attrs']['max_rating_count']) : ?> 
                                    <? $buttonText = sprintf($this->data['attrs']['label_rating'], $answer->name, $this->data['attrs']['max_rating_count']); ?>
                                    <button id="rn_<?=$this->instanceID?>_Rating_<?=$index?>" class="rn_Rating rn_StarRatingInput" data-id="<?=$index?>" title="<?=$buttonText?>" type="button" data-rating="<?= $this->data['ratingData']['surveyRecordID'] ?>:<?= $answer->recordID ?>:<?= $this->data['ratingData']['contentID'] ?>" data-maxRating="<?=count($answers)?>"><span class="rn_ScreenReaderOnly"><?=$buttonText?></span></button>
                                <? else : ?>
                                    <div>
                                        <input id="rn_<?=$this->instanceID?>_Rating_<?=$index?>" type="radio" class="rn_Rating rn_RatingInput" name="survey" data-rating="<?=$this->data['ratingData']['surveyRecordID']?>:<?=$answer->recordID;?>:<?=$this->data['ratingData']['contentID']?>" data-maxRating="<?=count($answers)?>"/>
                                        <label for="rn_<?=$this->instanceID?>_Rating_<?=$index?>" class="rn_RatingLabel"><?=$answer->name?></label>
                                    </div>
                                <? endif; ?>
                                <? $index++; ?>
                            <? endforeach; ?>
                        </div>
                        <button type="submit" class="rn_SubmitButton" id="rn_<?=$this->instanceID;?>_SubmitButton" disabled><?= $this->data['attrs']['label_button'] ?></button>
                        <img id="rn_<?= $this->instanceID ?>_LoadingIcon" class="rn_Hidden" alt="<?= \RightNow\Utils\Config::getMessage(LOADING_LBL) ?>" src="<?= $this->data['attrs']['loading_icon_path'] ?>"/>
                        <span id="rn_<?= $this->instanceID ?>_StatusMessage" class="rn_Hidden"><?= $this->data['attrs']['label_submitting_message'] ?></span>
                        <div id="rn_<?=$this->instanceID;?>_ThanksMessage" class="rn_HiddenMessage rn_Hidden"><?= $this->data['attrs']['label_thanks_msg'] ?></div>
                    </fieldset>
                <? endforeach; ?>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
