<?

/****
 * 
 * Developer zach Cordell
 * 
 * This script runs from a scheduled report.  101148 in production
 * 
 * Runs every 15 minutes Friday morning from midnight to 3am.  If letters ever gets bigger than 13*25, extend the number of runs in the schedule.
 * 
 */

 
define(BASE_OSVC_INTF_URL, 'http://africanewlife.custhelp.com');

ini_set('display_errors', 'On');
error_reporting(E_ERROR);

$ip_dbreq = true;
require_once ('include/init.phph');

list($common_cfgid, $rnw_common_cfgid, $rnw_ui_cfgid, $ma_cfgid) = msg_init($p_cfgdir, 'config', array('common', 'rnw_common', 'rnw_ui', 'ma'));
list($common_mbid, $rnw_mbid) = msg_init($p_cfgdir, 'msgbase', array('common', 'rnw'));

require_once (get_cfg_var("doc_root") . "/include/ConnectPHP/Connect_init.phph");
use RightNow\Connect\v1_2 as RNCPHP;
initConnectAPI('api_access', 'Password1');

require_once ('fpdf_pagegroups.php');

// Incident statuses
define(INC_STATUS_NEW, 1);
define(INC_STATUS_LETTER_PRINTED, 105);
// Incident categories
define(INC_CAT_ONLINE_LETTER, 10);


load_curl();


//what to name the file in dropbox and the path to the file (/tmp/2018-01-03/letters-1.pdf)
function sendToDropbox($dbFileName, $pathToFile){
    
    $token = RNCPHP\Configuration::fetch('CUSTOM_CFG_DROPBOX_TOKEN')->Value;

    $curl = curl_init();

    $path = $pathToFile;
    $fp = fopen($path, 'rb');
    $filesize = filesize($path);
    
    

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://content.dropboxapi.com/2/files/upload",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => fread($fp, $filesize),
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer $token",
        "cache-control: no-cache",
        "content-type: application/octet-stream",
        "dropbox-api-arg: {\"path\": \"/LettersUpload/".date('Y-m-d')."/".$dbFileName."\",\"mode\": \"add\",\"autorename\": true,\"mute\": false}"
      ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    print_r($response);
    print_r($err);
    if ($err) {
      //echo "cURL Error #:" . $err;
    } else {
      //echo $response;
    }
}


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

//dir to store all the pdf's
if(!is_dir('/tmp/letterupload/'.date('Y-m-d'))){
    mkdir('/tmp/letterupload/'.date('Y-m-d'), 0777, true);
}

        
    $roql = "Select Incident from Incident where Incident.Category.ID = " . INC_CAT_ONLINE_LETTER .
            " and Incident.StatusWithType.Status.ID = " . INC_STATUS_NEW . " ORDER BY Incident.CustomFields.CO.ChildRef.LookupName ASC, Incident.ID Limit 25";
    $res = RNCPHP\ROQL::queryObject($roql) -> next();
    
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $sendFiles = false;

    while ($inc = $res -> next()) {
        $sendFiles = true;
        $child = $inc->CustomFields->CO->ChildRef;
        $pledge = $inc->CustomFields->CO->PledgeRef;
    
        $pdf->StartPageGroup();
        $pdf -> AddPage();
        $pdf-> setRef($inc->ReferenceNumber);
        $pdf -> Image(BASE_OSVC_INTF_URL.'/euf/assets/images/ANLM_Logo_Black.jpg', 10, 6, 75, 25);
        $pdf->Ln(30);
        $pdf -> SetFont('Arial', 'I', 10);
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
            if($fattach->FileName == $inc->ReferenceNumber.".pdf"){
                continue;
            }
            $timeExt = time();
            $imgURL = $fattach -> getAdminURL();
            $imgURL = str_replace("https://", "http://", $imgURL);
            $tmpFileName = preg_replace('/[^a-z0-9\.]/', '', strtolower($timeExt . $fattach -> FileName));
            file_put_contents('/tmp/' . $tmpFileName, file_get_contents($imgURL));
            
            
            try{
                $pdf -> Image('/tmp/' . $tmpFileName, 10 , null , 150);
            }catch(\Exception $e){
                // Swallow error here, which is likely due to bad image format (non-jpeg), to prevent entire process from bombing.
                // Place message in PDF suggesting to user how to fix.
                //echo $e->getMessage()."<br/>";
                $imageNotValidMsg = RNCPHP\MessageBase::fetch(CUSTOM_MSG_img_not_valid) -> Value;
                $imageNotValidMsg = str_replace( "{FileName}" , $fattach -> FileName , $imageNotValidMsg);
                $imageNotValidMsg = str_replace( "{ReferenceNum}" , $inc->ReferenceNumber , $imageNotValidMsg);
                $pdf -> MultiCell(null, 5, $imageNotValidMsg, '', 2);
            }
        }
    
        // Update incident status to letter printed
        if($inc->StatusWithType){
            $inc->StatusWithType->Status->ID = INC_STATUS_LETTER_PRINTED;
            $inc->save(RNCPHP\RNObject::SuppressAll);
            //RNCPHP\ConnectAPI::commit();  
        }
    }

    if($sendFiles){
        $dateSuffix = date('Hi');
        $pdf -> Output('F', '/tmp/letterupload/'.date('Y-m-d').'/letters-'.$dateSuffix.'.pdf');
        sendToDropbox( 'letters-'.$dateSuffix.'.pdf', '/tmp/letterupload/'.date('Y-m-d').'/letters-'.$dateSuffix.'.pdf' );  
    }
    

