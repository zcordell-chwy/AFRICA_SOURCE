<?
	$CI = get_instance();

	// Don't require login if event allows anonymous advocacy, otherwise require it.
	$loginRequired = true;
	$eventID = \RightNow\Utils\Url::getParameter('event');
	if(!is_null($eventID)){
		try{
			$event = $this-> CI -> model('custom/event_model') -> getEvent($eventID);
			if(!is_null($event) && $event->AllowAnonymousAdvocacy){
				logMessage('$event = ' . var_export($event,true));
				logMessage('$event->AllowAnonymousAdvocacy = ' . var_export($event->AllowAnonymousAdvocacy,true));
				$loginRequired = false;
			}else{
				logMessage('Anonymous advocacy is not allowed for this event, let login default to required');
			}
		}catch(\Exception $e){
			// Couldn't read the 'AllowAnonymousAdvocacy' flag on event object, let login default to required
			logMessage("Couldn't read the 'AllowAnonymousAdvocacy' flag on event object, let login default to required");
		}
	}else{
		// Couldn't get event ID, let login default to required
		logMessage("Couldn't get event ID, let login default to required");
	}
	logMessage('$loginRequired = ' . var_export($loginRequired,true));
	
	// If login required and user not logged in (profile is null), redirect them to login page
	if($loginRequired){
		logMessage('Login is required to access advocacy page.');
		$profile = $CI->session->getProfile(true);
		if(is_null($profile)){
			logMessage('User is not logged in, redirecting to login page.');
			$loginURL = \RightNow\Utils\Url::getShortEufBaseUrl() . '/app/utils/login_form/redirect/advocacy';
			if(!is_null($eventID)){
				$loginURL = $loginURL . '/event/' . $eventID;
			}
			logMessage('$loginURL = ' . var_export($loginURL,true));
			header('Location: '. $loginURL);
		}
	}
?>
<rn:meta title="Advocate for a Child"  template="standard.php" login_required="false" clickstream="home"/>

<div id="rn_PageContent" class="rn_Home">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="Advocate for a Child" banner_img_path="/euf/assets/images/banners/sponsor.jpg" />
    <rn:condition url_parameter_check="success != null">
        <div id="advocateConfirmation" class="advocateConfirmation"></div>
    </rn:condition>
    <rn:condition url_parameter_check="event != null">
        <rn:widget path="custom/sponsorship/UnsponsoredChildGallery" rows="5" columns="5" advocacy_page="true"/>
    <rn:condition_else/>
       <span>An event must be specified to access the advocacy page.</span>
    </rn:condition>
</div>
