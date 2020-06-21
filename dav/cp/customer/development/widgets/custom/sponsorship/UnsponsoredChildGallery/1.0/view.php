<div id="rn_<?=$this -> instanceID; ?>" class="<?= $this -> classList ?> clearfix">
	<!-- Unsponsored Child Image Gallery Container -->
	<div id="rn_<?=$this -> instanceID; ?>_Loading"></div>
    <div id="rn_<?=$this -> instanceID; ?>_Content" class="rn_Content">
    <!-- Unsponsored Child Image Gallery Filters -->
	<? 
		logMessage("Filter Vals:");
		logMessage($this->data['unsponsoredChildren']['metadata']['filters']);
	?>
	<div class="rn_UnsponsoredChildImageGalleryFilters">
		<? if($this->data['unsponsoredChildren']['metadata']['eventName'] != ""): ?>
			<div class="rn_UnsponsoredChildFiltersForm rn_UnsponsoredChildFiltersFormEventInfo">
	             <p>
	                 <?=$this -> data['unsponsoredChildren']['metadata']['eventName']; ?>
	             </p>
	             <p class="description">
	                 <?=$this -> data['unsponsoredChildren']['metadata']['eventDescription']; ?>
	             </p>
	        </div>
     	<? endif; ?>
			<form class="rn_UnsponsoredChildFiltersForm" action="" method="">
			<? if($this->data['unsponsoredChildren']['metadata']['eventName'] == ""): ?>
			
			<label>Priority</label>
            <select class="rn_PriorityFilter">
                <?php 
                    $selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['priority']) ? $this->data['unsponsoredChildren']['metadata']['filters']['priority'] : 0;
                    $options = array('1' => 'High', '2' => 'Medium', '0' => 'All'); 
                    foreach($options as $value => $label): 
                ?>
                    <?php if(intval($value) === $selectedValue): ?>
                        <option value="<?= $value ?>" selected><?= $label ?></option>
                    <?php else: ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <? endif; ?>
			<label>Community</label>
			<select class="rn_CommunityFilter">
				<?php 
					$selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['community']) ? $this->data['unsponsoredChildren']['metadata']['filters']['community'] : 0; 
					if($selectedValue === 0): 
				?>
					<option value="0" selected>All</option>
				<?php else: ?>
					<option value="0">All</option>
				<?php endif; ?>
				<?php foreach($this->data['communities'] as $community): ?>
					<?php if($selectedValue === $community->ID): ?>
						<option value="<?= $community -> ID ?>" selected><?= $community -> Name; ?></option>
					<?php else: ?>
						<option value="<?= $community -> ID ?>"><?= $community -> Name; ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<label>Gender</label>
			<select class="rn_GenderFilter">
				<?php 
					$selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['gender']) ? $this->data['unsponsoredChildren']['metadata']['filters']['gender'] : 0;
					$options = array('0' => 'All', '1' => 'Male', '2' => 'Female'); 
					foreach($options as $value => $label): 
				?>
					<?php if(intval($value) === $selectedValue): ?>
						<option value="<?= $value ?>" selected><?= $label ?></option>
					<?php else: ?>
						<option value="<?= $value ?>"><?= $label ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<label>Age</label>
			<select class="rn_AgeFilter">
				<?php 
					$selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['age']) ? $this->data['unsponsoredChildren']['metadata']['filters']['age'] : 0;		  
					$options = array('0' => 'All', '4' => '4-6', '7' => '7-9', '10' => '10-12', '13' => '13-15', '16' => '16+'); 
					foreach($options as $value => $label): 
				?>
					<?php if(intval($value) === $selectedValue): ?>
						<option value="<?= $value ?>" selected><?= $label ?></option>
					<?php else: ?>
						<option value="<?= $value ?>"><?= $label ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			
			<label>Birthday</label>
			   <div class='rn_birthdayFilter'>
				<select class="rn_MonthFilter">
					<?php 
						$selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['monthofbirth']) ? $this->data['unsponsoredChildren']['metadata']['filters']['monthofbirth'] : 0;
						$options = array('0' => '', '1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'Septembver', '10' => 'October', '11' => 'November', '12' => 'December'); 
						foreach($options as $value => $label): 
					?>
						<?php if(intval($value) === $selectedValue): ?>
							<option value="<?= $value ?>" selected><?= $label ?></option>
						<?php else: ?>
							<option value="<?= $value ?>"><?= $label ?></option>
						<?php endif; ?>
					<?php endforeach; ?>

					
				</select>
				<?php 
					$selectedValue = isset($this->data['unsponsoredChildren']['metadata']['filters']['yearofbirth']) ? $this->data['unsponsoredChildren']['metadata']['filters']['yearofbirth'] : "";
						
				?>
				<input type='text' name='rn_yearofbirth' id='rn_yearofbirth' value="<?=$selectedValue?>" placeholder="Year i.e. 2008"></input>
			   </div>
			<input type="submit" value="Search" onclick="javascript: void(0)"> 
		</form>
	</div>
	<!-- End Unsponsored Child Image Gallery Filters -->
	<div class="rn_UnsponsoredChildImageGalleryContainer">
		<!-- Unsponsored Child Image Gallery -->
		<div class="rn_UnsponsoredChildImageGallery">
			<?php
            $linkIndex = 0;
            $numMatches = count($this -> data['unsponsoredChildren']['data']);
			?>
			<?php if($numMatches > 0): ?>
				<?php foreach($this->data['unsponsoredChildren']['data'] as $child): ?>
				<div class="rn_UnsponsoredChildImageLinkContainer">
					<a id="rn_UnsponsoredChildImageLink<?= $child -> ID ?>" class="rn_UnsponsoredChildImageLink" href="javascript: void(0);"
						data-id="<?= $child -> ID ?>"
						data-name="<?= $child -> FullName ?>"
						data-gender="<?= $child -> Gender ?>"
						data-age="<?= $child -> Age ?>"
						data-birth="<?= $child -> MonthOfBirth . '/' . $child -> DayOfBirth . '/' . $child -> YearOfBirth ?>"
						data-ref="<?= $child -> ChildRef ?>"
						data-rate="<?= $child -> Rate ?>"
						data-bio="<?= $child -> Description ?>"
						data-imgSrc="<?= $child -> imageLocation ?>"
						data-imgTitle="<?= $child -> FullName ?>"
						data-imgAlt="<?= $child -> FullName ?>"
						data-linkIndex="<?= $linkIndex++; ?>"
					>
						<!-- The below loading img gets replaced by the actual image via Custom.Widgets.sponsorship.UnsponsoredChildGallery.loadImages -->
						<img class="rn_UnsponsoredChildImageLoadingIcon" src="/euf/assets/images/loading.gif" />
					</a>
					<? 
                       $advocateKey = null;
                       if(count($this->data['js']['advocacies']) > 0 ){
                           foreach($this->data['js']['advocacies'] as $key => $advocate)
                           {
                              if ( $advocate->ChildId == $child -> ID ){
                                  logMessage("Found match for ".$advocate->ChildId." = ".$child -> ID);
                                  $advocateKey = $key;
                                  logMessage("key =**".$key."**advocateKey**".$advocateKey);
                                  break;
                              } 
                           }
    
                          if($advocateKey !== null){
                              logMessage("Advocate in write line advocate key =**".$advocateKey."**");
                              logMessage($this->data['js']['advocacies'][$advocateKey]);
                        ?>
                          <div class="advocateInfo">Advocated By <?=$this->data['js']['advocacies'][$advocateKey]->ContactName?></div>
                        <?}}?>
				</div>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="rn_NoUnsponsoredChildMatchesMsgContainer">
					No unsponsored children matched your search criteria.
				</div>
			<?php endif; ?>
		</div>
		<!-- End Unsponsored Child Image Gallery -->
		<!-- Unsponsored Child Image Gallery Paginator -->
		<div class="rn_UnsponsoredChildImageGalleryPaginator">
			<?php

            if ($this -> data['attrs']['advocacy_page']) {
                $paginatorLinkURL = '/app/advocacy';
            } else {
                $paginatorLinkURL = '/app/home';
            }

            if (\RightNow\Utils\Url::getParameter('event') != "") {
                $paginatorLinkURL .= "/event/" . \RightNow\Utils\Url::getParameter('event');
            }

            foreach ($this->data['unsponsoredChildren']['metadata']['filters'] as $filterName => $filterValue) {
                if (is_null($filterValue))
                    continue;
                $paginatorLinkURL .= '/' . $filterName . '/' . $filterValue;
            }
            $paginatorLinkURL .= '/page/';
            $currPage = $this -> data['unsponsoredChildren']['metadata']['page'];
            $lastPage = $this -> data['unsponsoredChildren']['metadata']['lastPage'];
            $nextPage = $currPage + 1 <= $lastPage ? $currPage + 1 : null;
            $prevPage = $currPage - 1 >= 1 ? $currPage - 1 : null;
			?>
			<div class="rn_UnsponsoredChildImageGalleryPaginatorLinksContainer" style="<?= $lastPage === 1 ? 'display: none;' : '' ?>">
				<!-- Prev Page Link -->
				<?php if(!is_null($prevPage)): ?>
					<a href="<?= $paginatorLinkURL . $prevPage ?>">Previous</a>
				<?php endif; ?>
				<!-- End Prev Page Link -->
				<!-- Page Links -->
				<?php if($lastPage !== 1): ?>
					<?php for($i = 1; $i <= $lastPage; $i++): ?>
						<?php if($i === $currPage): ?>
							<a class="rn_CurrentPageLink" href="javascript: void(0);"><?= $i ?></a>
						<?php else: ?>
							<a href="<?= $paginatorLinkURL . $i ?>"><?= $i ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				<?php endif; ?>
				<!-- End Page Links -->
				<!-- Next Page Link -->
				<?php if(!is_null($nextPage)): ?>
					<a href="<?= $paginatorLinkURL . $nextPage ?>">Next</a>
				<?php endif; ?>
				<!-- End Next Page Link -->
			</div>
		</div>
		<!-- End Unsponsored Child Image Gallery Paginator -->
	</div>
	<!-- Unsponsored Child Image Gallery Container -->
	</div> <!--End Content -->
</div>