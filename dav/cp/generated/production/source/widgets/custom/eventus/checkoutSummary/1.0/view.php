<?
 //session_start();
//$_SESSION["PaymentSummaryMsg"] = "";
// Set Payment summary session variable here: 
$this -> CI -> load -> helper('constants'); 

//$total = $this -> CI -> session -> getSessionData('total');
//$totalRecurring = $this -> CI -> session -> getSessionData('totalRecurring');
//$items = $this -> CI -> session -> getSessionData('items');
$items = $this -> CI -> model('custom/items') -> getItemsFromCart($this->CI->session->getSessionData('sessionID'), 'checkout');  
logMessage($items);  
$fieldsToShow = array();
$availableFields = array(
    'itemName' => array(
        'label' => "Item",
        'type' => 'string',
        'sourceField' => 'itemName'
    ),
    'oneTime' => array(
        'label' => "One Time Donation",
        'type' => 'currency',
        'sourceField' => 'oneTime'
    ),
    'price' => array(
        'label' => 'Price',
        'type' => 'currency',
        'sourceField' => 'oneTime'
    ),
    'qty' => array(
        'label' => 'Quantity',
        'type' => 'string',
        'sourceField' => 'qty'
    ),
    'recurring' => array(
        'label' => 'Recurring Donation',
        'type' => 'currency',
        'sourceField' => 'recurring'
    ),
    'childName' => array(
        'label' => 'Sponsoree/Scholarshipee',
        'type' => 'string',
        'sourceField' => 'childName'
    )
);

//preparse the fields to determine what to show

$total = $this -> CI -> model('custom/items') -> getTotalDueNow($this->CI->session->getSessionData('sessionID'));
$totalRecurring = $this -> CI -> model('custom/items') -> getTotalReoccurring($this->CI->session->getSessionData('sessionID'));

foreach ($items as $item) {
    logMessage(__LINE__." - ".__CLASS__);
    logMessage($item);
    
    switch ($item['type']) {
        case DONATION_TYPE_PLEDGE :
            logMessage("Pledge Type Found");           
            $fieldsToShow['itemName'] = $availableFields['itemName'];
            if ($item['oneTime'] > 0) {
                $fieldsToShow['oneTime'] = $availableFields['oneTime'];
            }
            if ($item['recurring'] > 0) {
                $fieldsToShow['recurring'] = $availableFields['recurring'];
            }
            
            //$_SESSION["PaymentSummaryMsg"] = "DONATION_TYPE_PLEDGE";
            break;
        case DONATION_TYPE_GIFT :
            logMessage("Donation Type Found");            
            $fieldsToShow['itemName'] = $availableFields['itemName'];
            $fieldsToShow['price'] = $availableFields['price'];
            $fieldsToShow['qty'] = $availableFields['qty'];
            if (strlen($item['childName']) > 0) {
                $fieldsToShow['childName'] = $availableFields['childName'];
            }
            break;
        case DONATION_TYPE_SPONSOR :
            logMessage("Sponsor Type Found");           
            $fieldsToShow['itemName'] = $availableFields['itemName'];
            $fieldsToShow['recurring'] = $availableFields['recurring'];
            $fieldsToShow['oneTime'] = $availableFields['oneTime'];
            $fieldsToShow['childName'] = $availableFields['childName'];
            //$_SESSION["PaymentSummaryMsg"] = "DONATION_TYPE_SPONSOR";
            break;
        
    }
}
logMessage($fieldsToShow);
?>

<div class="esg_checkoutSummary">
	<table>
		<?
        print('<tr>');
        foreach ($fieldsToShow as $fieldName => $fieldDetail) {
            print('<th>');
            printf('%s', $fieldDetail['label']);
            print('</th>');

        }
        print('</tr>');
        foreach ($items as $item) {
            print('<tr>');
            foreach ($fieldsToShow as $fieldName => $fieldDetail) {
                print('<td>');
                if (isset($item[$fieldDetail['sourceField']])) {
                    if ($fieldDetail['type'] == 'currency') {
                        printf("$%01.2f", $item[$fieldDetail['sourceField']]);
                    } else {
                        printf('%s', $item[$fieldDetail['sourceField']]);
                    }
                }
                print('</td>');
            }
            print('</tr>');
        }
		?>
	</table>
</div>
<?if($total != 0){
?>
<div class="esg_totalCharge">
	Total Charge Now: <?printf("$%01.2f", $total); ?>
</div>
<?} ?>
<?if($totalRecurring != 0){
?>
<div class="esg_totalCharge">
	Total Charge Recurring: <?printf("$%01.2f", $totalRecurring); ?>
</div>
<?} ?>
