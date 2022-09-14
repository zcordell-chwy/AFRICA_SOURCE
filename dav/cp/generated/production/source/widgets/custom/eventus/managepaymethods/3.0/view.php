<a class="newPaymentLink" id="newPaymentLink" title="" href="#">
    <img alt="" src="images/newPaymentIcon.png" />
    <span class="a-letter-space"></span><span class="addCardLink">Add card or checking account</span>
</a>

<form id="newPaymentForm" class="rn_Hidden">
    <p style="color:red;"><span class="required">*</span> Required</p>
    <label for="paymenttype">Payment Type <span class="required">*</span></label>
    <select id="paymenttype" name="paymenttype">
        <option hidden disabled selected value>Select an option.</option>
        <option value="card">Credit Card</option>
        <option value="check">Check</option>
    </select>
    <div id="cardpay" class="rn_Hidden">
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
                    <?
                    foreach (range(date('Y'), intval(date('Y')) + 10) as $x) {
                        print '<option value="' . $x . '"' . ($x === $already_selected_value ? ' selected="selected"' : '') . '>' . $x . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div id="checkpay" class="rn_Hidden">
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
    <h3 id="paymentMethodError"></h3>
</form>