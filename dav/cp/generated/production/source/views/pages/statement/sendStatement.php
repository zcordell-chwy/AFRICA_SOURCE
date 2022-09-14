<rn:meta title="" login_required="false"  template="responsive.php" />

<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<?

	$contact = getUrlParm("c_id"); 
	$mailResponse = $this -> model('custom/mail_model') -> sendStatement($contact);

	if($mailResponse){
	    echo "Sent Statment to ".$contact;
	}else{
	    echo "Mail Failed";
	    echo "<pre>";
	    print_r($mailResponse);
	    echo "</pre>";
	}
	?>
</div>