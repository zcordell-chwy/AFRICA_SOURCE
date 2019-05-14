<rn:meta title="#rn:msg:ASK_QUESTION_HDG#" template="mobile.php" clickstream="incident_create"/>

<section id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:ASK_OUR_SUPPORT_TEAM_A_QUESTION_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_AskQuestion">
    <div class="rn_Padding">
        <form id="rn_QuestionSubmit" onsubmit="return false;">
            <div id="rn_ErrorLocation"></div>
            <fieldset>
            <rn:condition logged_in="false">
                <rn:widget path="input/FormInput" name="contacts.email" required="true"/>
                <rn:widget path="input/FormInput" name="incidents.subject" required="true" />
            </rn:condition>
            <rn:condition logged_in="true">
                <rn:widget path="input/FormInput" name="incidents.subject" required="true"/>
            </rn:condition>
            <rn:condition answers_viewed="2" searches_done="1">
            <rn:condition_else/>
                <rn:widget path="input/SmartAssistantDialog" display_answers_inline="true" label_prompt="#rn:msg:FLLOWING_ANS_HELP_IMMEDIATELY_MSG#" accesskeys_enabled="false" label_solved_button="#rn:msg:MY_QUESTION_IS_ANSWERED_MSG#" 
                    label_cancel_button="#rn:msg:EDIT_QUESTION_CMD#"/>
            </rn:condition>
                <rn:widget path="input/FormInput" name="incidents.thread" required="true" label_input="#rn:msg:ADD_ADDITIONAL_DETAILS_CMD#"/>
                <rn:widget path="input/MobileProductCategoryInput" table="incidents"/>
                <rn:widget path="input/MobileProductCategoryInput" table="incidents" label_data_type="#rn:msg:CATEGORIES_LBL#" label_input="#rn:msg:CATEGORY_LBL#" label_prompt="#rn:msg:SELECT_A_CATEGORY_LBL#" data_type="categories"/>
                <rn:widget path="input/CustomAllInput" table="incidents" always_show_mask="true"/>
                <br/><br/><br/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation" />
            </fieldset>
        </form>
    </div>
</section>
