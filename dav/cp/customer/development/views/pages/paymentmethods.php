<rn:meta title="Manage Payment Methods" template="responsive.php" login_required="true" clickstream="paymethods"/>

<!--need to set this here so successnewpm can get ahold of it-->
<?
$c_id = $this -> session -> getProfileData('contactID');
$this -> session -> setSessionData(array('theRealContactID' => $c_id));
$therealcontactID = $this -> session -> getSessionData('theRealContactID');  
//print("contactID: $contactId, the real c_id: ".$therealcontactID);
?>
<div class="rn_AfricaNewLifeLayoutSingleColumn" style="overflow: auto;">
	<rn:widget path="custom/eventus/managepaymethods"  />
</div>