<?php

//define(BASE_SITE_URL, 'https://africanewlife--tst.custhelp.com');
define(BASE_SITE_URL, 'https://' . $_SERVER['HTTP_HOST']);

// Find our position in the file tree
if (!defined('DOCROOT')) {
    $docroot = get_cfg_var('doc_root');
    define('DOCROOT', $docroot);
}

/************* Agent Authentication ***************/

// Set up and call the AgentAuthenticator
require_once (DOCROOT . '/include/services/AgentAuthenticator.phph');

function getPass() {
    if (!empty($_POST['password'])) {
        return htmlspecialchars(trim($_POST['password']));
    }

    return "";

}

function getUser() {
    if (!empty($_POST['username'])) {
        return htmlspecialchars(trim($_POST['username']));
    }
    return '';
}

/**
 *  Set up and call the AgentAuthenticator
 */
function authenticate($userName, $password) {
    require_once (DOCROOT . '/include/services/AgentAuthenticator.phph');
    return AgentAuthenticator::authenticateCookieOrCredentials($userName, $password);
}

$session = authenticate(getUser(), getPass());

// Set up versioned namespace for the Connect PHP API
use RightNow\Connect\v1_3 as RNCPHP;

$account = RNCPHP\Account::fetch($session['acct_id']);


?>

