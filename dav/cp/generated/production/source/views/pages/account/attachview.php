<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="standard.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
   <?
   $this -> CI = get_instance();
   $profile = $this -> CI -> session -> getProfile();
   
   if ($this->CI->session->getProfileData('contactID') > 0 && $this->CI->session->getProfileData('contactID') == $profile->c_id->value){?>
           
          <?
                initConnectAPI('api_access', 'Password1');
                $attach_id = getUrlParm('attach_id');
                $contactObj = $this -> CI -> model('Contact') -> get() -> result;
                $attachments = $contactObj->FileAttachments;
                foreach ($attachments as $item){

                    
                    if($attach_id == $item->ID){

                        logMessage($item->ContentType);

                        $filePath = $item->getAdminUrl();
                        $imgURL = str_replace("https://", "http://", $filePath);

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
                        }

                        

                    }
                    
                }
          ?>  
    <?}else{     
        // header('Location: /app/account/communications/c_id/'.$profile->c_id->value);
    }?>
    
</div>