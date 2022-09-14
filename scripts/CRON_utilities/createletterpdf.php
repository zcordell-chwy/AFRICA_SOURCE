<?
define(BASE_OSVC_INTF_URL, 'http://africanewlife--tst.custhelp.com');

ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$ip_dbreq = true;
require_once ('include/init.phph');

list($common_cfgid, $rnw_common_cfgid, $rnw_ui_cfgid, $ma_cfgid) = msg_init($p_cfgdir, 'config', array('common', 'rnw_common', 'rnw_ui', 'ma'));
list($common_mbid, $rnw_mbid) = msg_init($p_cfgdir, 'msgbase', array('common', 'rnw'));


require_once (get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');


try{
    
    require_once ('/cgi-bin/africanewlife.cfg/scripts/custom/LettersAdmin/fpdf_pagegroups.php');

    class PDF extends PDF_PageGroup {
        
        public $ref;    
        // Page header
        function setRef($refNum){
            $this->ref = $refNum;
        }
    
        // Page footer
        function Footer() {
            // Position at 1.5 cm from bottom
            $this -> SetY(-15);
            // Arial italic 8
            $this -> SetFont('Arial', 'I', 8);
            // Page number
            $this -> Cell(0, 10, $this->ref.' Page ' . $this->GroupPageNo().'/'.$this->PageGroupAlias(), 0, 0, 'C');
        }
    }
}catch(Exception $e){
  _logToFile($e->getMessage());
}catch(RNCPHP\ConnectAPIError $err){
  _logToFile($err->getMessage());
}

//createPDF(3111);

function createPDF($incID){

    _logToFile("\n_____________Begin create letter:".$incID."______________", 51);

    $inc = RNCPHP\Incident::fetch(intval($incID));
    
    if(checkforpdf($inc)){
        _logToFile("PDF Exists for :".$incID, 56);
        return;
    }
    try{
        $child = $inc->CustomFields->CO->ChildRef;
        $pledge = $inc->CustomFields->CO->PledgeRef;
        
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->StartPageGroup();
        $pdf -> AddPage();
        $pdf -> Image('http://africanewlife.custhelp.com/euf/assets/images/ANLM_Logo_Black.jpg', 10, 6, 75, 25);
        $pdf->Ln(30);
        $pdf -> SetFont('Arial', 'I', 10);
        $pdf-> setRef($inc->ReferenceNumber);
        if(!empty($pledge->CorrespondenceContact)){
            $fromLabel = $pledge->CorrespondenceContact . ' ' . $inc->PrimaryContact->Name->Last;
        }else{
            $spouse = ($inc->PrimaryContact->CustomFields->c->spousefirstname != "") ? $inc->PrimaryContact->CustomFields->c->spousefirstname : "";
                
            if(!empty($spouse)){
                $fromLabel = $inc->PrimaryContact->Name->First . ' & ' . $spouse . ' ' . $inc->PrimaryContact->Name->Last;
            }else{
                $fromLabel = $inc->PrimaryContact->Name->First . ' ' . $inc->PrimaryContact->Name->Last;
            }
        }
        $pdf -> MultiCell(null, 4, 
            "Sponsor Letter from: ".$fromLabel.
            "\nChild Ref: " . $child->ChildRef . 
            "\nChild Name: " . $child->FullName . 
            "\nDate: ".date('Y-m-d'), '', 2);
        $pdf->Ln(8);
        $pdf -> SetFont('Arial', null , 12);
        //echo $inc->Threads[0]->Text;
        //echo "<pre>";
        $sentences = preg_split("/\\r\\n|\\r|\\n/", $inc -> Threads[0] -> Text);
            
        foreach ($sentences as $sentence) {
            $pdf -> MultiCell(null, 5, "$sentence", '', 2);
        }
        
        foreach ($inc->FileAttachments as $fattach) {
            $timeExt = time();
            $imgURL = $fattach -> getAdminURL();
            $imgURL = str_replace("https://", "http://", $imgURL);
            $tmpFileName = preg_replace('/[^a-z0-9\.]/', '', strtolower($timeExt . $fattach -> FileName));
            file_put_contents('/tmp/' . $tmpFileName, file_get_contents($imgURL));
                
                
            try{
                $pdf -> Image('/tmp/' . $tmpFileName, 10 , null , 150);
            }catch(\Exception $e){
                _logToFile("Exception:".$e->getMessage(), 108);
                // Swallow error here, which is likely due to bad image format (non-jpeg), to prevent entire process from bombing.
                // Place message in PDF suggesting to user how to fix.
                $imageNotValidMsg = RNCPHP\MessageBase::fetch(CUSTOM_MSG_img_not_valid) -> Value;
                $imageNotValidMsg = str_replace( "{FileName}" , $fattach -> FileName , $imageNotValidMsg);
                $imageNotValidMsg = str_replace( "{ReferenceNum}" , $inc->ReferenceNumber , $imageNotValidMsg);
                $pdf -> MultiCell(null, 5, $imageNotValidMsg, '', 2);
            }
        }

        $pdf -> Output("F", "/tmp/".$inc->ReferenceNumber.".pdf");
        
        //should be saved to tmp now
        if (file_exists("/tmp/".$inc->ReferenceNumber.".pdf")){
            //save to incident
            $fattach = new RNCPHP\FileAttachmentIncident();
            $fattach->ContentType = "application/pdf";
            $fattach->setFile("/tmp/".$inc->ReferenceNumber.".pdf");
            $fattach->FileName = $inc->ReferenceNumber.".pdf";
            $fattach->Name = $inc->ReferenceNumber.".pdf";
            $inc->FileAttachments[] = $fattach;

            $inc->save();
        }

        _logToFile("____________________End create letter:".$incID."_____________\n ", 133);
        //$pdf -> Output();
    }catch(Exception $e){
      _logToFile("Exception:".$e->getMessage(), 136);
      echo($e->getMessage());
    }catch(RNCPHP\ConnectAPIError $err){
      _logToFile("Exception:".$err->getMessage(), 139);
      echo($err->getMessage());
    }
    

    
    return true;
}

function checkforpdf($inc){
    
    foreach ($inc->FileAttachments as $attach){
        //echo $attach->FileName." = ". $inc->ReferenceNumber.".pdf";
        if($attach->FileName == $inc->ReferenceNumber.".pdf"){
            return true;
        }
    }
    return false;
}

function _logToFile( $message, $lineNum){
    
    $dirName = '/tmp/letterLogs';
    if (!is_dir($dirName)){
        $oldumask = umask(0);
        mkdir($dirName, 0775, true);
        umask($oldumask);
    }

    $fp = fopen('/tmp/letterLogs/logs_'.date("Ymd").'.log', 'a');
    fwrite($fp,  "create letter on incident @ $lineNum : ".$message."\n");
    fclose($fp);
    
}