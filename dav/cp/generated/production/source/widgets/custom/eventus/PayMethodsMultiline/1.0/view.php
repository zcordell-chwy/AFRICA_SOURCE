<rn:widget path="custom/eventus/managepaymethods" />

<? foreach ($this->data['reportData']['data'] as $value): 

    switch(strtolower($value[2])){
        case "amex":
            $payClass = "cc-amex";   
            break;
        case "visa":
            $payClass = "cc-visa";
            break;
        case "mastercard":
            $payClass = "cc-mastercard";
            break;
        case "discover":
            $payClass = "cc-discover";
            break;
        case "checking":
            $payClass = "bank-account"; 
            break;
      }
                    
?>
                    
                    
 <div class="payMethodContainer">                   
	    <div id="" aria-live="polite" class="payMethodHeader">
			<div class="ccImage <?=$payClass ?>">
			    &nbsp;
			</div>
			<div class="ccInfo" >
				<div class="a-row">
					<span class="a-size-base pmts-aui-account-number-display">
					    <span class=""><?=$value[2] ?></span> 
					    <span class="">ending in 
					        <span class=""><?=$value[3] ?></span>
					     </span>
					     <?=($value[2] == "Checking") ? "" : "<span>Expires: $value[4]/$value[5]</span>";?>
					     
					 </span>
				</div>
			</div>
			<?if( intval($value[5]) && ((intval($value[5]) < date("Y")) || ( intval($value[5]) == date("Y") && intval($value[4]) < date("m") )  )){?>
            <div class="ccValidationError">
                    <span>Expired</span>
            </div>
            <?}?>   		
	   </div>
	   
	   
		<div class="payMethodDetail">
			<table>
				<tbody>
					<tr>
					   <td>
						<div class="billingDetails">
						    <span class="billingHeader">Billing Address</span>
						    <br/>
							<span class=""><?=$value[7] ?> <?=$value[8] ?></span>
							<br>
							<span class="a-size-base a-color-base pmts-address-field"><?=$value[9] ?></span>
							<br>
							<span class="a-size-base a-color-base pmts-address-field"><?=$value[10] ?></span><span>, </span>
							<span class="a-size-base a-color-base pmts-address-field"><?=$value[11] ?></span>
							<span class="a-letter-space">&nbsp;</span><span class="a-size-base a-color-base pmts-address-field"><?=$value[12] ?></span><span>, </span><span class="a-size-base a-color-base pmts-address-field"><?=$value[13] ?></span>
							<br>
							<span class="a-size-base a-color-base pmts-address-field"><?=$value[14] ?></span>
						</div>
					  </td>
					  <td>
					      <div class="billingDetails">
					          <span class="billingHeader">Associated Pledges</span><br/>
					          <span> <?=($value[6]) ? $value[6] : "None"; ?></span>
					      </div>
					          
					  </td>
					</tr>
				</tbody>
			</table>
			<div class="a-row a-grid-bottom">
				<div class="a-column a-span3 a-text-right a-span-last">
					<ul class="a-nostyle a-horizontal pmts-aui-instrument-edit-delete-buttons">
					    
    					    <?if (getUrlParm('p_id') > 0){?>
        					    <li>
        					        <form id="payMethod_<?=$value[0] ?>_payMethodsSelectForm" onsubmit="return false;" name="payMethod_<?=$value[0] ?>_payMethodsSelectForm">
        					           <input type="hidden" id="payMethodId" name="payMethodId" value="<?=$value[0] ?>">
        					           <input type="hidden" id="pledgeId" name="pledgeId" value="<?=getUrlParm('p_id')?>">
        					           <rn:widget path="custom/eventus/ajaxCustomFormSubmit"  formName="changePayMethod" label_button="Select" />
        					        </form>
        					    </li>
    					    <?} ?>
    						<li>
    						    <?if(!$value[6]){?>
    							     <form id="payMethod_<?=$value[0] ?>_payMethodsDeleteForm" onsubmit="return false;" name="payMethod_<?=$value[0] ?>_payMethodsDeleteForm">
    							       <input type="hidden" id="payMethodId" name="payMethodId" value="<?=$value[0] ?>">
                                       <input type="hidden" id="pledgeId" name="pledgeId" value="<?=getUrlParm('p_id')?>">
                                       <rn:widget path="custom/eventus/ajaxCustomFormSubmit"  formName="deletePayMethod" label_button="Delete" />
                                    </form>
                                <?}else{ ?>
                                    <div class="infoBox">This Payment Method cannot be deleted because it is associated with an active, recurring pledge.</div>
                                <?} ?>
    						</li>
						
					</ul>
				</div>
			</div>
	</div>
</div>
<? endforeach; ?>