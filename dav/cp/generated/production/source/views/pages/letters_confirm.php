<?php
    $iid = \RightNow\Utils\Url::getParameter('i_id');
    $refno = \RightNow\Utils\Url::getParameter('refno');
    $confirmMsg = \RightNow\Utils\Config::getMessage(CUSTOM_MSG_cp_letters_confirm_page_confirm_msg);
    $confirmMsg = str_replace(array('i_id', 'refno'), array($iid, $refno), $confirmMsg);

    // Update Incident.PledgeRef attribute to match Incident.PledgeRef_placeholder
    if(!empty($iid)){
    	$incident = \RightNow\Connect\v1_2\Incident::fetch($iid);
    }else if(!empty($refno)){
    	$incident = \RightNow\Connect\v1_2\Incident::first("ReferenceNumber = '" . $refno . "'");
    }

    if(!is_null($incident) && $incident->CustomFields->CO->PledgeRef != $incident->CustomFields->CO->PledgeRef_PlaceHolder){
    	$incident->CustomFields->CO->PledgeRef = $incident->CustomFields->CO->PledgeRef_PlaceHolder;
        $pledge = \RightNow\Connect\v1_2\Donation\Pledge::fetch($incident->CustomFields->CO->PledgeRef_PlaceHolder);
        $incident->CustomFields->CO->ChildRef = $pledge->Child->ID;
    	$incident->save();
    }
?>
<rn:meta title="#rn:msg:QUESTION_SUBMITTED_LBL#" template="standard.php" login_required="true" clickstream="incident_confirm"/>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <h2>
        #rn:msg:CUSTOM_MSG_SPONSOR_PAGE_EVENT_MSG#
    </h2>
    <p> 
     
        <?= $confirmMsg ?>
    
    </p>
    <br/>
    <p>
        
    </p>
</div>
