<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title><?=getMessage(ANSWER_PRINT_PAGE_LBL);?></title>
    <link href="/euf/assets/themes/standard/site.css" rel="stylesheet" type="text/css" media="all" />
    <link href="/rnt/rnw/yui_2.7/container/assets/skins/sam/container.css" rel="stylesheet" type="text/css" />
</head>

<body class="yui-skin-sam">
<div id="rn_Container">
    <div id="rn_Header"></div>
    <div id="rn_Navigation"></div>
    <div id="rn_Body">
      <div id="rn_MainColumn">
        <div id="rn_PageTitle" class="rn_AnswerDetail">
            <h1 id="rn_Summary"><?=$summary;?></h1>
            <div id="rn_AnswerInfo"></div>
            <?=$description;?>
        </div>
        <div id="rn_PageContent" class="rn_AnswerDetail">
            <div id="rn_AnswerText">
                <p><?=$solution;?></p>
            </div>
            <div id="rn_FileAttach" class="rn_FileListDisplay">
                <?if(count($fileAttachments) > 0):?>
                    <span class="rn_DataLabel"> <?= getMessage(FILE_ATTACHMENTS_LBL) ?> </span>
                    <div class="rn_DataValue rn_FileList">
                        <ul>
                            <?$loopCount = count($fileAttachments);
                            for($i=0; $i<$loopCount; $i++):?>
                            <li>
                                <a href="/ci/fattach/get/<?=$fileAttachments[$i][0] . '/' . $fileAttachments[$i][2] . sessionParm()?>" target="_blank"><?=$fileAttachments[$i]['icon'];?><?=$fileAttachments[$i][1];?></a>
                            </li>
                            <?endfor;?>
                        </ul>
                    </div>
                <?endif;?>
            </div>
        </div>
    </div>
  </div>
</div>
<script type="text/javascript">
    var tags = document.getElementsByTagName('a');
    for (var i=0; i<tags.length; i++)
    {
        var hashLocation = tags[i].href.split("#");
        //Fix anchor links (i.e. href="#Bottom") because of the base tag. Also don't change their target
        if(hashLocation[1] === undefined || hashLocation[0] !== "<?= getShortEufBaseUrl(false, '/');?>"){
            tags[i].target = "_blank";
        }
    }

    tags = document.getElementsByTagName('form');
    for (var i=0; i<tags.length; i++)
    {
        tags[i].onsubmit = function(){alert("<?=getMessageJS(DISABLED_FOR_PREVIEW_MSG);?>"); return false;};
    }
</script>

</body>
</html>
