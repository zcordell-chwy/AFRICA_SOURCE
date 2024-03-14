<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?> rn_DonatePaymentProcess">
   <div>
      <rn:condition logged_in="true">
         <table>
            <tr>
               <th>Payment Type</th>
               <th>Card or Account Number</th>
               <th>Expiration Date</th>
               <th>Select One</th>
            </tr>
            <?
            foreach ($this->data['paymentMethodsArr'] as $pm) :

               //don't print the exp month on checking account
               $expDate = ($pm->expMonth != "") ? $pm->expMonth . '/' . $pm->expYear : "";

               if (count($this->data['paymentMethodsArr']) > 1) :
            ?>
                  <tr onclick="callme(this)">
                     <td><?= $pm->CardType ?> </td>                     
                     <td><?= $pm->lastFour ?> </td>
                     <td><?= $expDate ?> </td>
                     <td><input type="radio" value="<?= $pm->ID ?> " name="paymentMethodId" id="paymentMethodId"/></td>
                  </tr>
               <? else : ?>
                  <tr onclick="callme(this)">
                     <td><?= $pm->CardType ?> </td>
                     <td><?= $pm->lastFour ?> </td>
                     <td><?= $expDate ?> </td>
                     <td><input type="radio" value="<?= $pm->ID ?>" name="paymentMethodId" id="paymentMethodId"  /></td>
                  </tr>
            <?
               endif;
            endforeach;
            ?>
         </table> 
         <div id='cardpay2' class="rn_Hidden">
         <label for="cvnum2">Card verification number (CVN) <span class="required">*</span></label>
         <input type="text" id="cvnum2" name="cvnumber2" placeholder="" style="width: 80px!important;" >
         </div>
      </rn:condition>

      <? //foreach ($this->data['js']['postToFsVals'] as $key => $value) : ?>
         <!-- <input type="hidden" name="<?= $key ?>" value="<?= $value ?>" /> -->
      <? //endforeach; ?>
   </div>

   <rn:condition logged_in="true">
      <div class="paymentForm" id="paymentFormLink"><span><label>Expand New Payment Method Form ></label></span></div>
   </rn:condition>
   <rn:condition logged_in="false">
        <a id="rn_LoginLink_payment">Sign in for faster checkout ></a>
        <rn:widget path="login/CustomLoginDialog" trigger_element="rn_LoginLink_payment,rn_LoginLink" sub:input_Contact.Emails.PRIMARY.Address:label_input="#rn:msg:EMAIL_ADDR_LBL#" sub:input_Contact.Login:label_input="#rn:msg:USERNAME_LBL#" sub:input_Contact.NewPassword:label_input="#rn:msg:PASSWORD_LBL#" sub:input_Contact.Phones.HOME.Number:label_input="#rn:msg:PHONE_NUMBER_LBL#" sub:input_Contact.Name.First:label_input="#rn:msg:FIRST_NAME_LBL#"
