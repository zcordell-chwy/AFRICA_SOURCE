<?
$sessionData = array('recentPledgeId' => \RightNow\Utils\Url::getParameter('pledge_id'));
$this -> session -> setSessionData($sessionData);
?>
<rn:meta title="Pledge Detail" template="responsive.php" login_required="true" clickstream="pledge_view"/>
<rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
<div class="rn_AfricaNewLifeLayoutSingleColumn">
	<rn:widget path="custom/eventus/pledgeform" pledge_id="#rn:php:getUrlParm('pledge_id')#" />
</div>