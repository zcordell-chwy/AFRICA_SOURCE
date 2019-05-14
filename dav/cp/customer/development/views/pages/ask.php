<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="responsive.php" login_required="true" clickstream="incident_create"/>



<div id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:SUBMIT_QUESTION_OUR_SUPPORT_TEAM_CMD#</h1>
</div>
<div id="rn_PageContent" class="rn_AskQuestion">
    <div class="rn_Padding"> 
        <form id="rn_QuestionSubmit" onsubmit="return false;">
            <div id="rn_ErrorLocation"></div>
            
            <input type="text" id="datepick" name="datepick" />

<script>
    $("#datepick").datepicker();
    
    
</script>
            
            <rn:condition logged_in="false">
                <rn:widget path="input/FormInput" name="contacts.email" required="true" initial_focus="true"/>
                <rn:widget path="input/FormInput" name="incidents.subject" required="true" />
            </rn:condition>
            <rn:condition logged_in="true">
                <rn:widget path="input/FormInput" name="incidents.subject" required="true" initial_focus="true"/>
            </rn:condition>
                <rn:widget path="input/FormInput" name="incidents.thread" required="true" label_input="#rn:msg:QUESTION_LBL#"/>
                <rn:widget path="input/FileAttachmentUpload2"/>
                <rn:widget path="input/ProductCategoryInput" table="incidents" show_confirm_button_in_dialog="true"/>
                <rn:widget path="input/ProductCategoryInput" table="incidents" data_type="categories" label_input="#rn:msg:CATEGORY_LBL#" label_nothing_selected="#rn:msg:SELECT_A_CATEGORY_LBL#" show_confirm_button_in_dialog="true"/>
                <rn:widget path="input/CustomAllInput" table="incidents" always_show_mask="true"/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation" />
        </form>
        <rn:condition answers_viewed="2" searches_done="1">
        <rn:condition_else/>
            <rn:widget path="input/SmartAssistantDialog" label_cancel_button="#rn:msg:EDIT_QUESTION_CMD#" label_solved_button="#rn:msg:MY_QUESTION_IS_ANSWERED_MSG#" display_answers_inline="true" 
                label_accesskey="<span class='rn_ScreenReaderOnly'>#rn:msg:PREFER_KEYBOARD_PCT_S_PLUS_1_PCT_D_LBL#</span>" label_prompt="#rn:msg:FLLOWING_ANS_HELP_IMMEDIATELY_MSG#" display_button_as_link="label_cancel_button" dialog_width="800px"/>
        </rn:condition>
    </div>
</div>
<div></div>    