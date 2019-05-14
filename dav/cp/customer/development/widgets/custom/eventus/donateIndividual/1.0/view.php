<?php
$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();
$itnum = 0;
$IndividualFunds = $this -> data['Items'];
$i=0; 
 	foreach( $this -> data['Items'] as $Item)	{ 	
 	$itemObjID = $Item -> ID; 	
 	
     	$i++; ?>
     		
				<div class="rn_cartCarousel">
					<div >
						<?php echo "<img width='140px' height='88px' src='" . str_replace($baseURL, "", $Item -> PhotoURL) . "' alt='#' title='" . $Item -> Description . "' />"; ?>
					</div>
					
					<div><b id="rn_<?= $this -> instanceID ?>itemTag" class="rn_cart_itemIDTag"  itemId="<?php echo $Item -> ID; ?>"
					 fund="<?php echo $Item -> DonationFund; ?>"
					  appeal="<?php echo $Item -> DonationAppeal; ?>"  >
					<?php echo $Item -> Title; ?></b>					
					</div>
					<p class="txtfield_box">
					
					
					<div><b class="rn_cart_onetime">One Time:</b> <input type="text" size="10" class="txtamt1" id="rn_<?= $this -> instanceID ?>txtOneTime" /></div>
					
					</br>		
					
					<div><b class="rn_cart_monthly" >Monthly:</b><input type="text" size="10" class="txtamt2" id="rn_<?= $this -> instanceID ?>txtMonthly" /></div>  
					      
					<div  class="rn_cart_don_linkbutton">										
						<a class="don_linkbutton" id="rn_<?= $this -> instanceID ?>_adddonation" >
						Add Donation »
						</a>								
					</div>
					</p> 
				</div>				
				<div class="rn_cart_descriptioncontainer"  > 				       
					<h3>
					<b id="sam_item" class="rn_cartDonationDescriptionTitle" >
					<?php echo $Item -> Title; ?>
					</b>
					</h3>					
					<p class="rn_cartDonationDescription" ><?php echo $Item -> Description; ?></p> 
				</div>	
	         <?php } ?>         
 
