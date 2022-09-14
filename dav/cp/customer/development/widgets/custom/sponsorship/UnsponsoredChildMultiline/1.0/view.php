<? /* Overriding Multiline's view */ ?>
<div id="rn_<?= $this->instanceID; ?>" class="<?= $this->classList ?>">
    <rn:block id="top" />
    <div id="rn_<?= $this->instanceID; ?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="preLoadingIndicator" />
    <div id="rn_<?= $this->instanceID; ?>_Loading"></div>
    <rn:block id="postLoadingIndicator" />
    <div id="rn_<?= $this->instanceID; ?>_Content" class="rn_Content">
        <rn:block id="topContent" />
        <? if (is_array($this->data['reportData']['data']) && count($this->data['reportData']['data']) > 0) : ?>
            <rn:block id="preResultList" />
            <<?= ($this->data['reportData']['row_num']) ? "ol start=\"{$this->data['reportData']['start_num']}\"" : "ul" ?>>
                <rn:block id="topResultList" />
                <? foreach ($this->data['reportData']['data'] as $value) : ?>
                    <rn:block id="resultListItem">
                        <li>
                            <a href="/app/child/detail/id/<?= $value['ID'] ?>">
                                <figure class="childInfo">
                                    <div class="image-container">
                                        <? if (!empty($value['image'])) : ?>
                                            <img src="<?= $value['image'] ?>" loading="lazy">
                                        <? else : ?>
                                            <div class="no-image"></div>
                                        <? endif; ?>

                                        <div class="hover">
                                            <?
                                            $summary_lines = array();
                                            if (!empty($value['Favorite Subject'])) $summary_lines[] = '<span class="subject">' . $value['Favorite Subject'] . '</span>';
                                            if (!empty($value['Hobby'])) $summary_lines[] = '<span class="hobby">' . $value['Hobby']  . '</span>';
                                            if (!empty($value['Grade'])) $summary_lines[] = '<span class="Grade">' . $value['Grade']  . '</span>';
                                            ?>
                                            <span class="summary">
                                                <? echo implode('', $summary_lines); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <figcaption class="description">
                                        <span class="name">
                                            <span class="label">
                                                Please Meet
                                            </span>
                                            <span class="value"><?= $value['Full Name'] ?></span>
                                        </span>
                                        <span class="summary">
                                            <span class="age"><?= $value['Age'] ?></span>, <span class="gender"><?= $value['Gender'] ?></span>
                                        </span>
                                        <span class="birthday">
                                            <span class="label">Born</span>
                                            <span class="value"><?= $value['Birthday'] ?></span>

                                        </span>
                                    </figcaption>
                                </figure>
                            </a>
                            <div class="footer">
                                <!-- <button class="sponsorshipButton" data-childID="<?= $value['ID'] ?>" data-childRate="<?= $value['Rate'] ?>" onclick="javascript:location.href='/app/child/sponsor/id/<?= $value['ID'] ?>'">Sponsor Me!</button> -->
                                <button id="sponsorshipButton" class="sponsorshipButton" data-childID="<?= $value['ID'] ?>" data-childRate="<?= $value['Rate'] ?>" >Sponsor Me!</button>
                                <button class="more-info" data-childID="<?= $value['ID'] ?>" data-childRate="<?= $value['Rate'] ?> " onclick="javascript:location.href='/app/child/detail/id/<?= $value['ID'] ?>'">More Info <i class="fa fa-arrow-right"></i></button>
                            </div>
                        </li>
                    </rn:block>
                <? endforeach; ?>
                <rn:block id="bottomResultList" />
            </<?= ($this->data['reportData']['row_num']) ? "ol" : "ul" ?>>
            <rn:block id="postResultList" />
        <? else : ?>
            <rn:block id="noResultListItem" />
        <? endif; ?>
        <rn:block id="bottomContent" />
    </div>
    <rn:block id="bottom" />
</div>