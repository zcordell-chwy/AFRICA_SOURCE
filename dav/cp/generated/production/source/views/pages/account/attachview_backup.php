<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
   <?
   $ip_dbreq = true;
   $CI = & get_instance();
   $profile = $CI->session->getProfile();
   
   if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $CI->session->getProfileData('contactID')){?>
           
          <?
                initConnectAPI('api_access', 'Password1');
                $attach_id = getUrlParm('attach_id');
                $contactObj = $CI->model('Contact')->get()->result;
                $attachments = $contactObj->FileAttachments;
                foreach ($attachments as $item){

                    
                    if($attach_id == $item->ID){

                        logMessage($item->ContentType);
                        print_r($item->ContentType);die;
                        $filePath = $item->getAdminUrl();

                        $imgURL = str_replace("https://", "http://", $filePath);
                        // load_curl();
                        // $ch = curl_init();
                        // curl_setopt($ch, CURLOPT_URL, $imgURL);
                        // curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                        // $content = curl_exec ($ch);
                        // curl_close ($ch); 
                        // echo $content;die;

                        $fileContents = file_get_contents($imgURL);

                        if($item->ContentType == 'application/pdf'){
                            ob_clean();
                            flush();
                            header('Content-type: application/pdf');
                            header('Content-Transfer-Encoding: Binary');
                            header('Content-disposition: attachment; filename="'.$item->FileName.'"');
                            readfile($imgURL);
                        }else{
                            echo $fileContents;
                            // echo $content;
                        }

                        

                    }
                    
                }
          ?>  
    <?}else{     
        header('Location: /app/account/communications/c_id/'. $CI->session->getProfileData('contactID'));
    }?>
    
</div>