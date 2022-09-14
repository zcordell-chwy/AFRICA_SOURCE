<rn:meta title="Sponsor A Student"  template="responsive.php" login_required="false" clickstream="home"/>

<div id="rn_PageContent" class="rn_Home">
<form id="rn_QuestionSubmit" method="post" action="" onsubmit="">
				<rn:widget path="input/ProductCategoryInput" name="Incident.Category" table="incidents" required_lvl="1" data_type="categories" label_input="#rn:msg:CATEGORY_LBL#" label_nothing_selected="#rn:msg:SELECT_A_CATEGORY_LBL#"/>
                <rn:widget path="input/FormInput" name="contacts.c$user_name"   />
				<rn:widget path="input/FormInput" name="contacts.email" required="true" initial_focus="true"/>
                <!--<rn:widget path="input/FormInput" name="incidents.subject" required="true" initial_focus="true"/>-->
				<rn:widget path="input/ContactNameInput" required="true"   />
				<rn:widget path="input/FormInput" name="contacts.postal_code" required="false" label_input="Your zip/postal code"   />
				<rn:widget path="input/FormInput" name="contacts.ph_mobile" required="false" label_input="Phone number"   />
				<rn:widget path="input/CustomAllInput" table="incidents" always_show_mask="true"/>
                <!--<rn:widget path="input/FileAttachmentUpload2"/>-->
                <rn:widget path="standard/input/ProductCategoryInput" name="Incident.Category" table="incidents"/>
				<rn:widget path="input/FormInput" name="incidents.thread" required="true" label_input="#rn:msg:QUESTION_LBL#"/>
                <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/ask_confirm" error_location="rn_ErrorLocation" />
        </form>
</div>
		