<html lang="en-US" xml:lang="en-US" xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <!-- Begin Inspectlet Embed Code -->
<script type="text/javascript" id="inspectletjs">
window.__insp = window.__insp || [];
__insp.push(['wid', 516718186]);
(function() {
function ldinsp(){if(typeof window.__inspld != "undefined") return; window.__inspld = 1; var insp = document.createElement('script'); insp.type = 'text/javascript'; insp.async = true; insp.id = "inspsync"; insp.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cdn.inspectlet.com/inspectlet.js'; var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(insp, x); };
setTimeout(ldinsp, 500); document.readyState != "complete" ? (window.attachEvent ? window.attachEvent('onload', ldinsp) : window.addEventListener('load', ldinsp, false)) : ldinsp();
})();
</script>
<!-- End Inspectlet Embed Code -->
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.0/jquery-ui.js"></script>

        
        <title>Africa New Life Ministries Radiothon Donations</title>
        <script>
            var contactList = [],
                importForm = null,
                contactLookup = null,
                contactListTable = null,
                lastStopDateVal = "",
                lastPledgeObj = null,
                baseSiteURL = "<?= BASE_SITE_URL ?>";
            

            $(document).ready(function(){
                importForm = $("#importForm");
                contactLookup = $("#contactLookup");
                contactListTable = $("#contactListTable");
                contactListSearchError = $("#contactListSearchError");
                contactListBody = $("#contactListBody");
                contactLookup.show();
                importForm.hide();
                contactListTable.hide();
                contactListSearchError.hide();
                contactListBody.show();

                $("input[name=frequency]:radio").change(function(){
                    if($("input[name=frequency]:checked").val() == "1"){
                        // If value = "one-time", hide stop date
                        $("#stopLabel").hide();
                        $("#stop").hide();
                        lastStopDateVal = $("#stop").val();
                        $("#stop").val("0");
                    }else{
                        $("#stopLabel").show();
                        $("#stop").show();
                        $("#stop").val(lastStopDateVal);
                    }
                });

                $("#scriptExpandCollapseToggle").click(function(e){
                    var toggle = $(this),
                        scriptContainer = $("#scriptContainer");
                    if(toggle.hasClass("Maximized")){
                        // minimizing
                        scriptContainer.css("height", "30px");
                        toggle.html("Maximize Tips");
                        toggle.removeClass("Maximized");
                        toggle.addClass("Minimized");
                    }else{
                        // maximizing
                        scriptContainer.css("height", "auto");
                        toggle.html("Minimize Tips");
                        toggle.removeClass("Minimized");
                        toggle.addClass("Maximized");
                    }
                });

                $("#refreshPaymentWindowButton").click(function(){
                    if(lastPledgeObj){
                        buildPaymentFrame(lastPledgeObj.data.Transaction, lastPledgeObj.data.futureDate);
                    }
                });

                 $("#filterButton").click(function() { 
                    $.ajax({
                      url: "paymentapi.php?action=getcontactlist",
                      context: document.body,
                      data: getContactSearchFilters(),
                      type: "POST",
                      dataType: "json"
                    }).done(function(response) {
                        contactListTable.show();
                        if(response.status == "success"){
                            if(response.contacts.length == 1){
                                var contact = response.contacts[0];
                                setContactOnForm(contact);
                            }else{
                                contactList = response.contacts;
                                contactListBody.html("");
                                $.each(contactList, function(index, contact){
                                    var contactTR = $("<tr contact_id=\"" + contact.ID + "\"><td>" + contact.LastName + "</td><td>" + 
                                        contact.FirstName + "</td><td>" + contact.Email + "</td><td>" + 
                                        contact.Phone + "</td><td>" + contact.Street + "</td><td>" +
                                        "<button class=\"ContactSelectButton\">Select Contact</button></td></tr>");

                                    contactTR.find(".ContactSelectButton").on("click", onContactSelectFromList);

                                    contactListBody.append(contactTR);
                                });
                                contactListTable.show();
                                contactListSearchError.hide();
                            }
                        }else{
                            var errMsg = response && response.msg && response.msg != "" ? 
                                response.msg : "An error was encountered while trying to lookup contact, please try again later."; 
                            contactListSearchError.html(errMsg);
                            contactListTable.hide();
                            contactListSearchError.show();
                        }
                    }); 
                 }); 
                
            });


             
             
              $( function() {
                $( "#start" ).datepicker({ minDate: 0 });
              } );
              
              $( function() {
                $( "#stop" ).datepicker({ minDate: 0 });
              } );
  
            function onContactSelectFromList(element){
                var selectedContactID = parseInt($(this).parent().parent().attr("contact_id"));
                $.each(contactList, function(index, contact){
                    if(contact.ID == selectedContactID){
                        setContactOnForm(contact);
                    }
                });
            }

            function setContactOnForm(contact){
                contactLookup.hide();
                importForm.show();
                $("#first").val(contact.FirstName);
                $("#last").val(contact.LastName);
                $("#email").val(contact.Email);
                $("#email").prop("readonly", true);
                $("#phone").val(contact.Phone);
                $("#street").val(contact.Street);
                $("#city").val(contact.City);
                $("#state").val(contact.StateOrProvince);
                $("#postal").val(contact.PostalCode);
                if(contact.EmailPref){
                    $("#emailpref option[value=\"" + contact.EmailPref + "\"]").attr("selected",true);
                }
            }

            function getContactSearchFilters(){
                var contactSearchFilters = {
                    first : document.getElementById("filterFirst").value,
                    last : document.getElementById("filterLast").value,
                    email : document.getElementById("filterEmail").value,
                    phone : document.getElementById("filterPhone").value,
                    street: document.getElementById("filterStreet").value 
                };

                return contactSearchFilters;
            }
            
            function getFormData(field) {
                var element = document.getElementById(field);
                return element.value;
            }


            function submitListener() {

                var buttonElement = document.getElementById("submitBtn");
                buttonElement.onclick = function() {
                    
                       formData = {
                            first : document.getElementById("first").value,
                            last : document.getElementById("last").value,
                            email : document.getElementById("email").value,
                            phone : document.getElementById("phone").value,
                            street : document.getElementById("street").value,
                            city : document.getElementById("city").value,
                            country : document.getElementById("country").value,
                            state : document.getElementById("state").value,
                            postal : document.getElementById("postal").value,
                            frequency : $("input[name=frequency]:checked").val(),
                            amount : document.getElementById("amount").value,
                            start : document.getElementById("start").value,
                            stop : document.getElementById("stop").value,
                            check : document.getElementById("check").checked,
                            emailpref: document.getElementById("emailpref").value,
                            notes: document.getElementById("notes").value, 
                            appeal: document.getElementById("appeal").value,
                        };
                    
                    if(validateFields(formData)){
                    
                        buttonElement.setAttribute("disabled", "true");
                        
                        $.post( "paymentapi.php?action=createpledge", { formData })
                              .done(function( data ) {
                                  
                                var obj = JSON.parse(data);
                                lastPledgeObj = obj;
                                
                                if(obj.data.isCheck == "true"){
                                    var addDetails = "Please instruct customer to send check to: <br/><br/> Africa New Life Ministries <br/> 7405 SW Tech Center Dr. #144 <br/> Portland, OR 97223.<br/><br/>Please reference Pledge # " + obj.data.Pledge + " on your check";
                                }else{

                                    var addDetails = "Please process credit card or ACH.";
                                    if(obj.data.futureDate){
                                        addDetails = addDetails + "<br/><br/>Due to a future date selected. The amount show of the transaction will be a nominal amount </br> to approve the card, then refunded.";
                                    }
                                    //Disable actually creating payment outside OSvC on test site
                                    buildPaymentFrame(obj.data.Transaction, obj.data.futureDate);
                                }
                                $("#pledgeDetails").html("<table><tr><td>Pledge ID Created:</td> <td>" + obj.data.Pledge + "</td></tr>  <tr><td>Contact ID: </td><td>" + obj.data.Contact + "</td></tr><tr><td>Transaction ID: </td><td>" + obj.data.Transaction + "</td></tr><tr><td colspan = 2>" + addDetails + "</td></tr></table>");
                                
                                
                         });

                    }

                    
                }
            }

            function buildPaymentFrame(transId, futureDate){
                
                var targetIframe = "paymentIdFrame";
                postToIframe(transId, futureDate);
                if($("#newPaymentContainer").hasClass("rn_Hidden")){
                    $("#newPaymentContainer").toggleClass("rn_Hidden");
                }   
                $('#' + targetIframe).load(handleIframeLoad);
                
            }
            
            function postToIframe(transId, futureDate) {
                var targetIframe = "paymentIdFrame";
        
                //nominal charge for approving card, will be refunded after approval
                var amountToCharge = (futureDate) ? 2 + (Math.floor((Math.random() * 100) + 1) / 100) : document.getElementById("amount").value;
        
                $('body').append('<form action="https://partnerportal.fasttransact.net/Web/Payment.aspx" method="post" target="' + targetIframe + '" id="postToIframe"></form>');
                
                
                $('#postToIframe').append('<input type="hidden" name="EmailAddress" value="' + document.getElementById("email").value + '" />');
                $('#postToIframe').append('<input type="hidden" name="FirstName" value="' + document.getElementById("first").value + '" />');
                $('#postToIframe').append('<input type="hidden" name="LastName" value="' + document.getElementById("last").value + '" />');
                $('#postToIframe').append('<input type="hidden" name="PaymentAmount" value="' + amountToCharge + '" />');
                $('#postToIframe').append('<input type="hidden" name="BillingStreetAddress" value="' + document.getElementById("street").value + '" />');
                $('#postToIframe').append('<input type="hidden" name="BillingStreetAddress2" value="" />');
                $('#postToIframe').append('<input type="hidden" name="BillingCity" value="' + document.getElementById("city").value + '" />');
                $('#postToIframe').append('<input type="hidden" name="BillingStateOrProvince" value="' + $("#state option:selected").text() + '" />');
                $('#postToIframe').append('<input type="hidden" name="BillingPostalCode" value="' + document.getElementById("postal").value.substr(0,5) + '" />');
                $('#postToIframe').append('<input type="hidden" name="BillingCountry" value="US" />');
                $('#postToIframe').append('<input type="hidden" name="PaymentButtonText" value="" />');
                $('#postToIframe').append('<input type="hidden" name="NotificationFlag" value="0" />');
                $('#postToIframe').append('<input type="hidden" name="TrackingID" value="" />');
                $('#postToIframe').append('<input type="hidden" name="StyleSheetURL" value="' + baseSiteURL + '/euf/assets/themes/africa/payment.css" />');
                $('#postToIframe').append('<input type="hidden" name="MerchantToken" value="<?=cfg_get(CUSTOM_CFG_merchant_token);?>" />');//CUSTOM_CFG_merchant_token
                $('#postToIframe').append('<input type="hidden" name="PostbackURL" value="' + baseSiteURL + '/cgi-bin/africanewlife.cfg/php/custom/PledgeDrive/paymentapi.php?action=transactionreply" />');
                $('#postToIframe').append('<input type="hidden" name="PostBackRedirectURL" value="' + baseSiteURL + '/cgi-bin/africanewlife.cfg/php/custom/PledgeDrive/paymentapi.php?action=transactionreply" />');
                $('#postToIframe').append('<input type="hidden" name="PostBackErrorURL" value="' + baseSiteURL + '/cgi-bin/africanewlife.cfg/php/custom/PledgeDrive/paymentapi.php?action=errorlocation">');
                $('#postToIframe').append('<input type="hidden" name="SetupMode" value="Direct" />');
                $('#postToIframe').append('<input type="hidden" name="InvoiceNumber" value="' + transId + '" />');
                $('#postToIframe').append('<input type="hidden" name="HeaderImageURL" value="#" />');
                $('#postToIframe').append('<input type="hidden" name="DirectUserName" value="<?=cfg_get(CUSTOM_CFG_frontstream_user);?>" />');//CUSTOM_CFG_frontstream_user
                $('#postToIframe').append('<input type="hidden" name="DirectMerchantKey" value="<?=cfg_get(CUSTOM_CFG_merchant_key);?>">');//CUSTOM_CFG_merchant_key
                $('#postToIframe').append('<input type="hidden" name="DirectUserToken" value="<?=cfg_get(CUSTOM_CFG_direct_user_token);?>" />'); //CUSTOM_CFG_direct_user_token
                $('#postToIframe').append('<input type="hidden" name="NotificationType" value="" />');
                
                /*
                This was an attempt to intercept the POST to partnerportal.fasttransact.net so that we could log an error response. However,
                try as I might, I cannot get jquery .ajax to like the response, which is HTML content, so it always treats it as an error. In
                any event, I don't think this is where Africanewlife is running into issues, since all we're doing here is loading the payment
                form in an iframe. It could be the payment form isn't behaving, but we have no way of logging that to my knowledge.

                $("#postToIframe").on("submit", function(event){
                    event.preventDefault();
                    var action = $(this).attr("action");
                    var formData = $(this).serialize();
                    $.post({
                        url: action,
                        data: formData,
                        method: "POST",
                        dataType: "html",
                        success: function(response, status){
                            $("#" + targetIframe).contents().find('body').html(response);
                            $('#postToIframe').remove(); },
                        error: function(jqXHR, status, error){
                            var errorMsg = "Error sending HTTTP POST request to: " + action + " Status: " + status + " Error: " + error;
                            errorMsg = errorMsg + "<br/><br/>Form data: " + formData;
                            $("#" + targetIframe).contents().find('body').html(errorMsg);
                            logMessage(errorMsg.replace("<br/>", "\n").replace("<br/>", "\n"));
                            $('#postToIframe').remove(); }
                    });
                });*/

                $('#postToIframe').submit().remove();
            }
        
            function handleIframeLoad(args1, args2) {
                
                if ($("#paymentIdFrame").src == (baseSiteURL + "/cgi-bin/africanewlife.cfg/php/custom/PledgeDrive/paymentapi.php?action=transactionreply")) {
                    alert("store new payment method");
                }
            }
            
            function validateFields(formData){
                
                var pass = true;
                
                if (!formData.first){
                    pass = false;
                    $("#firstLabel").addClass( "error");   
                }else{
                    $("#firstLabel").removeClass( "error"); 
                }
                
                if (!formData.last){
                    pass = false;
                    $("#lastLabel").addClass( "error");   
                }else{
                    $("#lastLabel").removeClass( "error"); 
                }
                
                
                if (!formData.email){
                    pass = false;
                    $("#emailLabel").addClass( "error");   
                }else{
                    $("#emailLabel").removeClass( "error"); 
                }
                
                var regex = new RegExp("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$");
                if(!regex.test(formData.email)){
                    pass = false;
                    $("#emailLabel").addClass( "error");   
                }else{
                    $("#emailLabel").removeClass( "error"); 
                }

                
                if (!formData.street){
                    pass = false;
                    $("#streetLabel").addClass( "error");   
                }else{
                    $("#streetLabel").removeClass( "error"); 
                }
                
                if (!formData.city){
                    pass = false;
                    $("#cityLabel").addClass( "error");   
                }else{
                    $("#cityLabel").removeClass( "error"); 
                }
                
                if (!formData.state){
                    pass = false;
                    $("#stateLabel").addClass( "error");   
                }else{
                    $("#stateLabel").removeClass( "error"); 
                }
                
                if (!formData.postal){
                    pass = false;
                    $("#postalLabel").addClass( "error");   
                }else{
                    $("#postalLabel").removeClass( "error"); 
                }

                if($("input[name=frequency]:checked").length == 0){
                    pass = false;
                    $("#frequencyLabel").addClass( "error");
                }else{
                    $("#frequencyLabel").removeClass( "error");
                }

                if (!formData.amount){
                    pass = false;
                    $("#amountLabel").addClass( "error");   
                }else{
                    $("#amountLabel").removeClass( "error"); 
                }
                
                if (!formData.start){
                    pass = false;
                    $("#startLabel").addClass( "error");   
                }else{
                    $("#startLabel").removeClass( "error"); 
                }

                if(formData.emailpref == ""){
                    pass = false;
                    $("#emailprefLabel").addClass( "error");   
                }else{
                    $("#emailprefLabel").removeClass( "error"); 
                }
                
                return pass;
                
            }

            function logMessage(msg){
                $.ajax({
                      url: "paymentapi.php?action=logmessage",
                      context: document.body,
                      data: {msg: msg},
                      type: "POST",
                      dataType: "json"
                    }).done(function(response){
                    });
            }
            
            
            document.addEventListener("DOMContentLoaded", function(event) {
                submitListener();
            });
            
        </script>
        <link href="/rnt/rnw/css/admin.css" type="text/css" rel="stylesheet">
        <style>
            input[readonly]{
                color: gray;
            }
            .hidden {
                display: none;
                visibility: hidden;
            }
            .label {
                font-weight: bold;
            }
            #log ul {
                display: block;
            }
            #log ul li {
                list-style: none;
                border-bottom: thin solid black;
                padding: 1em;
            }
            #pledge_file {
                border: 1px solid black;
                margin-bottom: 10px;
                padding: 10px;
            }
            #inputButton {
                margin-left: 10px;
            }
            
            .formGroup div{
                
                margin-bottom:14px;
                
            }
            
            label.rn_Label {
                width: 200px;
                float: left;
                text-align: right;
                margin-right: 30px;
            }
            
            .error{
                color:red;
            }
            
            .rightContainer{
                float:left;
            }
            #importForm{
                float:left;
            }
            
            .rn_Hidden{
                display:none;
            }
            
            #newPaymentContainer{
                width: 464px;
            }
            
            #newPaymentContainer, #pledgeDetails{
                float:left;
            }
            
            #pledgeDetails{
                padding-left:15px;
            }
            
            td.filterButton{
                vertical-align: bottom;
            }

            #contactListSearchError{
                color: red;
                padding: 10px;
            }
            #contactListTable{
                padding: 10px;
            }
            #scriptContainer{
                position: absolute;
                top: 0;
                right: 0;
                width: 500px;
                padding: 10px;
                background-color: white;
                border-left: 1px solid #444;
                border-bottom: 1px solid #444;
                overflow: hidden;
                z-index: 10;
                height:30px
            }
            #scriptContainer h2{
                margin-top: 8px;
            }
            #scriptContainer ul{
                list-style-position: inside;
            }
        </style>
    </head>
    <body>
        <div>
        <?
                    
            $appealID = intval($_GET['appeal']);
            
            if(is_int($appealID) && $appealID > 0){
                
                try{
                    $appeal = RNCPHP\donation\Appeal::fetch($appealID);
                    if(!$appeal->ID){
                        $appealDesc =  "The appeal ID is incorrect or blank.  Please correct this issue before continuing.";
                    }else{
                        $appealCode = "(".$appeal->Code.")";
                    }
                }catch(\Exception $e) {
                    $appealDesc = "<font color='red' >".$e->getMessage()." PLEASE CORRECT THIS ISSUE.</font>";
                }catch(RNCPHP\ConnectAPIError $e) {
                    $appealDesc = "<font color='red' >".$e->getMessage()." PLEASE CORRECT THIS ISSUE.</font>";   
                }
                
            }else{
                $appealDesc =  "The appeal ID is incorrect or blank.  Please correct this issue before continuing.";
            }
            
            
            
        ?>
            <h2>Africa New Life Ministries Radiothon Donations <?=$appealCode?></h2>
            <table>
                <tr><td>$25 a month provides a month of daily nutritious meals to a child</td></tr>
                <tr><td>$150 provides six children a month of meals</td></tr>
                <tr><td>$300 provides 12 children a month of meals</td></tr>
                <tr><td>$1000 provides 40 children a month of meals</td></tr>
            </table>
            
            <div>
                <input type=button onclick="window.location='<?= BASE_SITE_URL ?>/cgi-bin/africanewlife.cfg/php/custom/PledgeDrive/entry.php?appeal=<?=$_GET["appeal"]?>'" value="New Pledge">
            </div>
            <h3>Logged in as: <?=$account -> DisplayName ?></h3>
        </div>
