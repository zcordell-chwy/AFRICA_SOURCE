<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" />
<? $CI = & get_instance(); 
   // print_r($CI->session->getProfileData('contactID')); ?>


<div id="rn_PageContent" class="rn_AccountOverviewPage">
	<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
	<rn:widget path="custom/aesthetic/AccountSubNav" />

	<div class="rn_Overview rn_AfricaNewLifeLayoutSingleColumn ">
		<!-- && getUrlParm('c_id') == $CI->session->getProfileData('contactID') -->
		<?if ($CI->session->getProfileData('contactID') > 0 ){ 
			
			$this->data['children'] = $CI->model('custom/sponsor_model')->getSponsoredChildren($CI->session->getProfileData('contactID'));
			logMessage("Count: ".count($this->data['children']));
			logMessage($this->data['children']);
            if (count($this->data['children']) <= 1){
				header('Location: /app/account/overview');
			}
		?>
		<div class="page-content cf">
			<div class="content-container">
			    <p>
                    #rn:msg:CUSTOM_MSG_letter_info_msg#
                </p>
                    
				<rn:widget path="letters/childSelector" childId="#$CI->session->getProfileData('contactID')#"/>
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
				<!-- <div id="faqContainer" class="accordion-container">
					<rn:widget path="custom/reports/LetterFaqsMultiline" truncate_size="4000" report_id="101035" />
				</div> -->
			</div>
			<aside class="sidebar">
				
				<a href="#faqContainer" class="button sidebarLink">Have questions? Click here.</a>
				<a href="/app/give" class="button sidebarLink" >Order a Gift</a>
				<h2>Online Letter History </h2>
				<p><i>#rn:msg:CUSTOM_MSG_click_link_letters#</i></p>
				<div class="letter-history-box">
					<rn:widget path="custom/reports/LetterHistoryMultiline" report_id="101033" />
				</div>
				<div id="faqContainer" class="accordion-container">
					<rn:widget path="custom/reports/LetterFaqsMultiline" truncate_size="4000" report_id="101035" />
				</div>
			</aside>
		</div>

		<?}else{
			if($CI->session->getProfileData('contactID')){
            $this->data['children'] = $CI->model('custom/sponsor_model')->getSponsoredChildren($CI->session->getProfileData('contactID'));
            logMessage("2Count: ".count($this->data['children']));
			if (count($this->data['children']) > 1)
            	header('Location: /app/account/letters/c_id/pledge/'.$this->data['children'][0]->PledgeId);
				// header('Location: /app/account/letters/c_id/'.$CI->session->getProfileData('contactID')."/pledge/".$this->data['children'][0]->PledgeId);
			}else{
				header('Location: /app/account/overview');
			}
         }
         ?>

	</div>
</div>   

