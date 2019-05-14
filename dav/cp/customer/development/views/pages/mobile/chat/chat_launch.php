<rn:meta title="#rn:msg:LIVE_CHAT_LBL#" template="mobile.php" clickstream="chat_request"/>

<section id="rn_PageTitle" class="rn_LiveHelp">
    <h1>#rn:msg:CHAT_WITH_OUR_SUPPORT_TEAM_LBL#</h1>
</section>
<section id="rn_PageContent" class="rn_LiveHelp">
    <div class="rn_Padding" >
        <div class="rn_ChatForm">
           
            <div id="rn_ErrorLocation"></div>
            <fieldset>
                <rn:widget path="input/ContactNameInput" required="true" />
                <rn:widget path="input/FormInput" name="contacts.email" required="true" />
            </fieldset>
            <br />
            <rn:widget path="chat/ChatLaunchButton" error_location="rn_ErrorLocation" open_in_new_window="false" />
            <br /><br />
            </form>
       </div>
       <rn:widget path="chat/ChatStatus2"/>
       <rn:widget path="chat/ChatHours2"/>
    </div>
</section>


