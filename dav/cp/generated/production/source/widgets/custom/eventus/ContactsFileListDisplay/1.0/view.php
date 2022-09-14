<?php /* Originating Release: May 2015 */?>

<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
   
    <div class="rn_DataValue<?=$this->data['wrapClass']?>">
        <rn:block id="preList"/>
        <ul>
            <? foreach ($this->data['value'] as $attachment): ?>
               <? if (!($attachment->Private === true) && $attachment->AttachmentUrl): ?>
                <rn:block id="listItem">
                <li>
                    <rn:block id="topListItem"/>
                    <a href="<?=$attachment->AttachmentUrl;?>" target="<?= $attachment->Target ?>">
                        <rn:block id="icon">
                        <?if($attachment->ThumbnailUrl && $attachment->ThumbnailScreenReaderText):?>
                            <img src='<?=$attachment->ThumbnailUrl;?>' class='rn_FileTypeImageThumbnail' alt='<?=\RightNow\Utils\Config::getMessage(THUMBNAIL_FOR_ATTACHED_IMAGE_MSG);?>' />
                            <span class='rn_ScreenReaderOnly'><?=$attachment->ThumbnailScreenReaderText;?></span>
                        <?else:?>
                            <?=$attachment->Icon;?>
                        <?endif;?>
                        </rn:block>
                        <rn:block id="fileName">
                        <?= $attachment->Name ?: $attachment->FileName ?>
                        </rn:block>
                    </a>
                    -  Sent <?=date("m/d/Y g:i A", $attachment->CreatedTime);?>
                   <? if ($this->data['attrs']['display_file_size']): ?>
                    <rn:block id="fileSize">
                    <span class="rn_FileSize">(<?=$attachment->ReadableSize;?>)</span>
                    </rn:block>
                   <? endif; ?>
                    <rn:block id="bottomListItem"/>
                </li>
                </rn:block>
               <? endif; ?>
            <? endforeach; ?>
        </ul>
        <rn:block id="postList"/>
   </div>
   <rn:block id="bottom"/>
</div>