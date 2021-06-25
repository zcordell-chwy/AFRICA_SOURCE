
<?php 
	$Children = $this->data['Children'];	
	$ChildList = $this->data['ChildList'];	
	$CommunityList = $this->data['CommunityList']; 
	$gender = $this->data['Gender'];
	$age = $this->data['Age'];
	$community = $this->data['Community'];	
    
    logMessage("list");
    logMessage($ChildList);
    logMessage("children");
    logMessage($Children);
?>
 
<?php

    if(count($ChildList) > 0){
    	foreach($ChildList as $child)
    	{
    		?>
            <div class="sponsor-content">
                <?if(!$child->WebHold){?>
                <h3 style='color:#911A1D; margin-bottom:15px;'>
                    <div class="bio-info">We’re sorry, this student no longer needs a sponsor. Please visit <a href="/app/home">this page</a> to sponsor a different student.</div>
                </h3>
                <?}?>            
                <div class="sponsor-info">
                	<div style="width:20%; border:1px; float:left; text-align: center;">
                        <img src="<?php echo $child->imageLocation;?>" alt="<?php echo $child->ChildRef;?>" id="ind_image"/>
                    </div>                
                    <div class="bio-info" style="width:30%; padding-top:10px; float:left;">
                    	<h3 id="ind_name"><?php echo $child->FullName; ?></h3>                
                        <div style="width:50%; border:1px; float:left;">
                            <span>
                            <label>Gender: </label><br />
                            <label>Age: </label><br />
                            <label>Birthdate: </label><br />
                            <label>Reference: </label><br />
                            <label style="display:inline-block;position:relative;top:2px;">Sponsorship rate: </label><br /> 
                            <br />
                        
                            <?  
                                if($child->WebHold){
                            ?>
                                    <a class="sponsor-button" childId="<?=$child->ID?>" link="/app/sponsorchild/ChildID/<?=$child->ID?>"  appeal="1239" href="javascript:void(0)" id="ind_link" rate="<?php echo $child->Rate; ?>">Sponsor Me ></a>
                            <?
                                }else{
                            ?>
                                    <a class="sponsor-button" href="/app/home" >Sponsor Another Student</a>
                            <?
                                }
                            ?>
                            </span>
                        </div>                    
                        <div style="width:50%; border:1px; float:left;">
                            <span>
    	                        <span id="ind_gender"><?php echo $child->Gender; ?></span><br />
    	                        <span id="ind_age"><?php echo $child->Age; ?></span><br />
    	                        <span id="ind_dob">
    	                        	<?php if($child->MonthOfBirth!=null && $child->DayOfBirth!=null && $child->YearOfBirth!=null){echo $child->MonthOfBirth; ?>/<?php echo $child->DayOfBirth; ?>/<?php echo $child->YearOfBirth;} ?>
    	                        </span>
    	                        <br />
    	                        <span id="ind_ref"><?php echo $child->ChildRef; ?></span><br />
    	                        <span id="ind_rate">$<?php echo $child->Rate; ?>.00/mo</span>
    	                        <br /> 
                            </span>
                        </div>
                    </div>                
                    <div class="sponsor-text">
                        <p id="spo_dyntext">
    						<?php echo $child->Description; ?>                          
                        </p>
                    </div>
                </div>         
            </div>
        <?php
        }
    }else{ ?>
        <div class="sponsor-content">
            <h3 style='color:#911A1D; margin-bottom:15px;'>
                <div class="bio-info" style="float: initial;">The specified Student ID is invalid. Please visit our <a href='/app/home'>home page</a> for more students needing sponsorship.</div>
            </h3>
        </div>
    <?php
    }
?>

    




<style type="text/css">
#loadingDiv
{
	display:none;
}
#Info_items
{
	display:none;	
}
.ct_link
{	
	display:inline-block;
	width: 115px;
	height: 158px;
	border: 1px solid #eee;
}
.ct_img
{
	width: 120px;
height: 158px;
}
#ind_image {
    width: 120px;
    height: 158px;
}
</style>