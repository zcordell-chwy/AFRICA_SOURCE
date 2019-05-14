<?
$updatedPage = sprintf('/app/account/pledge/pledgedetail/pledge_id/%d', \RightNow\Utils\Url::getParameter('pledge_id'));
\RightNow\Utils\Framework::setLocationHeader($updatedPage);
?>
<rn:meta title="Pledge Detail" template="standard.php" login_required="true" clickstream="pledge_view"/>
<p>
	This page has moved, please access the <a href="<?=$updatedPage ?>">updated page</a>
</p>
 