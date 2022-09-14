<rn:meta title="#rn:php:SEO::getDynamicTitle('answer', getUrlParm('a_id'))#"  login_required="true" template="standard.php" answer_details="true" clickstream="answer_view"/>

<div id="rn_PageTitle" class="rn_AnswerDetail">
    <h1 id="rn_Summary"><rn:field name="answers.summary" highlight="true"/></h1>
    <div id="rn_AnswerInfo">
        #rn:msg:PUBLISHED_LBL# <rn:field name="answers.created" />
        &nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
        #rn:msg:UPDATED_LBL# <rn:field name="answers.updated" />
    </div>
    <rn:field name="answers.description" highlight="true"/>
</div>
<div id="rn_PageContent" class="rn_AnswerDetail">
    <div id="rn_AnswerText">
        <rn:field name="answers.solution" highlight="true"/>
    </div>
    <rn:widget path="knowledgebase/GuidedAssistant" label_text_result=""/>
    <div id="rn_FileAttach">
        <rn:widget path="output/DataDisplay" name="answers.fattach" />
    </div>
    <rn:widget path="feedback/AnswerFeedback2" label_dialog_title="#rn:msg:PROVIDE_ADDITIONAL_INFORMATION_LBL#" label_dialog_description="#rn:msg:RATING_SUBMITTED_PLS_TELL_ANS_MSG#" dialog_width="375px" />
    <br/>
    <rn:widget path="knowledgebase/RelatedAnswers2" />

    <div id="rn_DetailTools">
        <rn:widget path="utils/SocialBookmarkLink" />
        <rn:widget path="utils/PrintPageLink" />
        <rn:widget path="utils/EmailAnswerLink" />
        <rn:condition logged_in="true">
            <rn:widget path="notifications/AnswerNotificationIcon3" />
        </rn:condition>
    </div>
</div>
