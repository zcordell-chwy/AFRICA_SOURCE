<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="responsive.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
    <rn:widget path="custom/aesthetic/ImageBannerTitle" banner_title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" banner_img_path="/euf/assets/images/banners/account.jpg" />
    <rn:widget path="custom/aesthetic/AccountSubNav" />
    
    <div class=" rn_AfricaNewLifeLayoutSingleColumn">
        <?if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value){?>
            <h2><a class="rn_Profile" href="javascript: void(0)" style="cursor: default">Documents and Photos</a></h2>
            <div id="rn_FileAttach">
                    <rn:widget path="custom/eventus/ContactsFileListDisplay" content_type_allowed='text/html, application/pdf, image/pjpeg' include_only='Emailed_2015_Tax_Statement.html, Printed 2015 Tax Statement.pdf' name="Contacts.FileAttachments" label="#rn:msg:FILE_ATTACHMENTS_LBL#"/>
            </div>
            
        <?}else{   
            header('Location: /app/account/communications/c_id/'.$profile->c_id->value);
        }?>
            
    </div> 
</div>  