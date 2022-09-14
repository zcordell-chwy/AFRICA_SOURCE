<div id="rn_<?= $this->instanceID ?>_QuestionEdit" class="rn_Hidden rn_QuestionEdit" data-contentType="<?= $question->BodyContentType->LookupName ?>">
    <div id="rn_<?= $this->instanceID ?>_SocialQuestionUpdateErrors"></div>
    <rn:block id="preQuestionEditForm" />
    <form action="/ci/ajaxRequest/sendForm" id="rn_<?= $this->instanceID ?>_QuestionUpdateForm">
        <rn:widget path="input/TextInput" name="SocialQuestion.Subject" required="true" sub_id="subject" label_input="#rn:php:$this->data['attrs']['label_edit_question_subject']#"/>

        <? if($useRichTextInput): ?>
          <rn:widget path="input/RichTextInput"
                      name="SocialQuestion.Body"
                      required="true"
                      sub_id="body"
                      read_only="#rn:php:$question->BodyContentType->LookupName === 'text/html'#"
                      label_input="#rn:php:$question->BodyContentType->LookupName === 'text/html' ? $this->data['attrs']['label_body_not_editable'] : $this->data['attrs']['label_edit_question_body']#"/>
        <? else: ?>
          <rn:widget path="input/TextInput" name="SocialQuestion.Body" required="true" sub_id="body" label_input="#rn:php:$question->BodyContentType->LookupName === 'text/html' ? $this->data['attrs']['label_body_not_editable'] : $this->data['attrs']['label_edit_question_body']#" />
        <? endif; ?>

        <? if (!$this->data['attrs']['mobile_enabled']): ?>
          <rn:widget path="input/ProductCategoryInput" name="SocialQuestion.Product" default_value="#rn:php:$question->Product->ID#" sub_id="prodcat"/>
        <? else: ?>
          <rn:widget path="input/MobileProductCategoryInput" name="SocialQuestion.Product" default_value="#rn:php:$question->Product->ID#" sub_id="prodcat"/>
        <? endif; ?>

        <rn:block id="additionalFields" />
        <div class="rn_FormControls">
            <? if($question->SocialPermissions->canDelete()): ?>
              <button class="rn_QuestionEditAction rn_DeleteQuestion" data-questionID="<?= $question->ID ?>">
                  <?= $this->data['attrs']['label_delete_button'] ?>
              </button>
            <? endif; ?>

            <span class="rn_QuestionEditAction rn_CancelEdit">
                <a href="javascript:void(0);" role="button"><?= $this->data['attrs']['label_cancel_button'] ?></a>
            </span>

            <rn:widget path="input/FormSubmit" label_button="#rn:php:$this->data['attrs']['label_save_edit_button']#" on_success_url="#rn:php:$this->data['currentPage']#" error_location="rn_#rn:php:$this->instanceID#_SocialQuestionUpdateErrors" sub_id="submit"/>
        </div>
    </form>
    <rn:block id="postQuestionEditForm" />
</div>