<? if ($appealDesc != ""){
    echo $appealDesc;
   }else{?>
        <div id="contactLookup">
            <h3>Please search and select a contact for this pledge:</h3>
            <table class="filterList">
                <tr>
                <td>
                    <label id="filterFirstLabel" class=""> First Name</label><br/>
                    <input type="text" id="filterFirst" name="filterFirst" class="rn_Text" maxlength="240">
                </td>
                <td>
                    <label id="filterLastLabel" class=""> Last Name</label><br/>
                    <input type="text" id="filterLast" name="filterLast" class="rn_Text" maxlength="240">
                </td>
                <td>
                    <label id="filterEmailLabel" class=""> Email</label><br/>
                    <input type="text" id="filterEmail" name="filterEmail" class="rn_Text" maxlength="240">
                </td>
                <td>
                    <label id="filterPhoneLabel" class=""> Phone</label><br/>
                    <input type="text" id="filterPhone" name="filterPhone" class="rn_Text" maxlength="240">
                </td>
                <td>
                    <label id="filterStreetLabel" class=""> Street</label><br/>
                    <input type="text" id="filterStreet" name="filterStreet" class="rn_Text" maxlength="240">
                </td>
                <td class="filterButton" class="filterButton">
                    <button id="filterButton" name="filterButton">Find Contact</button>
                </td>
                </tr>
            </table>
            <div id="contactListSearchError">
            </div>
            <table id="contactListTable" class="tablesorter"> 
                <thead> 
                    <tr> 
                        <th>Last Name</th> 
                        <th>First Name</th> 
                        <th>Email</th> 
                        <th>Phone</th>
                        <th>Street</th> 
                        <th>Select Contact</th> 
                    </tr> 
                </thead> 
                <tbody id="contactListBody">
                </tbody>
            </table>
        </div>
        <div id="importForm">
            <form id="importData" onsubmit="return false;">
                <div>
                    <div class="formGroup" id="yui_3_8_1_3_1469547292628_22">
                        
                        <div>
                            <label id="firstLabel" class="rn_Label"> First Name *</label>
                            <input type="text" id="first" name="first" class="rn_Text" maxlength="240">
                        </div>
                        <div>
                            <label id="lastLabel" class="rn_Label"> Last Name *</label>
                            <input type="text" id="last" name="last" class="rn_Text" maxlength="240">
                        </div>
                        <div>
                            <label id="emailLabel" class="rn_Label"> Email *</label>
                            <input type="text" id="email" name="email" class="rn_Text" maxlength="240">
                        </div>
                        <div>
                            <label id="phoneLabel" class="rn_Label"> Phone</label>
                            <input type="text" id="phone" name="phone" class="rn_Text" maxlength="240">
                        </div>
                        
                        <div>
                            <label id="streetLabel" class="rn_Label"> Street *</label>
                            <input type="text" id="street" name="street" class="rn_Text" maxlength="240">
                        </div>
                        
                        <div>
                            <label id="cityLabel" class="rn_Label">City *</label>
                            <input type="text" id="city" name="city" class="rn_Text" maxlength="80">
                        </div>
                        
                        <div>
                            <label id="countryLabel" class="rn_Label">Country *</label>
                            <select id="country" name="country">
                                <option value="">--</option>
                                <option value="1" selected>United States (US)</option>
                                <option value="2">Rwanda</option>
                                <option value="3">Canada</option>
                                <option value="4">Israel</option>
                                <option value="5">United Kingdom</option>
                            </select>
                        </div>
                        
                        <div>
                            <label id="stateLabel" class="rn_Label">State Or Province *</label>
                            <select id="state" name="state">
                                <option value="">--</option><option value="1">AK</option><option value="2">AL</option><option value="3">AR</option><option value="4">AS</option><option value="5">AZ</option><option value="6">CA</option><option value="7">CO</option><option value="8">CT</option><option value="9">DC</option><option value="10">DE</option><option value="11">FL</option><option value="12">FM</option><option value="13">GA</option><option value="14">GU</option><option value="15">HI</option><option value="16">IA</option><option value="17">ID</option><option value="18">IL</option><option value="19">IN</option><option value="20">KS</option><option value="21">KY</option><option value="22">LA</option><option value="23">MA</option><option value="24">MD</option><option value="25">ME</option><option value="26">MH</option><option value="27">MI</option><option value="28">MN</option><option value="29">MO</option><option value="30">MP</option><option value="31">MS</option><option value="32">MT</option><option value="33">NC</option><option value="34">ND</option><option value="35">NE</option><option value="36">NH</option><option value="37">NJ</option><option value="38">NM</option><option value="39">NV</option><option value="40">NY</option><option value="41">OH</option><option value="42">OK</option><option value="43">OR</option><option value="44">PA</option><option value="45">PR</option><option value="46">PW</option><option value="47">RI</option><option value="48">SC</option><option value="49">SD</option><option value="50">TN</option><option value="51">TX</option><option value="52">UT</option><option value="53">VA</option><option value="54">VI</option><option value="55">VT</option><option value="56">WA</option><option value="57">WI</option><option value="58">WV</option><option value="59">WY</option>
                            </select>
                        </div>
                        
                        <div>
                            <label id="postalLabel" class="rn_Label"> PostalCode *</label>
                            <input type="text" id="postal" name="postal" class="rn_Text" maxlength="10" >
                        </div>
                        
                        <div>
                            <label id="frequencyLabel" class="rn_Label"> Pledge Frequency *</label>
                            <input type="radio" id="frequency" name="frequency" value="1" > One Time
                            <input type="radio" id="frequency" name="frequency" value="2" > Monthly
                        </div>
                        
                        <div>
                            <label id="amountLabel" class="rn_Label"> Pledge Amount *</label>
                            <input type="text" id="amount" name="amount" class="rn_Text" maxlength="10" >
                        </div>
                        
                        <div>
                            <label id="startLabel" class="rn_Label"> Start Date *</label>
                            <input type="text" id="start" name="start" class="rn_Text" maxlength="10" value="<?=date("m/d/Y")?>" >
                        </div>
                        
                        <div>
                            <label id="stopLabel" class="rn_Label">Stop Date </label>
                            <input type="text" id="stop" name="stop" class="rn_Text" maxlength="10" >
                        </div>
                        
                        <div>
                            <label id="firstLabel" class="rn_Label"> Customer Sending in Check</label>
                            <input type="checkbox" id="check" name="check" class="rn_Text" >
                        </div>
                            <label id="emailprefLabel" class="rn_Label">Communication Preference *</label>
                            <select id="emailpref" name="emailpref">
                                <option value="">--</option>
                                <option value="16">No Mail</option>
                                <option value="14">Email</option>
                                <option value="15">Printed</option>
                            </select>
                        <div>
                        </div>
                        <div>
                            <label id="notesLabel" class="rn_Label"> Special Instructions</label>
                            <textarea id="notes" name="notes" maxlength="400" class="rn_Text" rows="10"></textarea>
                        </div>
                    </div>
                    
                </div>
                <div id="inputButton">
                    
                    <input type="submit" value="Create Pledge" id="submitBtn" />
                </div>
                
                <input type="hidden" id="appeal" name="appeal" value="<?=$_GET['appeal']?>" >
                
            </form>
        </div>
        
<?}?>
        <div id="rightContainer" class="rightContainer">
            
            
            
            <div id="newPaymentContainer" class="rn_Hidden">
                <iframe src="about:blank" name="paymentIdFrame" id="paymentIdFrame"  style="height: 680px; width: 100%; border: 1px solid;"></iframe>
                <button id="refreshPaymentWindowButton">Refresh</button>
            </div>
            
            <div id="pledgeDetails" class="pledgeDetails">
                
            </div>
            
        </div>
        <div id="scriptContainer">
            <div>
                <button id="scriptExpandCollapseToggle" class="Minimized">Maximize Tips</button>
            </div>
            <div id="searchPageTips">
                <h3>Search page:</h3>
                <ul>
                    <li>Enter first and last name, and click the ‘Find Contact’ button (‘Enter’ will not work)</li>
                    <li>If multiple accounts pull up from search, verify street address and select proper account.</li>
                    <li>If caller is not found on the search list, then enter email address (required), and click the ‘Find Contact’ button.</li>
                    <li>If caller is unwilling to provide email, explain it’ll be used for account issues only. If they are still unwilling to provide, make up a fictitious email (must be unique each time) and then make a comment in comment field.</li>
                </ul>
            </div>
            <div id="entryPageTips">
                <h3>Contact/Pledge Entry Page:</h3>
                <ul> 
                    <li>First Name: Use initial caps (e.g. Tom)</li>
                    <li>Last Name: Use initial caps (e.g. Smith)</li>
                    <li>Phone: Use periods in between, no punctuation and no brackets (e.g. 503.459.1234) – we obtain in case you’re disconnected or if there are pledge issues.</li>
                    <li>Street: Include Apt or Ste, no punctuation can be used in the address (e.g. 1234 SW Anywhere St Portland OR 97219. Not 1234 S.W. Anywhere St., Portland, OR 97219)</li>
                    <li>City: Use initial caps (e.g. Portland)</li>
                    <li>Postal Code: 5-digit Postal Code (e.g. 97223)</li>
                    <li>Pledge Amount: Use two decimal places and no dollar symbol (e.g. 25.00 not $25.00), no punctuation can be used (e.g. 1000.00 not $1,000.00))</li>
                    <li>Start Date: Only change if donor needs this to be a future date. Click in field to change.</li>
                    <li>Stop Date: Leave blank unless caller wants a short-term pledge (e.g. if wants 6 mo. Pledge= 9/1/21, 1 year Pledge= 3/1/21).</li>
                    <li>Mail Preference: If caller doesn’t want to receive communication from us, set to No Mail</li>
                    <li>Special Instructions: Enter notes and special requests here (e.g. send information on child sponsorship)</li>
                    <li>If donation declines or fails, click the Refresh button to try the pay info again</li>
                    <li>To start new pledge for new caller, click ‘New Pledge” icon top left of screen.</li>
                </ul>
            </div>
        </div>
       
    </body>
    
   
</html>




