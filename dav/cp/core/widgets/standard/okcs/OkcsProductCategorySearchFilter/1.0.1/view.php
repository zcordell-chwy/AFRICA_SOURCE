<?php /* Originating Release: February 2019 */?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList?>">
<? if ($this->data['js']['viewType'] === 'explorer'): ?>
    <div class="rn_CategoryExplorer">
        <div class="rn_CategoryExplorerContent">
            <? if ($this->data['attrs']['show_headers']): ?>
                <div class="rn_CategoryExplorerTitleDiv"><?= $this->data['attrs']['label_input'] ?></div>
            <? endif; ?>
            <rn:block id="preExplorerTree"/>
            <div id="rn_<?= $this->instanceID ?>_Tree" class="rn_CategoryExplorerContentDiv">
                <? if (count($this->data['results']) > 0): ?>
                    <ul class = "rn_CategoryExplorerList">
                        <? foreach ($this->data['results'] as $categoryList):?>
                            <? $categoryItem = (array) $categoryList ?>
                                <? foreach ($categoryItem as $categoryValue):?>
                                  <? if (!$categoryValue->hasChildren ): ?>
                                        <li class= "rn_CategoryExplorerItem">
                                            <div class="rn_CategoryExplorerLeaf"></div>
                                            <a id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                                            <a class="rn_CategoryExplorerLink" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title=" " href="javascript:void(0)"><?=$categoryValue->name;?></a>
                                        </li>
                                    <? else: ?>
                                        <li class="rn_CategoryExplorerItem">
                                            <a id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Expanded" class="rn_CategoryExplorerExpanded" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                                            <a id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_collapse_icon']?></span></a>
                                            <a class="<?=$categoryValue->selectedClass;?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title=" " href="javascript:void(0)"><?=$categoryValue->name;?></a>
                                            <?= $this->render('categoryNode', array('children' => $categoryValue->children)) ?>
                                        </li>
                                    <? endif; ?>
                                 <? endforeach; ?>
                        <? endforeach; ?>
                    </ul>
                <? else: ?>
                    <div class="rn_NoCategoriesMsg"><?= $this->data['attrs']['label_no_categories'] ?></div>
                <? endif; ?>
                </div>
        </div>
    </div>
<? else: ?>
    <rn:block id="preLink"/>
   <a href="javascript:void(0);" class="rn_ScreenReaderOnly" id="rn_<?= $this->instanceID ?>_LinksTrigger"><? printf($this->data['attrs']['label_screen_reader_accessible_option'], $this->data['attrs']['label_input']) ?>&nbsp;<span id="rn_<?= $this->instanceID ?>_TreeDescription"></span></a>
   <rn:block id="postLink"/>
   <? if ($this->data['attrs']['label_input']): ?>
   <rn:block id="preLabel"/>
   <span class="rn_Label"><?= $this->data['attrs']['label_input'] ?></span>
   <rn:block id="postLabel"/>
   <? endif; ?>
   <rn:block id="preButton"/>
   <button type="button" id="rn_<?= $this->instanceID ?>_<?= $this->data['attrs']['filter_type'] ?>_Button" class="rn_DisplayButton"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_accessible_interface'] ?></span> <span id="rn_<?= $this->instanceID ?>_ButtonVisibleText"><?= $this->data['attrs']['label_nothing_selected'] ?></span></button>
   <rn:block id="postButton"/>
   <div class="rn_ProductCategoryLinks rn_Hidden" id="rn_<?= $this->instanceID ?>_Links"></div>
   <div id="rn_<?= $this->instanceID ?>_TreeContainer" class="rn_PanelContainer rn_Hidden">
       <rn:block id="preTree"/>
       <div id="rn_<?= $this->instanceID ?>_Tree" class="rn_Panel"><? /* Product / Category Tree goes here */?></div>
   <? if ($this->data['attrs']['show_confirm_button_in_dialog']): ?>
       <rn:block id="preConfirmButton"/>
       <div id="rn_<?= $this->instanceID ?>_SelectionButtons" class="rn_SelectionButtons">
           <rn:block id="confirmButtonTop"/>
           <button type="button" id="rn_<?= $this->instanceID ?>_<?= $this->data['attrs']['filter_type'] ?>_ConfirmButton"><?= $this->data['attrs']['label_confirm_button'] ?></button>
           <button type="button" id="rn_<?= $this->instanceID ?>_<?= $this->data['attrs']['filter_type'] ?>_CancelButton"><?= $this->data['attrs']['label_cancel_button'] ?></button>
           <rn:block id="confirmButtonBottom"/>
       </div>
       <rn:block id="postConfirmButton"/>
   <? endif; ?>
       <rn:block id="postTree"/>
   </div>
<? endif; ?>
<rn:block id="bottom"/>
</div>