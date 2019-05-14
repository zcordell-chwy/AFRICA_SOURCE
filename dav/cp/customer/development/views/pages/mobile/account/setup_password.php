<rn:meta title="#rn:msg:FINISH_ACCOUNT_CREATION_CMD#" template="mobile.php" login_required="false" />
<? /* This page is navigated to by following an email link when:
(A) An account is automatically created and an email is sent [techmail/chat]
(B) An existing user doesn't have a login and attempts to recover it */?>
<section id="rn_PageTitle" class="rn_Account">
    <h1>#rn:msg:FINISH_ACCOUNT_CREATION_CMD#</h1>
</section>
<section id="rn_PageContent">
    <div class="rn_Padding">
        <rn:widget path="login/ResetPassword2" label_heading="#rn:msg:CREATE_A_USERNAME_AND_PASSWORD_CMD#" on_success_url="#rn:php:'/app/account/profile/msg/' . urlencode(getMessage(SUCC_ACTIVATED_ACCT_PLS_COMP_MSG))#" />
    </div>
</section>
