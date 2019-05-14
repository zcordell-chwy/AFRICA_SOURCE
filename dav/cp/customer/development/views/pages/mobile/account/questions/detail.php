<rn:meta title="#rn:php:SEO::getDynamicTitle('incident', getUrlParm('i_id'))#" template="mobile.php" login_required="true" clickstream="incident_view"/>

<section id="rn_PageTitle" class="rn_QuestionDetail">
    <h1><rn:field name="incidents.subject" highlight="true"/></h1>
</section>
<section id="rn_PageContent" class="rn_QuestionDetail">
    <rn:condition incident_reopen_deadline_hours="168">
        <div class="rn_Module">
            <rn:widget path="navigation/Accordion" toggle="rn_QuestionUpdate"/>
            <h2 id="rn_QuestionUpdate">#rn:msg:UPDATE_THIS_QUESTION_CMD#<span class="rn_Expand"></span></h2>
            <form id="rn_UpdateQuestion" onsubmit="return false;">
                <div id="rn_ErrorLocation"></div>
                <rn:widget path="input/FormInput" name="incidents.thread" label_input="#rn:msg:ADD_ADDTL_INFORMATION_QUESTION_CMD#" initial_focus="true"/>
                <rn:widget path="input/FormInput" name="incidents.status" label_input="#rn:msg:DO_YOU_WANT_A_RESPONSE_MSG#"/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:SUBMIT_CMD#" on_success_url="/app/account/questions/list" error_location="rn_ErrorLocation"/>
            </form>
        </div>
    <rn:condition_else/>
        <h4>#rn:msg:INC_REOPENED_UPD_FURTHER_ASST_PLS_MSG#</h4>
    </rn:condition>

    <div class="rn_Module">
        <rn:widget path="navigation/Accordion" toggle="rn_QuestionThread"/>
        <h2 id="rn_QuestionThread">#rn:msg:COMMUNICATION_HISTORY_LBL#<span class="rn_Expand"></span></h2>
        <div class="rn_Hidden rn_QuestionThreadContent">
            <rn:widget path="output/DataDisplay" name="incidents.thread" label=""/>
        </div>
    </div>

    <div class="rn_Module">
        <rn:widget path="navigation/Accordion" toggle="rn_QuestionDetails"/>
        <h2 id="rn_QuestionDetails">#rn:msg:ADDITIONAL_DETAILS_LBL#<span class="rn_Expand"></span></h2>
        <div class="rn_Hidden rn_Padding">
            <rn:widget path="output/DataDisplay" name="incidents.contact_email" label="#rn:msg:EMAIL_ADDR_LBL#" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.ref_no" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.status" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.created" label="#rn:msg:CREATED_LBL#" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.updated" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.prod" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.cat" left_justify="true"/>
            <rn:widget path="output/DataDisplay" name="incidents.fattach" label="#rn:msg:FILE_ATTACHMENTS_LBL#" left_justify="true"/>
            <rn:widget path="output/CustomAllDisplay" table="incidents" left_justify="true"/>
        </div>
    </div>
</section>
