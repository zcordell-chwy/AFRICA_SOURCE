<?
$CI = & get_instance(); 
?>
<div class="accountSubNav">
        <ul>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Donation History" link="/app/account/overview/c_id/#rn:php:$this->data['contactId']#" pages="account/overview "/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Pledges" link="/app/account/pledges" pages="account/pledges "/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Payment Methods" link="/app/account/transactions" pages="account/transactions"/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Receipts" link="/app/account/communications" pages="account/communications"/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="My Profile" link="/app/account/profile" pages="account/profile"/>
            </li>
            <li>
                <?  
                    $this->data['children'] = $this->CI->model('custom/sponsor_model')->getSponsoredChildren($CI->session->getProfileData('contactID'));
                    // > 1 due to needy child alway exists.
                    if (count($this->data['children']) > 1){
                ?>
                    <rn:widget path="navigation/NavigationTab2" label_tab="Online Letter Writing" link="/app/account/letters/pledge/#rn:php:$this->data['children'][0]->PledgeId#" pages="account/letters, account/letters_detail"/>
                <?}?>
            </li>            
        </ul>
    </div>
