<div class="paymentAmountSelection rn_Hidden" id="rn_<?=$this->instanceID;?>_amountSelector">
    <input type="hidden" id="rn_<?=$this->instanceID;?>_pledge_id" name="pledge_id" value="<?=$this->data['attrs']['pledgeid']?>" /> 
    <div id="rn_<?=$this->instanceID;?>_ErrorLocation" class="rn_MessageBox rn_ErrorMessage rn_Hidden" tabindex="0"></div>
    <input type="radio" name="rn_<?=$this->instanceID;?>_pledgepayamount" value="<?=$this->data['attrs']['pledgeamount']?>">Recurring Pledge Amount: <?=$this->data['attrs']['pledgeamount']?> <br/>
    <?if($this->data['attrs']['aheadbehind'] < 0){?>
        <input type="radio" name="rn_<?=$this->instanceID;?>_pledgepayamount" value="<?="$".number_format(abs($this->data['attrs']['aheadbehind']), 2, '.', '')?>">Balance: <?="$".number_format(abs($this->data['attrs']['aheadbehind']), 2, '.', '')?><br/>
    <?}?>
    <input type="radio" name="rn_<?=$this->instanceID;?>_pledgepayamount" value="other">Other Amount<input type="text" class="pledgeotheramount" name="rn_<?=$this->instanceID;?>_pledgeotheramount" id="rn_<?=$this->instanceID;?>_pledgeotheramount"><br/>
     
    
</div>
<button id="rn_<?=$this->instanceID;?>_makePayment" class="paymentButton" name="makePayment"><?=$this->data['attrs']['label_button']?></button>
