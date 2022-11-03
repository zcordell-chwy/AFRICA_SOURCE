<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "https://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html lang="#rn:language_code#">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>  
        <meta http-equiv="X-Frame-Options" content="allow">
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/standard"/>
                
 
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
                <rn:head_content/>
        <style type="text/css">
        
            body {
                background: #e9e3dc;
            }
            #rn_SkipNav a{
                left:0px;
                height:1px;
                overflow:hidden;
                position:absolute;
                top:-500px;
                width:1px;
            }
            #rn_SkipNav a:active, #rn_SkipNav a:focus{
                background-color:#FFF;
                height:auto;
                left:auto;
                top:auto;
                width:auto;
            }
            .rn_Header{
                font-weight:bold;
                font-size:18pt;
            }
            .rn_LinksBlock a{
                display:block;
                margin-bottom:10px;
            }
            a img{
                border:0;
            }
            .rn_CenterText{
                text-align:center;
            }
            h1{
                font-weight:bold;
                font-size:16pt;
                line-height:1.4em;
                margin:0;
                padding:0;
            }
            h2{
                font-size:14pt;
                line-height:1.3em;
                margin:0;
                padding:0;
            }
            h3{
                font-size:12pt;
                line-height:1.2em;
                margin:0;
                padding:0;
            }
        </style>
        <rn:head_content/>
        
    </head>
    <body>
        <div class="ResponsiveRow">
            <div class="ResponsiveCol-12 no-padding">
                <a id="rn_HeaderLogoLink" href="https://www.africanewlife.org">
                    <img src="/euf/assets/images/afnl-header-logo.png" height="38" width="163" title="Africa New Life" alt="Africa New Life">
                </a>
            </div>
        </div>
        <div id="rn_SkipNav"><a href="<?=ORIGINAL_REQUEST_URI?>#rn_MainContent">#rn:msg:SKIP_NAVIGATION_CMD#</a></div>
        <rn:widget path="utils/CapabilityDetector"/>

        <div><a id="rn_MainContent"></a></div>
        <rn:page_content/>

    </body>
</html>