<rn:meta title="#rn:msg:SHP_TITLE_HDG#"  template="basic.php" login_required="false" />
 
<? 
   $womanRef = getUrlParm('womanID');
    if (file_exists('/vhosts/africanewlife/euf/assets/womanphotos/'. $womanRef . ".JPG")) {
        $url = '/euf/assets/womanphotos/'. $womanRef . ".JPG";
    }
?>
<div class="sponsor-content">
    <div class="sponsor-info">
        <div style="float:left;">
            <img src="<?=$url?>" style="width:280px;" id="ind_image"/>
        </div>                
    </div>                
</div>
 
 