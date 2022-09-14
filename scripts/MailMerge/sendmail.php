<?php
/**********
 * Author:Zach Cordell
 * 
 * Script will scan themes/imports folder for csv files.  For each line an incident will be created.  
 * The summary along with any error will be logged in CO/carrier_error_log.  Once a file has been processed it will be moved
 * to the themes/imports/complete folder.  Files in the complete folder will be kept for 5 days and deleted.
 * 
 */

ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$ip_dbreq = true;
require_once('include/init.phph');

list ($common_cfgid, $rnw_common_cfgid, $rnw_ui_cfgid, $ma_cfgid) = msg_init($p_cfgdir, 'config', array('common', 'rnw_common', 'rnw_ui', 'ma'));
list ($common_mbid, $rnw_mbid) = msg_init($p_cfgdir, 'msgbase', array('common', 'rnw'));

require_once(get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');
ini_set('auto_detect_line_endings',TRUE);


$logArr = array($_POST['contactId'], $_POST['childImage'], $_POST['emailContact']);
_logToFile(27, "-----Starting ".$_POST['contactId']."----");

_logToFile(26, print_r($logArr, true));

if ($_POST['contactId']){
    
    $contact = RNCPHP\Contact::fetch($_POST['contactId']);
    $_POST['emailBody'] = html_entity_decode($_POST['emailBody']);
    $subject =  _getEmailSubject($_POST['emailBody']);
    
    $childImage = $_POST['childImage'];
    
    $fp = fopen('/tmp/mailmerge.txt', 'a');
    fwrite($fp, date("H:i:s   -").$childImage."\n");
    
    //checking if a child image exists.  don't send an email if it doesn't
    //this check should be turned off for statements.
    if(cfg_get(CUSTOM_CFG_CHECK_CHILD_IMG_EXISTS) == 1){
        
        $pos = strpos($childImage, '.JPG');
        if($pos === false){
            _logToFile(46, "letting suffix to lower case");
            $suffix = '.jpg';
        }else{
            $suffix = '.JPG';
        }

        $addtlChildImage = str_replace($suffix, "_".$_POST['contactId'].$suffix, $childImage);
        
        
        if(getimagesize($addtlChildImage)){
            _logToFile(56, "Found additional child photo:".$addtlChildImage);
            //found adn additional image, now replace that url with the one that is created in teh add in
            $_POST['emailBody'] = str_replace($childImage, $addtlChildImage, $_POST['emailBody']);
            
        }else if(getimagesize($childImage)){
            //image exists!
        }else{
            return;
        }
     }
        
        if($_POST['emailContact'] == "True"){
            fwrite($fp, date("H:i:s   -")."Sending Email\n");
            
            try{
                $mm = new RNCPHP\MailMessage();
                $mm -> To -> EmailAddresses = array($contact->Emails[0]->Address);
                $mm -> Subject = $subject;
                $mm -> Body -> Text = "This email contains HTML formatting.  If you wish to view this message online please log in at http://africanewlife.custhelp.com";
                $mm -> Body -> Html = $_POST['emailBody'];
                $mm -> Options -> IncludeOECustomHeaders = false;
                $result = $mm -> send();
            }catch(Exception $e){
                fwrite($fp, "exception: ".$e->getMessage()."\n");
            }catch(RNCPHP\ConnectAPIError $e) {
                fwrite($fp, "exception: ".$e->getMessage()."\n"); 
            }
            
            // fwrite($fp, "result: \n");
            // fwrite($fp, $mm -> Body -> Html);
            // fwrite($fp, print_r($result, true));
            // fwrite($fp, "end result: \n");
            
            print_r("result: \n");
            print_r($mm -> Body -> Html);
            print_r( $result, true);
            print_r( "end result: \n");
        }
        
        _createContactAttachment($_POST['emailBody'], $contact, $subject, $fp);
        _logToFile(98, "-----Ending ".$_POST['contactId']."----");
    
    fclose($fp);
    
    print "you just posted ".$_POST['contactId'];
    
}else{
    die("invalid");
}

function _createContactAttachment($htmlEmail, $contact, $subject, $fp){
    
    try{  
        print_r(date("H:i:s   -")."Creating Attachment \n");
        $contact->FileAttachments =new RNCPHP\FileAttachmentCommonArray();
        $fattach = new RNCPHP\FileAttachmentCommon();
        $fattach->ContentType = "text/html";
        $fp = $fattach->makeFile();
        fwrite($fp,$htmlEmail);
        fclose($fp);
        $fattach->FileName = substr($subject, 0, 95).".html";
        $fattach->Name = substr($subject, 0, 40);        
        $contact->FileAttachments[] = $fattach;
        $contact->save();
        print_r(date("H:i:s   -")."after save");
    }catch(Exception $e){
        print_r("exception: ".$e->getMessage());
    }catch(RNCPHP\ConnectAPIError $e) {
        print_r("exception: ".$e->getMessage()."\n"); 
    }
}

function _getEmailSubject($emailBody){

    $pattern = '/(?=<title>).*?(?=<\/title>)/';
    preg_match($pattern, $emailBody, $matches, PREG_OFFSET_CAPTURE, 3);
    $subject = str_replace("<title>", "", $matches[0][0]);
    
    return $subject;
}


function _logToFile($lineNum, $message){
    $filePointer = fopen('/tmp/mailingLogs/mailMergeLogs2_'.date("Ymd").'.log', 'a');
    fwrite($filePointer,  date('H:i:s.').":mailMerge sendMail @ $lineNum : ".$message."\n");
    fclose($filePointer);
}



?>