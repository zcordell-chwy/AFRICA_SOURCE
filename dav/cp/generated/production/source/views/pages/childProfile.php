<rn:meta title="#rn:msg:SHP_TITLE_HDG#"  template="basic.php" login_required="false" />
 
<? 
   $url = $this->get_instance()->model('custom/sponsorship_model')->getChildImg(getUrlParm('childID'));
?>
<div class="sponsor-content">
    <div class="sponsor-info">
        <div style="float:left;">
            <img src="<?=$url?>" style="width:280px;" id="ind_image"/>
        </div>                
    </div>                
</div>
 
 