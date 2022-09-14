<!--
<rn:block id='Multiline-top'>

</rn:block>
-->

<!--
<rn:block id='Multiline-preLoadingIndicator'>

</rn:block>
-->

<!--
<rn:block id='Multiline-postLoadingIndicator'>

</rn:block>
-->

<!--
<rn:block id='Multiline-topContent'>

</rn:block>
-->

<!--
<rn:block id='Multiline-preResultList'>

</rn:block>
-->

<!--
<rn:block id='Multiline-topResultList'>

</rn:block>
-->

<rn:block id='Multiline-resultListItem'>
<div class="letter-history-item">
    <span class="date"><?=$value[0]?></span>
    <span class="sender"><a href="/app/account/letters_detail/i_id/<?=$value[3]?>">To <?=$value[1]?></a></span>
    <? if($value[2] != ""){?>
        <br/><span class="sender" style="font-size:.9em; color:#E44C2E">Responded <?=$value[2]?></span>
    <?}?>
</div>
                    
</rn:block>

<!--
<rn:block id='Multiline-bottomResultList'>

</rn:block>
-->

<!--
<rn:block id='Multiline-postResultList'>

</rn:block>
-->

<!--
<rn:block id='Multiline-noResultListItem'>

</rn:block>
-->

<!--
<rn:block id='Multiline-bottomContent'>

</rn:block>
-->

<!--
<rn:block id='Multiline-bottom'>

</rn:block>
-->

