<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="false" template="standard.php" clickstream="payment"/>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <p>  
    <div class="childImgContainer">
    	<rn:widget path="payment/Thankyou" />
    </div>    
</div>

<script>  
window.onload = function()  {
    gtag('event', 'Single_Donation_Success', {
		'Donated' : 'true'
	  }); 
}
</script>