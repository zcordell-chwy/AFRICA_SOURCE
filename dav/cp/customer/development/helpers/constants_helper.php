<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

//$this -> CI -> load -> helper('constants');

$baseURL = \RightNow\Utils\Url::getShortEufBaseUrl(true);

define(CUSTOM_CFG_frontstream_endpoint_id, 1000001);
define(CUSTOM_CFG_frontstream_pass_id, 1000002);
define(CUSTOM_CFG_frontstream_user_id, 1000004);
define(CUSTOM_CFG_general_cc_error_id, 1000005);

define(GIFTS_APPEAL_ID, 839);//seem to be unused
DEFINE(GIFTS_FUND_ID, 292);//seem to be unused
define(DEFAULT_TRANSACTION_STATUS, 'Pending - Web Initiated');
define(DEFAULT_TRANSACTION_DESC, "Web Initiated Transaction");
define(TRANSACTION_PROCESSING_STATUS, "Processing");
define(TRANSACTION_SALE_SUCCESS_STATUS, 'Completed');
define(TRANSACTION_SALE_ERROR_STATUS, 'Error');



//there is an error with the Database and it can't lookup things by LookupName, so, we're switching to id's
define(DEFAULT_TRANSACTION_STATUS_ID, 1);
define(TRANSACTION_PROCESSING_STATUS_ID, 4);
define(TRANSACTION_SALE_SUCCESS_STATUS_ID, 3);
define(TRANSACTION_SALE_ERROR_STATUS_ID, 6);
 
define(PLEDGE_PROCESSING_STATUS, "Pledge Processing");
define(PLEDGE_SUCCESS_STATUS, 'Pledge submission completed');

define(DONATION_PAYMENT_SOURCE, 2);
define(DONATION_TYPE_PLEDGE, 1);
define(DONATION_TYPE_SPONSOR, 39);
define(DONATION_TYPE_GIFT, 38);

define(ADD_METHOD_TRACKING_ID, "addPaymentMethod");

define(FS_POSTBACK_URL, $baseURL . "/app/payment/successNewPM");
define(FS_ADD_PM_POSTBACK_URL, $baseURL . "/app/payment/success_paymethod");
 
define(FS_HEADER_URL, "#");
////https://africanewlife--tst.custhelp.com/euf/assets/themes/africa/images/anlm-header-logo.png");

//development credentials
//define(FS_MERCHANT_TOKEN, "2B2A1C9F7ED4E76600184CA9D");
//define(FS_USERNAME, "FThost5193");
//define(FS_USERTOKEN, "PdgAaExjPzkhF1aD1W43Zbvc48/Rl/UEkVDYS35vHRsQyFXVFnk2jlzD1x14y5Vp");
//define(FS_MERCHANT_KEY, "1245");

//old config settings
//CUSTOM_CFG_frontstream_pass = 6919RUw7
//CUSTOM_CFG_frontstream_user = iihj3840


//production credentials.
define(FS_MERCHANT_TOKEN, "2B2A1C9F7ED4E76600184CA9D");
define(FS_USERNAME, "xtti3002");
define(FS_USERTOKEN, "yAC/GV7JObMmlaoQGH7toRJ54N+7hWeddczX8nGokssyrfaaoeYRbtcDgr0oYMbs");
define(FS_MERCHANT_KEY, "1436");


define(FS_API_ENDPOINT, "https://secure.ftipgw.com/smartpayments/transact.asmx");
define(FS_COMSUMER_ENDPOINT, "https://partnerportal.fasttransact.net/Web/Payment.aspx");
define(FS_SALE_TYPE, "Sale");
define(FS_EFT_SALE_TYPE, "RepeatSale");//this is for EFT Transactions where we use a PNRef
define(FS_REFUND_TYPE, "Reversal");
define(FS_REVERSAL_TYPE, "Return");
define(FS_AUTH_TYPE, "Auth");

