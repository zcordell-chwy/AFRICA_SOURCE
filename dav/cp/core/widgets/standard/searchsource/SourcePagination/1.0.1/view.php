<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <p class="rn_ScreenReaderOnly" id="rn_<?= $this->instanceID ?>_Description">
        <?= $this->data['attrs']['label_screen_reader_description'] ?>
    </p>
    <rn:block id="prePageList"/>
    <div role="navigation" aria-labelledby="rn_<?= $this->instanceID ?>_Description">
        <ul>
            <? if ($this->data['js']['currentPage'] > 1 && $this->data['js']['size']): ?>
            <rn:block id="prePreviousLink"/>
            <li>
            <?= $this->render('pageLink', array(
                'className' => 'rn_PreviousPage',
                'rel'       => 'previous',
                'iconClass' => 'fa fa-chevron-left',
                'label'     => \RightNow\Utils\Config::getMessage(PREVIOUS_LBL),
                'href'      => $this->helper->pageLink($this->data['js']['currentPage'] - 1, $this->data['js']['filter']),
            )) ?>
            </li>
            <rn:block id="postPreviousLink"/>
            <? endif; ?>

            <? if ($this->data['js']['numberOfPages'] > 1): ?>
            <? for ($i = 1; $i <= $this->data['js']['numberOfPages']; $i++): ?>
            <rn:block id="prePageLink"/>
            <li>
                <a data-rel="<?= $i ?>" class="rn_Page <?= ($i === $this->data['js']['currentPage']) ? 'rn_CurrentPage' : '' ?>" href="<?= $this->helper->pageLink($i, $this->data['js']['filter']) ?>">
                    <?= $i ?>
                </a>
            </li>
            <rn:block id="postPageLink"/>
            <? endfor; ?>
            <? endif; ?>

            <? if ($this->data['js']['total'] > $this->data['js']['size'] && $this->data['js']['offset'] + $this->data['js']['size'] < $this->data['js']['total']): ?>
            <rn:block id="preNextLink"/>
            <li>
            <?= $this->render('pageLink', array(
                'className' => 'rn_NextPage',
                'rel'       => 'next',
                'iconClass' => 'fa fa-chevron-right',
                'label'     => \RightNow\Utils\Config::getMessage(NEXT_LBL),
                'href'      => $this->helper->pageLink($this->data['js']['currentPage'] + 1, $this->data['js']['filter']),
            )) ?>
            </li>
            <rn:block id="postNextLink"/>
        <? endif; ?>
        </ul>
    </div>
    <rn:block id="postPageList"/>
    <rn:block id="bottom"/>
</div>