sub:input_Contact.Name.Last:label_input="#rn:msg:LAST_NAME_LBL#" sub:input_Contact.Address.StateOrProvince:label_input="#rn:msg:STATE_PROV_LBL#" sub:input_Contact.Address.PostalCode:label_input="#rn:msg:POSTAL_CODE_LBL#" create_account_fields="Contact.Emails.PRIMARY.Address;Contact.Login;Contact.NewPassword;Contact.Address.Street;Contact.Address.City;Contact.Address.Country;Contact.Address.StateOrProvince;Contact.Address.PostalCode;Contact.Name.First;Contact.Name.Last;Contact.Phones.HOME.Number;Contact.CustomFields.CO.how_did_you_hear;Contact.CustomFields.c.contacttype;Contact.CustomFields.c.anonymous" open_login_providers="">
   </rn:condition>
   
   <rn:condition logged_in="true">
      <div class="tabs rn_Hidden" id="newPaymentForm" >
   <rn:condition_else />
      <div class="tabs" id="newPaymentForm" >
   </rn:condition> 
      <input type="radio" id="cardpay_radio" name="paymenttype" class="cardpay" checked>
      <label for="cardpay_radio" class="cardpay radio tablinks">Credit Card</label>
      <input type="radio" id="checkpay_radio" name="paymenttype" class="checkpay">
      <label for="checkpay_radio" class="checkpay radio tablinks">Bank Withdrawal</label>
      <div id="cardpay" class="tab_content cardpay">
         <label>Accepted Cards</label>
         <div class="icon-container">
            <i class="fa fa-cc-visa" style="color:navy;"></i>
            <i class="fa fa-cc-amex" style="color:blue;"></i>
            <i class="fa fa-cc-mastercard" style="color:red;"></i>
            <i class="fa fa-cc-discover" style="color:orange;"></i>
         </div>
         <label for="ccname">Name on Card <span class="required">*</span></label>
         <input type="text" id="ccname" name="cardname" placeholder="John More Doe">
         <label for="ccnum">Credit Card Number <span class="required">*</span></label>
         <input type="text" id="ccnum" name="cardnumber" placeholder="1111222233334444">
         <div class="row">
            <div class="col-50">
               <label for="expmonth">Exp Month <span class="required">*</span></label>
               <select id="expmonth" name="expmonth">
                  <option hidden disabled selected value>Month <span class="required">*</span></option>
                  <option value="01">January</option>
                  <option value="02">Febuary</option>
                  <option value="03">March</option>
                  <option value="04">April</option>
                  <option value="05">May</option>
                  <option value="06">June</option>
                  <option value="07">July</option>
                  <option value="08">August</option>
                  <option value="09">September</option>
                  <option value="10">October</option>
                  <option value="11">November</option>
                  <option value="12">December</option>
               </select>
            </div>
            <div class="col-50">
               <label for="expyear">Exp Year <span class="required">*</span></label>
               <select id="expyear" name="expyear">
                  <option hidden disabled selected value>Year</option>
                  <? foreach (range(date('Y'), intval(date('Y')) + 10) as $x) : ?>
                     <option value="<?= $x ?>" <?= ($x === $already_selected_value ? 'selected="selected"' : '') ?>><?= $x ?></option>
                  <? endforeach; ?>
               </select>
            </div>
         </div>
         <label for="cvnum">Card verification number (CVN) <span class="required">*</span></label>
         <input type="text" id="cvnum" name="cvnumber" placeholder="" style="width: 80px!important;" >
      </div>
      <div id="checkpay" class="tab_content checkpay">
         <label for="ckname">Name on Check <span class="required">*</span></label>
         <input type="text" id="ckname" name="checkname" placeholder="John More Doe">
         <label for="anum">Account Number <span class="required">*</span></label>
         <input type="text" id="anum" name="accountnumber" placeholder="1001001234">
         <label for="rnum">Routing Number <span class="required">*</span></label>
         <input type="text" id="rnum" name="routingnumber" placeholder="012345678">
         <label for="accounttype">Account Type <span class="required">*</span></label>
         <select id="accounttype" name="accounttype">
            <option hidden disabled selected value>Select an option.</option>
            <option value="CHECKING">Checking</option>
            <option value="SAVINGS">Savings</option>
         </select>
      </div>
   </div>

   <h3 id="paymentMethodError"></h3>

   <!-- <div class="tab">
            <button class="tablinks" id="Card">Credit Card</button>
            <button class="tablinks" id="Bank">Bank Withdrawal</button>
         </div>
         
         <div id="cardpay" class="tabcontent">
            
            <div>
               <h3>Credit Card Info</h3>
               <label>Accepted Cards</label>
               <div class="icon-container">
                  <i class="fa fa-cc-visa" style="color:navy;"></i>
                  <i class="fa fa-cc-amex" style="color:blue;"></i>
                  <i class="fa fa-cc-mastercard" style="color:red;"></i>
                  <i class="fa fa-cc-discover" style="color:orange;"></i>
               </div>
               <label for="ccname">Name on Card <span class="required">*</span></label>
               <input type="text" id="ccname" name="cardname" placeholder="John More Doe">
               <label for="ccnum">Credit Card Number <span class="required">*</span></label>
               <input type="text" id="ccnum" name="cardnumber" placeholder="1111222233334444">
               <div class="row">
                  <div>
                     <label for="expmonth">Exp Month <span class="required">*</span></label>
                     <select id="expmonth" name="expmonth">
                        <option hidden disabled selected value>Month <span class="required">*</span></option>
                        <option value="01">January</option>
                        <option value="02">Febuary</option>
                  <option value="03">March</option>
                  <option value="04">April</option>
                  <option value="05">May</option>
                  <option value="06">June</option>
                  <option value="07">July</option>
                  <option value="08">August</option>
                  <option value="09">September</option>
                  <option value="10">October</option>
                  <option value="11">November</option>
                  <option value="12">December</option>
               </select>
            </div>
            <div>
               <label for="expyear">Exp Year <span class="required">*</span></label>
               <select id="expyear" name="expyear">
                  <option hidden disabled selected value>Year</option>
                  <? foreach (range(date('Y'), intval(date('Y')) + 10) as $x) : ?>
                     <option value="<?= $x ?>" <?= ($x === $already_selected_value ? 'selected="selected"' : '') ?>><?= $x ?></option>';
                  <? endforeach; ?>
               </select>
            </div>
         </div>
      </div>
   </div>
   <div id="checkpay" class="tabcontent" class="rn_Hidden">
      <div>
         <h3>Bank Details</h3>
         <label for="ckname">Name on Check <span class="required">*</span></label>
         <input type="text" id="ckname" name="checkname" placeholder="John More Doe">
         <label for="anum">Account Number <span class="required">*</span></label>
         <input type="text" id="anum" name="accountnumber" placeholder="1001001234">
         <label for="rnum">Routing Number <span class="required">*</span></label>
         <input type="text" id="rnum" name="routingnumber" placeholder="012345678">
         <label for="accounttype">Account Type <span class="required">*</span></label>
         <select id="accounttype" name="accounttype">
            <option hidden disabled selected value>Select an option.</option>
            <option value="CHECKING">Checking</option>
            <option value="SAVINGS">Savings</option>
         </select>
      </div>
   </div> -->

</div>