<rn:meta title="#rn:msg:ERROR_LBL#" template="responsive.php" login_required="true" login_required="false" />

<?list($errorTitle, $errorMessage) = getErrorMessageFromCode(getUrlParm('error_id'));?>
<div id="rn_PageTitle" class="rn_ErrorPage">
    <h1><?=$errorTitle;?></h1>
</div>
<div id="rn_PageContent" class="rn_ErrorPage">
    <div class="rn_Padding">
        <p><?=$errorMessage;?></p>
    </div>
</div>
