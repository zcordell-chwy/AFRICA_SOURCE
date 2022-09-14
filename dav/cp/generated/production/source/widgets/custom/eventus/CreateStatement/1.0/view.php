<div class="PledgeInfo">
    
    Pledge Information:
    <div>Pledge ID: <?=$this -> data['pledge'] -> ID?></div>
    <div>Contact ID: <?=$this -> data['contact'] ?></div>
    <div>Pledge Amount: <?=$this -> data['pledge'] -> PledgeAmount?></div>
    <div>Pledge Frequency: <?=$this -> data['pledge'] -> Frequency -> LookupName?></div>
    <div>Next Transaction Date: <?=date("m/d/Y",$this -> data['pledge'] -> NextTransaction)?></div>
    <div>Current Balance: <?=$this -> data['pledge'] -> Balance?></div>
    <div>Ahead Behind: <?=$this -> data['pledge'] -> AheadBehind?></div>
<!--    <form id='sendStatementForm'  method="POST" onsubmit="return false;">
        <div class="sendStatement">
            <input type="hidden" id="contactid" name="contactid" value="<?=$this -> data['pledge'] -> Contact -> ID?>" />
            <input type="submit" id="sendStatement" value="Email Statement"/>
        </div>
    </form>-->

    <a href="/app/statement/sendStatement/c_id/<?=$this -> data['contact'] ?>">Send Statement</a>
    
    <div>
        **Note:  This will send balances for all this contacts Active, Manual Pay and On Hold for Payment pledges.
    </div>
    <div>
        <h3>Statement Content</h3>
        <rn:container report_id="100309">
             <rn:widget path="reports/Grid2"/>
        </rn:container>
    </div>
</div>