<div id="rn_PageTitle" class="rn_Account">

<h2>Manage Payment Methods</h2>
<h4>*Pay Methods associated with a Recurring Pledge are not eligible for deletion.</h4>
</div>

<div class="esg_ccProcess">
    <div class="ccInputArea">
        <div id="newPaymentContainer" class="rn_Hidden">
                <iframe src="about:blank" name="paymentIdFrame" id="paymentIdFrame"  style="height: 750px; width: 678px;"></iframe>
        </div>
        <div id="existingPaymentContainer" class="esg_checkoutSummary">
            <form id='payMethodsform'  onsubmit="return false;">
                <div id="rn_ErrorLocation"></div>
                    <table class="tblDetail">
                        <tr>
                                <td class="label">Payment Type</td><td class="label">Card or Account Number</td><td class="label">Expiration Date</td><td class="data">Delete?</td>
                        </tr>
                        <?
                        foreach ($this->data['paymentMethodsArr'] as $pm) {

                            //don't print the exp month on checking account
                            $expDate = ($pm -> expMonth != "")? $pm -> expMonth . '/' . $pm -> expYear : "";

                            $disabled = (in_array($pm->ID, $this->data['disabledPayMethods'])) ? "disabled" : "" ;
                            
                            print('<tr class="'.$disabled.'">
                                <td class="label">' . $pm -> CardType . '</td>
                                <td class="label"> ************' . $pm -> lastFour . ' </td>
                                <td class="label">' . $expDate . '</td>
                                <td class="data"><input type="checkbox" value="' . $pm -> ID . '" name="paymentMethodId" '.$disabled.'/></td>
                                </tr>');
                        }
                        ?>
                    </table>
                        <?
                        foreach ($this->data['js']['postToFsVals'] as $key => $value) {
                            print('<input type="hidden" name="' . $key . '" value="' . $value . '" />');
                        }
                        ?>
                    <div class="esg_checkoutButton">  
                            <rn:widget path="custom/eventus/ajaxCustomFormSubmit" label_button="Update" on_success_url="/app/paymentmethods" error_location="rn_ErrorLocation"/>
                            <a href="javascript:void(0);" id="addPay" class="addLink">Add Payment Method...</button>
                            <img id="cancelPledge_LoadingIcon" class="rn_Hidden" alt="Loading" src="images/indicator.gif">
                            <span id="cancelPledge_StatusMessage" class="rn_Hidden">Submitting...</span>
                    </div>  
            </form>
        </div>
	</div>
</div>
