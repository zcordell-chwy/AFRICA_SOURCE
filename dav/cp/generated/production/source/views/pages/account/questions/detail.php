<rn:meta title="#rn:php:SEO::getDynamicTitle('incident', getUrlParm('i_id'))#" template="standard.php" login_required="true" clickstream="incident_view"/>

<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <div id="rn_PageTitle" class="rn_Account">
        <h1><rn:field name="incidents.subject" highlight="true"/></h1>
    </div>
    <div id="rn_PageContent" class="rn_QuestionDetail">
        <div class="rn_Padding">
            <rn:condition incident_reopen_deadline_hours="168">
                <h2 class="rn_HeadingBar">#rn:msg:UPDATE_THIS_QUESTION_CMD#</h2>
                <div id="rn_ErrorLocation"></div>
                <form id="rn_UpdateQuestion" onsubmit="return false;">
                    <rn:widget path="input/FormInput" name="incidents.thread" label_input="#rn:msg:ADD_ADDTL_INFORMATION_QUESTION_CMD#" initial_focus="true"/>
                    <rn:widget path="input/FileAttachmentUpload2" label_input="#rn:msg:ATTACH_ADDTL_DOCUMENTS_QUESTION_LBL#"/>
                    <div id="rn_FileAttach">
                        <rn:widget path="output/DataDisplay" name="incidents.fattach" label="#rn:msg:FILE_ATTACHMENTS_LBL#"/>
                    </div>
                    <rn:widget path="input/FormInput" name="incidents.status" label_input="#rn:msg:DO_YOU_WANT_A_RESPONSE_MSG#"/>
                    <rn:widget path="input/FormSubmit" on_success_url="/app/account/questions/list" error_location="rn_ErrorLocation"/>
                </form>
            <rn:condition_else/>
                <h2 class="rn_HeadingBar">#rn:msg:INC_REOPENED_UPD_FURTHER_ASST_PLS_MSG#</h2>
            </rn:condition>

            <h2 class="rn_HeadingBar">#rn:msg:COMMUNICATION_HISTORY_LBL#</h2>
            <div id="rn_QuestionThread">
                <rn:widget path="output/DataDisplay" name="incidents.thread" label=""/>
            </div>

            <h2 class="rn_HeadingBar">#rn:msg:ADDITIONAL_DETAILS_LBL#</h2>
            <div id="rn_AdditionalInfo">
                <rn:widget path="output/DataDisplay" name="incidents.contact_email" label="#rn:msg:EMAIL_ADDR_LBL#" />
                <rn:widget path="output/DataDisplay" name="incidents.ref_no" />
                <rn:widget path="output/DataDisplay" name="incidents.status" />
                <rn:widget path="output/DataDisplay" name="incidents.created" label="#rn:msg:CREATED_LBL#" />
                <rn:widget path="output/DataDisplay" name="incidents.updated" />
                <rn:widget path="output/DataDisplay" name="incidents.prod"  />
                <rn:widget path="output/DataDisplay" name="incidents.cat" />
                <rn:widget path="output/DataDisplay" name="incidents.fattach" label="#rn:msg:FILE_ATTACHMENTS_LBL#"/>
                <rn:widget path="output/CustomAllDisplay" table="incidents" />
            </div>

            <div id="rn_DetailTools">
                <rn:widget path="utils/PrintPageLink" />
            </div>
        </div>
    </div>
</div>