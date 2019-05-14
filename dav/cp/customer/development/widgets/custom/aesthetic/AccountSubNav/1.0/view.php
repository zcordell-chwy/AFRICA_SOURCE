<div class="accountSubNav">
        <ul>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Overview" link="/app/account/overview/c_id/#rn:php:$this->data['contactId']#" pages="account/overview "/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Pledge Details" link="/app/account/pledges/c_id/#rn:php:$this->data['contactId']#" pages="account/pledges "/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Payment Methods" link="/app/account/transactions/c_id/#rn:php:$this->data['contactId']#" pages="account/transactions"/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="Communications" link="/app/account/communications/c_id/#rn:php:$this->data['contactId']#" pages="account/communications"/>
            </li>
            <li>
                <rn:widget path="navigation/NavigationTab2" label_tab="My Profile" link="/app/account/profile/c_id/#rn:php:$this->data['contactId']#" pages="account/profile"/>
            </li>
            <li>
                <?  
                    $this->data['children'] = $this->CI->model('custom/sponsor_model')->getSponsoredChildren(getUrlParm('c_id'));
                    // > 1 due to needy child alway exists.
                    if (count($this->data['children']) > 1){
                ?>
                    <rn:widget path="navigation/NavigationTab2" label_tab="Online Letter Writing" link="/app/account/letters/c_id/#rn:php:$this->data['contactId']#/pledge/#rn:php:$this->data['children'][0]->PledgeId#" pages="account/letters, account/letters_detail"/>
                <?}?>
            </li>
            
        </ul>
    </div>
