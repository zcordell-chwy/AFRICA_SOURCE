<rn:meta title="Request to process failed June donation" template="standard.php" clickstream="incident_create" noindex="true" />
<style>
    .rn_paymentConfirmation legend {
        visibility: visible !important;
    }

    .rn_paymentConfirmation {
        padding: 0;
        font-family: 'GothamBook';
    }

    .contemporary {
        padding-top: 5ch;
        font-size: 13px;
    }

    .rn_Footer {
        font-size: 20px !important;
        line-height: 1.250em !important;
        color: #000 !important;
        font-family: GothamBook, Arial !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        background: #94ae59 !important;
        border: none !important;
        overflow: hidden !important;
        padding: 40px 0px 20px 0px !important;
        text-align: left !important;
        width: 100% !important;
        border-bottom: 1px solid #E2E2E2 !important;
    }

    .footer-left,
    .footer-right {
        font-size: .75em !important;
        line-height: 1.250em !important;
        color: #000 !important;
        font-family: GothamBook, Arial !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        text-align: center !important;
        border-right: 1px dashed white !important;
        float: left !important;
        padding: 15px !important;
        position: relative !important;
        width: 50% !important;
    }

    .footer-left h2,
    .footer-right h2 {
        text-align: center !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: GothamBook, Arial !important;
        font-size: 28px !important;
        line-height: 28px !important;
        font-weight: bold !important;
        font-style: normal !important;
        color: #706359 !important;
        text-transform: initial !important;
    }

    .rn_Footer h3 {
        text-align: center !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: Arial, sans-serif !important;
        font-weight: bold !important;
        color: black !important;
        font-size: 1.167em !important;
        line-height: 1.3em !important;
    }

    .footer-left p {
        color: #000 !important;
        font-family: GothamBook, Arial !important;
        text-align: center !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.4em !important;
        margin-bottom: 1em !important;
        font-size: 15px !important;
    }

    .footer-right {
        font-size: .75em !important;
    }

    .footer-right ul,
    .footer-right ul a {
        font-size: 13px !important;
    }

    .footer-right ul b {
        font-size: .95em !important;
    }

    .footer-right ul * {
        padding-bottom: 3px;
    }

    @font-face {
        font-family: 'GothamMedium';
        src: url('/euf/assets/themes/africa/fonts/gotham-medium-webfont.eot');
        src: local('☺'), url('/euf/assets/themes/africa/fonts/gotham-medium-webfont.eot?#iefix') format('embedded-opentype'), url('/euf/assets/themes/africa/fonts/gotham-medium-webfont.woff') format('woff'), url('/euf/assets/themes/africa/fonts/gotham-medium-webfont.ttf') format('truetype'), url('/euf/assets/themes/africa/fonts/gotham-medium-webfont.svg#GothamMedium') format('svg');
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'GothamBook';
        src: url('/euf/assets/themes/africa/fonts/gotham-book-webfont.eot');
        src: local('☺'), url('/euf/assets/themes/africa/fonts/gotham-book-webfont.eot?#iefix') format('embedded-opentype'), url('/euf/assets/themes/africa/fonts/gotham-book-webfont.woff') format('woff'), url('/euf/assets/themes/africa/fonts/gotham-book-webfont.ttf') format('truetype'), url('/euf/assets/themes/africa/fonts/gotham-book-webfont.svg#GothamBook') format('svg');
        font-weight: normal;
        font-style: normal;
    }
</style>

<div class="contemporary">
    <div class="center">
        <div>
            <div class="rn_Hero">
                <div class="rn_HeroInner">
                    <div class="rn_HeroCopy">
                        <h1>Request to process failed June donation</h1>
                    </div>
                </div>
            </div>

            <div class="rn_PageContent rn_AskQuestion rn_contemporary rn_paymentConfirmation">
                <form id="rn_QuestionSubmit" method="post" action="/ci/ajaxRequest/sendForm">
                    <div id="rn_ErrorLocation"></div>
                    <rn:widget path="input/FormInput" name="Incident.CustomFields.c.canprocess" required="true" initial_focus="true" label_input="#rn:msg:CUSTOM_MSG_MISSED_TRANSACTION#" />
                    <div class="rn_Hidden">
                        <rn:widget path="input/FormInput" name="Incident.Subject" required="true" label_input="#rn:msg:SUBJECT_LBL#" default_value="Request to Reprocess Payment - Response" />
                    </div>
                    <rn:condition logged_in="false">
                        <rn:widget path="input/FormInput" name="Contact.Emails.PRIMARY.Address" required="true" label_input="#rn:msg:EMAIL_ADDR_LBL#" />
                        <rn:widget path="input/FormInput" name="Incident.Threads" required="false" label_input="Comments or Concerns" />
                    </rn:condition>
                    <rn:condition logged_in="true">
                        <rn:widget path="input/FormInput" name="Incident.Threads" required="false" label_input="Comments or Concerns" initial_focus="true" />
                    </rn:condition>
                    <div id="html_element"></div>
                    <rn:widget path="input/FormSubmit" challenge_location="html_element" challenge_required="true" label_button="#rn:msg:SUBMIT_YOUR_QUESTION_CMD#" on_success_url="/app/payment/missedtransaction_confirm" error_location="rn_ErrorLocation" timeout="240" />

                </form>
            </div>
        </div>
    </div>
</div>