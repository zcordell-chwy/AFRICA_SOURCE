

<?php 
	$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl();
	$itnum=0;
	//$Children = $this->data['Items']; 
	//$items = $this -> CI -> session -> getSessionData('items');
	//foreach( $this -> data['Items'] as $Item)
	//$items = $this -> CI -> session -> getSessionData('items');
	$IndividualFunds = $this->data['Items']; 
	
?>


<?php 
 $i=0; 

 	foreach( $this -> data['Items'] as $Item)	{ 	
 	$itemObjID = $item -> ID;
 	
     	$i++; 
     
			?>
				<div class="carousel-thumb carousel-thumb1">
					<div>
						<?php echo "<img width='140px' height='88px' src='".str_replace($baseURL, "", $Item->PhotoURL)."' alt='#'' title='".$Item->Description."' />";  ?>
					</div>
					<div><b class="itemname" itemId="<?php echo $Item->ID;?>" fund="<?php echo $Item->DonationFund;?>" appeal="<?php echo $Item->DonationAppeal;?>">
					<?php echo $Item->Title;?></b></div>
					<div><b class="onetime">One Time:</b></div>
					<div> <input type="text" size="10" class="txtamt1" /></div>
					<div><b class="monthly">Monthly:</b> </div>		
					<div><input type="text" size="10" class="txtamt2" /></div>           
					<div style="float:right;margin-right:14px;margin-top:15px;">
						<a href="javascript:void(0);" class="don_linkbutton">Add Donation »</a>								
					</div>
				</div>
				<div class="info-right">                  
					<h3><b class="itemname1" id="sam_item" style="width:200px;"><?php echo $Item->Title;?></b></h3>
					<p><?php echo $Item->Description;?></p> 
				</div>	
	         <?php } ?>         
 	

  


<style type="text/css">
	#sel_item
	{
		width:115px;
	}
</style>