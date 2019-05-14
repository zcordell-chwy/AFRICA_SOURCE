<rn:meta title="#rn:msg:QUESTION_SUBMITTED_LBL#" template="basic.php" clickstream="incident_confirm"/>

<h1>#rn:msg:QUESTION_SUBMITTED_HDG#</h1>
<p>
#rn:msg:SUBMITTING_QUEST_REFERENCE_FOLLOW_LBL# 
    <b>
        <rn:condition url_parameter_check="i_id == null">
            ##rn:url_param_value:refno#
        <rn:condition_else/>
            <a href="/app/#rn:config:CP_INCIDENT_RESPONSE_URL#/i_id/#rn:url_param_value:i_id##rn:session#">#<rn:field name="Incident.ReferenceNumber" /></a>
        </rn:condition>
    </b>
</p>
<p>
    #rn:msg:SUPPORT_TEAM_SOON_MSG#
</p>
<rn:condition logged_in="true">
    <p>
        #rn:msg:UPD_QUEST_CLICK_ACCT_LINK_BTM_PG_LBL#
    </p>
    <rn:condition_else/>
    <p>
        #rn:msg:UPD_QUEST_ACCT_LOG_CLICK_ACCT_LINK_LBL#
    </p>
    <p>
        #rn:msg:DONT_ACCT_ACCOUNT_ASST_ENTER_EMAIL_MSG#
        <a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:ACCOUNT_ASSISTANCE_LBL#</a>
    </p>
</rn:condition>
