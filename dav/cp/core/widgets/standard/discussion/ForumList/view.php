<?php /* Originating Release: February 2019 */?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <table id="rn_<?=$this->instanceID;?>_CommunityForumTable">
        <caption><?=$this->data['attrs']['label_caption']?></caption>
        <thead>
            <rn:block id="topHeader"/>
            <tr>
                <rn:block id="headerData">
                <th scope="col"><?=$this->data['productHeader'];?></th>
                <? foreach($this->data['attrs']['show_columns'] as $header):?>                
                    <th scope="col"><?=$this->data['tableHeaders'][$header];?></th>                    
                <? endforeach;?>
                </rn:block>
            </tr>
            <rn:block id="bottomHeader"/>
        </thead>
        <tbody>
            <? if(count($this->data['userProducts']) > 0):
                $url = ($this->data['attrs']['type'] === "product") ? \RightNow\Utils\Config::getConfig(CP_PRODUCTS_DETAIL_URL) . "/p/" : \RightNow\Utils\Config::getConfig(CP_PRODUCTS_DETAIL_URL) . "/c/"; ?>
                <rn:block id="topBody"/>
                <? foreach($this->data['userProducts'] as $id => $data): ?>
                    <rn:block id="preBodyRow"/>
                    <tr>
                        <th scope="row">
                            <a href="/app/<?= $url . $id ?>"><?= $data['name'] ?></a>
                            <? if($this->data['attrs']['show_forum_description'] && $data['desc']): ?>
                                <p><?= \RightNow\Utils\Text::truncateText($data['desc'], $this->data['attrs']['maximum_description_length'], true) ?></p>
                            <? endif;?>
                        </th>
                        <? foreach($this->data['attrs']['show_columns'] as $metadata): 
                            $noActivity = ($metadata === "last_activity") ? $this->data['attrs']['label_no_activity'] : 0; ?>
                            <td>
                                <rn:block id="columnData">
                                    <?= $this->data[$metadata][$id] ? (($metadata === 'last_activity') ? \RightNow\Utils\Date::formatTimestamp($this->data[$metadata][$id], \RightNow\Utils\Date::getDateFormat($this->data['attrs']['last_activity_date_format'])) : $this->data[$metadata][$id]) : $noActivity ?>
                                </rn:block>
                            </td>
                        <? endforeach;?>                   
                    </tr>
                    <rn:block id="postBodyRow"/>
                <? endforeach; ?>
                <rn:block id="bottomBody"/>
            <? endif;?>
        </tbody>
    </table>
    <rn:block id="bottom"/>
</div>