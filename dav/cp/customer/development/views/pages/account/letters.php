<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="responsive.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
	<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
	<rn:widget path="custom/aesthetic/AccountSubNav" />

	<div class="rn_Overview rn_AfricaNewLifeLayoutSingleColumn ">
		<?if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){?>
		<div class="page-content cf">
			<div class="content-container">
			    <p>
                    #rn:msg:CUSTOM_MSG_letter_info_msg#
                </p>
                    
				<rn:widget path="letters/childSelector" childId="#rn:php:getUrlParm('child')#"/>
				<div class="text-content">
					<p>
					#rn:msg:CUSTOM_MSG_letter_text_content_msg#
					</p>
					
				</div>
				<div class="form-container">
					<form id="rn_QuestionSubmit" onsubmit="return false;">
					    <div id="rn_ErrorLocation"></div>
					    <div class="rn_Hidden">
					        <rn:widget path="input/FormInput" name="Incident.CustomFields.CO.PledgeRef_PlaceHolder" default_value="#rn:php:getUrlParm('pledge')#" required="false" label_input="Child Reference"/>
					        <rn:widget path="input/ProductCategoryInput" name="Incident.Category" default_value='10' table="incidents" data_type="categories" label_input="#rn:msg:CATEGORY_LBL#" label_nothing_selected="#rn:msg:SELECT_A_CATEGORY_LBL#" show_confirm_button_in_dialog="true"/>
					    </div>
						<rn:widget path="input/FormInput" name="incidents.thread" required="true" label_input="Please write your letter below:"/>
						<div class="form-footer">
							<rn:widget path="input/FileAttachmentUpload2" valid_file_extensions="jpg" label_input="Attach Photos" max_attachments="3" />
							<rn:widget path="input/FormSubmit" label_button="Send" on_success_url="/app/letters_confirm" error_location="rn_ErrorLocation" />
						</div>  
					</form>
				</div>
				<div id="faqContainer" class="accordion-container">
					<rn:widget path="custom/reports/LetterFaqsMultiline" truncate_size="4000" report_id="101035" />
				</div>
			</div>
			<aside class="sidebar">
				<a href="#faqContainer" class="button">Have questions? Click here.</a>
				<a href="/app/give" class="button" >Order a Gift</a>
				<h2>Online Letters History</h2>
				<div class="letter-history-box">
					<rn:widget path="custom/reports/LetterHistoryMultiline" report_id="101033" />
				</div>
			</aside>
		</div>

		<?}else{
            $this->data['children'] = $this->CI->model('custom/sponsor_model')->getSponsoredChildren($profile->c_id->value);
            if (count($this->data['children']) > 0){
            ?>
                
            <?}
            header('Location: /app/account/letters/c_id/'.$profile->c_id->value."/pledge/".$this->data['children'][0]->PledgeId);
         }
         ?>

	</div>
</div>
