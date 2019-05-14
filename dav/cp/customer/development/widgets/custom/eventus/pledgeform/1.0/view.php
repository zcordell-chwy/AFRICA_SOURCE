<div id="rn_PageTitle" class="rn_Account">

<h2>Pledge Detail</h2>

</div>
<div id="rn_PageContent" class="rn_QuestionDetail">
    <div class="rn_Padding">
        <form id="pledgeeditform" autocomplete="off" required="true">         
<input type="hidden" id="pledge_id" name="pledge_id" value="<?=$this->data['pledge']->ID?>" />  
           <table border="0" cellpadding="0" cellspacing="0" class="tblDetail">
            
            
            <tr>
             <td class="label">Pledge Amount</td>
             <td class="data"><?=$this->data['pledge']->PledgeAmount?></td>
            </tr>
            <tr>
             <td class="label">Pledge Status</td>
             <td class="data"><?=$this->data['pledge']->PledgeStatus->LookupName?></td>
            </tr>
            <tr>
             <td class="label">Fund</td>
             <td class="data"><?=$this->data['pledge']->Fund->Descriptions[0]->LabelText?></td>
            </tr>
            <tr>
             <td class="label">Balance</td>
             <td class="data"><?=$this->data['pledgeBalance']?>
                    &nbsp;&nbsp;&nbsp;
                    <span class="managepay"><a  href="/app/account/pledge/pledgepay/pledge_id/<?=$this->data['pledge']->ID?>">Make a Payment</a></span>
             </td>
            </tr>
            
            
<? if ($this->data['pledge']->PledgeStatus->ID == 1 || 
        $this->data['pledge']->PledgeStatus->ID == 2 || 
         $this->data['pledge']->PledgeStatus->ID == 43){ //active, on hold payment or manual pa
?>        
            
            <tr>
                <td class="label">Payment Method</td>
                <td class="data">
                    
                    <select id="paymethods" name="paymethods">
                        <?
                             foreach ($this->data['paymentMethods'] as $pay){
                                 $selected = ($this->data['pledge']->paymentMethod2->ID == $pay->ID) ? 'selected' : "" ;
                                 echo "<option value='$pay->ID' $selected>$pay->CardType ...$pay->lastFour</option>"; 
                             }

                        ?>
                    </select>
                    &nbsp;&nbsp;&nbsp;
                    <span class="managepay"><a  href="/app/paymentmethods">Manage Payment Methods...</a></span>
                </td>
            </tr>   
<?}?>
           </table>
<?if ($this->data['Child']){?>
        <div class="sponsor-content">          
            <div class="sponsor-info">
            	<div style="width:180px; border:1px; float:left;">
                    <img src="<?php echo $this->data['Child'][0]->imageLocation;?>" alt="<?php echo $this->data['Child'][0]->ChildRef;?>" id="ind_image"/>
                </div>                
                <div class="bio-info" style="width:270px; padding-top:10px; float:left;">
                	<h3 id="ind_name"><?php echo $this->data['Child'][0]->FullName; ?></h3>                
                    <div style="width:50%; border:1px; float:left;">
                        <span>
                        <label>Gender: </label><br />
                        <label>Age: </label><br />
                        <label>Birthdate: </label><br />
                        <label>Child ID: </label><br />
                        
                        <br />
                        <a class="sponsor-button" href="/app/home" >Sponsor Another Child</a>
                        </span>
                    </div>                    
                    <div style="width:50%; border:1px; float:left;">
                        <span>
	                        <span id="ind_gender"><?php echo $this->data['Child'][0]->Gender; ?></span><br />
	                        <span id="ind_age"><?php echo $this->data['Child'][0]->Age; ?></span><br />
	                        <span id="ind_dob">
	                        	<?php if($this->data['Child'][0]->MonthOfBirth!=null && $this->data['Child'][0]->DayOfBirth!=null && $this->data['Child'][0]->YearOfBirth!=null){echo $this->data['Child'][0]->MonthOfBirth; ?>/<?php echo $this->data['Child'][0]->DayOfBirth; ?>/<?php echo $this->data['Child'][0]->YearOfBirth;} ?>
	                        </span>
	                        <br />
	                        <span id="ind_ref"><?php echo $this->data['Child'][0]->ChildRef; ?></span><br />
	                        
	                        <br /> 
                        </span>
                    </div>
                </div>                
                <div class="sponsor-text">
                    <p id="spo_dyntext">
						<?php echo $this->data['Child'][0]->Description; ?>                          
                    </p>
                </div>
            </div>         
        </div>
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
        
<?}?>
<? if ($this->data['pledge']->PledgeStatus->ID == 1 || 
        $this->data['pledge']->PledgeStatus->ID == 2 || 
         $this->data['pledge']->PledgeStatus->ID == 43){ //active, on hold payment or manual pay
?>   
            <div class="esg_checkoutButton">  
                     <rn:widget path="custom/eventus/ajaxCustomFormSubmit" target_ajax_endpoint="/ci/AjaxCustom/updatePledge" label_button="Update Pledge" on_success_url="/app/payment/pledgeConfirm" error_location="rn_ErrorLocation"/>
                        <button id="cancelPledge" class="cancelButton">Cancel Pledge</button>
                        <img id="cancelPledge_LoadingIcon" class="rn_Hidden" alt="Loading" src="images/indicator.gif">
                        <span id="cancelPledge_StatusMessage" class="rn_Hidden">Submitting...</span>
             </div>
 <?}?>
 
</form>
           
          
    </div>
</div>



