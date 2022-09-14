<rn:meta title="#rn:msg:ERROR_LBL#" template="mobile.php" login_required="false" />

<?list($errorTitle, $errorMessage) = getErrorMessageFromCode(getUrlParm('error_id'));?>
<section id="rn_PageTitle" class="rn_ErrorPage">
    <h1><?=$errorTitle;?></h1>
</section>
<section id="rn_PageContent" class="rn_ErrorPage">
    <div class="rn_Padding">
        <p><?=$errorMessage;?></p>
    </div>
</section>
