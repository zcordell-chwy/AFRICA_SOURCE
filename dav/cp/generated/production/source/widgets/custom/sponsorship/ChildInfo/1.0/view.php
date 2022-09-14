<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <div class="">
        <? if (isset($this->data['Child'])) : ?>
            <!-- Child Info Preview -->
            <div class="childImgContainer">
                <figure class="childImg">
                    <img src="<?= $this->data['image'] ?>">
                    <div class="hover">
                            <?
                            $summary_lines = array();
                            if (!empty($this->data['Child']->FavoriteSubject->LookupName)) $summary_lines[] = '<span class="subject">' . $this->data["Gender2"] . " favorite subject is " . $this->data['Child']->FavoriteSubject->LookupName . '</span>';
                            if (!empty($this->data['Child']->FavoriteHobby->LookupName)) $summary_lines[] = '<span class="hobby">' . $this->data["Gender2"] . " favorite hobby is " . $this->data['Child']->FavoriteHobby->LookupName  . '</span>';
                            if (!empty($this->data['Child']->Grade->LookupName)) $summary_lines[] = '<span class="Grade">' . $this->data["Gender"] . " is in " . $this->data['Child']->Grade->LookupName  . ' Grade </span>';
                            ?>
                            <span class="summary">
                                <? echo implode('', $summary_lines); ?>
                            </span>
                        </div>

                    <figcaption class="ChildInfo">
                        Please Meet
                        <span class='Name'>
                            <?= $this->data['Child']->FullName; ?>
                        </span>
                        <span class='ChildRef'>
                            <?= $this->data['Child']->ChildRef; ?>
                        </span>
                        <span class='Age Gender'>
                            <?= $this->data['Child']->Age; ?> years old, &nbsp;<?= $this->data['Child']->Gender->LookupName; ?>
                        </span>
                        Born
                        <span class='Birthday'>
                            <?= date('d-M-Y', strtotime($this->data['Child']->Birthday)); ?>
                        </span>
                        <? if(strpos(\RightNow\Utils\Url::getOriginalUrl(),'app/child/sponsor') === FALSE) : ?>
                        <a id="sponsorshipButton">Sponsor me</a>
                        <!-- <a id="sponsorshipButton" href="/app/child/sponsor/id/<?= $this->data['Child']->ID; ?>">Sponsor me</a> -->
                        <? endif; ?>
                    </figcaption>
                </figure>
            </div>
            
            <div class="ChildDescContainer">
                <? if($this->data['Child']->SponsorshipStatus->LookupName == 'Co-Sponsor Needed'): ?> 
                    <b><?= $this->data['Child']->GivenName; ?> needs a co-sponsor to help <? if($this->data['Child']->Gender->LookupName == 'Male'): ?> him<? else : ?> her <? endif; ?> finish secondary school well.</b>
                    </br>
                <? endif; ?>
                <?= $this->data['Child']->GivenName; ?> <?= $this->data['Child']->Community->ChildDesc; ?>
            </div>
            <div class="SponsorCovers">
                <figure class="SponsorCoversImg">
                    <img src="/euf/assets/sponsor/home_page_how_sponsorship_works.png">
                </figure>
            </div>

            <? if ($this->data['attrs']['community']==true) : ?>
                <div class="IntroductionContainer">
                    <?= $this->data['Child']->Community->Introduction; ?>
                </div>

                <div class="CommunityHLContainer">
                    <figure class="CommunityImg">
                        <img src="community/<?= $this->data['Child']->Community->community_highlight_img; ?>">
                    </figure>
                </div>
                <div class="CommunityMapContainer">
                    <figure class="CommunityImg">
                        <img src="community/<?= $this->data['Child']->Community->map_filename; ?>">
                    </figure>
                </div>
            <? endif; ?>
            <? if ($this->data['attrs']['sponsorship']==false) : ?>
                <div class="sponsorshipContainer">
                    <figure class="SponsorshipImg">
                        <img src="community/sponsor.jpg">
                    </figure>
                </div>
            <? endif; ?>
        <? else : ?>
            <h1>There was a problem with your request.</h1>
        <? endif; ?>
    </div>
</div>