define(CHILD_NO_IMAGE_FILENAME, "NOCHILD.jpg"); 
define(CHILD_IMAGE_FILESYSTEM_DIR, "/vhosts/africanewlife/euf/assets/childphotos/hashedChildPhotos");
define(CHILD_IMAGE_URL_DIR, "/euf/assets/childphotos/hashedChildPhotos");

//sponsorship fields
define(NEEDY_CHILDREN_ID, "8793");//This is the general Needy Children "child" object.  used on teh /app/give page for a general gift
define(STATIC_SPONSORSHIP_RATE, 39);
define(WEBHOLDID, 1); //CHILDEVENTSTATUS

//used for setting appeal and fund in CP sponsorship
define(SPON_FUND_ID, 173);
define(WEB_APPEAL_ID, 515);

//PROD VALUES///

//define(CUSTOM_CFG_frontstream_endpoint_id, 1000001);
//define(CUSTOM_CFG_frontstream_pass_id, 1000002);
//define(CUSTOM_CFG_frontstream_user_id, 1000004);
//define(CUSTOM_CFG_general_cc_error_id, 1000005);
//
//define(GIFTS_APPEAL_ID, 839);
//DEFINE(GIFTS_FUND_ID, 292);
//define(DEFAULT_TRANSACTION_STATUS, 'Pending - Web Initiated');
//define(DEFAULT_TRANSACTION_DESC, "Web Initiated Transaction");
//define(TRANSACTION_PROCESSING_STATUS, "Processing");
//define(TRANSACTION_SALE_SUCCESS_STATUS, 'Completed');
//define(TRANSACTION_SALE_ERROR_STATUS, 'Error');
//
//define(PLEDGE_PROCESSING_STATUS, "Pledge Processing");
//define(PLEDGE_SUCCESS_STATUS, 'Pledge submission completed');
//
//define(DONATION_TYPE_PLEDGE, 0);
//define(DONATION_TYPE_SPONSOR, 1);
//define(DONATION_TYPE_GIFT, 2);
//
//define(ADD_METHOD_TRACKING_ID, "addPaymentMethod");
//define(FS_MERCHANT_TOKEN, "2B2A1C9F7ED4E76600184CA9D");
//define(FS_POSTBACK_URL, "https://africanewlife.custhelp.com/app/payment/successNewPM");
//define(FS_HEADER_URL, "#");
//////https://africanewlife--tst.custhelp.com/euf/assets/themes/africa/images/anlm-header-logo.png");
//define(FS_USERNAME, "FThost5193");
//define(FS_USERTOKEN, "PdgAaExjPzkhF1aD1W43Zbvc48/Rl/UEkVDYS35vHRsQyFXVFnk2jlzD1x14y5Vp");
//define(FS_MERCHANT_KEY, "1245");
//define(FS_API_ENDPOINT, "https://secure.ftipgw.com/smartpayments/transact.asmx");
//define(FS_COMSUMER_ENDPOINT, "https://partnerportal.fasttransact.net/Web/Payment.aspx");
//define(FS_SALE_TYPE, "Sale");
//define(FS_EFT_SALE_TYPE, "RepeatSale");//this is for EFT Transactions where we use a PNRef
//define(FS_REFUND_TYPE, "Reversal");
//define(FS_REVERSAL_TYPE, "Return");
//define(FS_AUTH_TYPE, "Auth");
//
//define(CHILD_NO_IMAGE_FILENAME, "../../NOCHILD.jpg");
//define(CHILD_IMAGE_FILESYSTEM_DIR, "/vhosts/africanewlife/euf/assets/childphotos/thumbnail");
//define(CHILD_IMAGE_URL_DIR, "/euf/assets/childphotos/thumbnail");
//
////sponsorship fields
//define(NEEDY_CHILDREN_ID, "8793");//This is the general Needy Children "child" object.  used on teh /app/give page for a general gift

