<h2>Write to your sponsored student</h2>
<h3>#rn:msg:CUSTOM_MSG_PLEASE_CHOOSE_STUDENT#</h3>
<h1><?=$this -> data['pledgeToShow'] -> GivenName ?> 
    <?if($this->data['previousPledge'] > 0){?>
        <a href='/app/account/letters/pledge/<?=$this -> data['previousPledge'] ?>'>
	<!--a href='/app/account/letters/c_id/<?=getUrlParm('c_id') ?>/pledge/<?=$this -> data['previousPledge'] ?>'-->
    <?} ?>
        <i id="leftArrow" class="fa fa-angle-left" aria-hidden="true"></i>
     <?if($this->data['previousPledge'] > 0){?> 
        </a> 
     <?} ?>
     |
      <?if($this->data['nextPledge'] > 0){?>
            <a href='/app/account/letters/pledge/<?=$this -> data['nextPledge'] ?>'>
	    <!--a href='/app/account/letters/c_id/<?=getUrlParm('c_id') ?>/pledge/<?=$this -> data['nextPledge'] ?>'-->

      <?} ?>
        <i id="rightArrow" class="fa fa-angle-right" aria-hidden="true"></i>
      <?if($this->data['nextPledge'] > 0){?>
        </a>
      <?} ?>
</h1>
<div class="profile-container cf">
	<div class="image-container">
		<img src="<?=$this -> data['pledgeToShow'] -> imageLocation ?>">
	</div>
	<div class="profile-info">
		<span class="child-name"><?=$this -> data['pledgeToShow'] -> FullName ?></span>
		<br>
		<span class="reference-number">Child Ref: <?=$this -> data['pledgeToShow'] -> ChildRef ?></span>
		<br>
		<span class="grade">Grade: <?=$this -> data['pledgeToShow'] -> Grade ?></span>
		<br>
		<span class="age">Age: <?=$this -> data['pledgeToShow'] -> Age ?></span>
		<br>
		<span class="birthday">Birthday: <?=$this -> data['pledgeToShow'] -> BirthDay ?></span>
		<br>
		<span class="gender">Gender: <?=$this -> data['pledgeToShow'] -> Gender ?></span>
		<br>
		<span class="school-status"><?=($this -> data['pledgeToShow'] -> isBoarding) ? "This student is in boarding school": ""?></span>
		<br>
	</div>
</div>