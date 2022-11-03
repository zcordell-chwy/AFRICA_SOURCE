<rn:meta title="#rn:msg:ACCOUNT_OVERVIEW_LBL#" template="basic.php" login_required="true" />

<div id="rn_PageContent" class="rn_AccountOverviewPage">
    <?
    $this->CI = get_instance();
    $profile = $this->CI->session->getProfile();

    if (getUrlParm('c_id') > 0 && getUrlParm('c_id') == $profile->c_id->value) { ?>

        <?
        initConnectAPI('cp_082022_user', '$qQJ616xWWJ9lXzb$');
        $attach_id = getUrlParm('attach_id');
        $contactObj = $this->CI->model('Contact')->get()->result;
        $attachments = $contactObj->FileAttachments;
        foreach ($attachments as $item) {
            print("hi");

            if ($attach_id == $item->ID) {

                logMessage($item->ContentType);

                $filePath = $item->getAdminUrl();
                $filePath = str_replace("https://", "http://", $filePath);
                $fileContents = file_get_contents($filePath);

                if ($item->ContentType == 'application/pdf') {
                    ob_clean();
                    flush();
                    header('Content-type: application/pdf');
                    header('Content-Transfer-Encoding: Binary');
                    header('Content-disposition: attachment; filename="' . $item->FileName . '"');
                    readfile($filePath);
                } else {
                    echo $fileContents;
                }
            }
        }
        ?>
    <? } else {
        header('Location: /app/account/communications/c_id/' . $profile->c_id->value);
    } ?>

</div>