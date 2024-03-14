<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define hooks to extend Customer Portal functionality. Hooks allow
| you to specify custom code that you wish to execute before and after many
| important events that occur within Customer Portal. This custom code can modify data,
| perform custom validation, and return customized error messages to display to your users.
|
| Hooks are defined by specifying the location where you wish the hook to run as the array index
| and setting that index to an array of 3 items, class, function, and filepath. The 'class' index
| is the case-sensitive name of the custom model you wish to use. The 'function' index is the name
| of the function within the 'class' you wish to call. Finally, the 'filepath' is the location to
| your model, which will automatically be prefixed by models/custom/. The 'filepath' index only
| needs a value if your model is contained within a subfolder
|
|-----------------
| Hook Locations
|-----------------
|
|     pre_allow_contact      - Called before allowing a contact to access content.
|     pre_login              - Called immediately before user becomes logged in
|     post_login             - Called immediately after user has been logged in
|     pre_logout             - Called immediately before user logs out
|     post_logout            - Called immediately after user logs out
|     pre_contact_create     - Called before Customer Portal validation and contact is created
|     post_contact_create    - Called immediately after contact has been created
|     pre_contact_update     - Called before Customer Portal validation and contact is updated
|     post_contact_update    - Called immediately after contact is updated
|     pre_incident_create    - Called before Customer Portal validation and incident is created
|     post_incident_create   - Called immediately after incident has been created
|     pre_incident_update    - Called before Customer Portal validation and incident is updated
|     post_incident_update   - Called immediately after incident is updated
|     pre_feedback_submit    - Called before both site and answer feedback
|     post_feedback_submit   - Called after both site and answer feedback is submitted
|     pre_login_redirect     - Called before user is redirected to a new page because they are not logged in
|     pre_pta_decode         - Called before PTA string is decoded and converted to pairdata
|     pre_pta_convert        - Called after PTA string has been decoded and converted into key/value pairs
|     pre_page_render        - Called before page content is sent to the browser
|     pre_report_get         - Called before a report is retrieved
|     pre_report_get_data    - Called before submitting the report and allows for modification of the query parameters.
|     post_report_get_data   - Called after the report data has been retrieved and allows for modification of the report data.
|     pre_page_set_selection - Called before the user is redirected to a specific page set
|
|
| Please refer to the documentation for further information
|
|------------------
|Examples
|------------------
|
| Example 1 - Call the sendFeedback function immediately after an incident is created
|             using the Immediateincidentfeedback_model
|             (located at /models/custom/immediateincidentfeedback_model.php).
|
| $rnHooks['post_incident_create'] = array(
|        'class' => 'Immediateincidentfeedback_model',
|        'function' => 'sendFeedback',
|        'filepath' => ''
|    );

|=========================================================================================================

| Example 2 - Call the copyLogin function immediately before a contact is created using
|             the Customcontact_model (located at /models/custom/contact/customcontact_model.php)
|
| $rnHooks['pre_contact_create'] = array(
|        'class' => 'Customcontact_model',
|        'function' => 'copyLogin',
|        'filepath' => 'contact'
|    );
|=========================================================================================================

| Example 3 - First call the customValidation function from the Myfeedback_model 
|             (located at /models/custom/feedback/myfeedback_model.php) then call 
|             the sendFeedback function from Immediateincidentfeedback_model (located at
|             /models/custom/immediateincidentfeedback_model.php). The first function will be called
|             before the feedback is submitted. The second will be called after.
|
| $rnHooks['pre_feedback_submit'][] = array(
|        'class' => 'Myfeedback_model',
|        'function' => 'customValidation',
|        'filepath' => 'feedback'
|    );
| $rnHooks['post_feedback_submit'][] = array(
|        'class' => 'Immediateincidentfeedback_model',
|        'function' => 'sendFeedback',
|        'filepath' => ''
|    );
|=========================================================================================================
*/
$rnHooks['pre_report_get'][] = array(
    'class' => 'hooks_model',
    'function' => 'pre_report_get',
    'filepath' => ''
);
$rnHooks['post_report_get_data'][] = array(
    'class' => 'hooks_model',
    'function' => 'post_report_get_data',
    'filepath' => ''
);
//pre_page_render
$rnHooks['pre_page_render'][] = array(
    'class' => 'hooks_model',
    'function' => 'prePageRenderModel',
    'filepath' => ''
);

