<? $fileClass = 'rn_SearchResultIcon rn_File_' . str_replace('-', '_', strtolower($answer->fileType)) ?>
<span class="<?= $fileClass ?>"><span class="rn_ScreenReaderOnly"><?= $fileType ?></span></span>
<? $href = strpos($answer->href, 'okcsFattach') ? $answer->href : $this->data['js']['answerPageUrl'] . $answer->href ?>
<a id="<?= $answer->answerId ?>" data-id="<?= $answer->docId ?>" href="<?= $href ?>" data-url="<?= $answer->clickThroughUrl ?>"><?= $title ?></a>