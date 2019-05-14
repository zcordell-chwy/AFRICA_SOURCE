<rn:meta title="#rn:msg:SHP_TITLE_HDG#" login_required="true" template="responsive.php" clickstream="payment"/>

<?
    logMessage('Began logging of payment/review');
    $total = $this -> session -> getSessionData('total');
    $totalRecurring = $this -> session -> getSessionData('totalRecurring');
    logMessage('totalRecurring = ' . var_export($totalRecurring, true));
    $items = $this -> session -> getSessionData('items');
    logMessage($items);
    $fieldsToShow = array(
        array(
            'field' => 'itemName',
            'label' => "Item",
            'type' => 'string'
        ),
        array(
            'field' => 'oneTime',
            'label' => "One Time Donation",
            'type' => 'currency'
        ),
        array(
            'field' => 'recurring',
            'label' => 'Recurring Donation',
            'type' => 'currency'
        )
    );
?>

<div class="rn_AfricaNewLifeLayoutSingleColumn">
    <div class="esg_centerContainer">
    	<h2>Transaction Summary</h2>
    	<rn:widget path="custom/eventus/checkoutSummary" />
    	<div class="esg_checkoutButton">
    		<a class="" href="/app/payment/billingContactDetails" >
    		<button>
    			Continue
    		</button></a>
    	</div>
    </div>
</div>