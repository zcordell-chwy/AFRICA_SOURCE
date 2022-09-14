<?php /* Originating Release: May 2015 */?> 
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Loading"></div>
    <rn:block id="postLoadingIndicator"/>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content">
        <rn:block id="topContent"/>
        <? if (is_array($this->data['reportData']['data']) && count($this->data['reportData']['data']) > 0): ?>
        <rn:block id="preResultList"/>
        
        <rn:block id="topResultList"/>
        <? foreach ($this->data['reportData']['data'] as $value): ?>
            <rn:block id="resultListItem">
            <div class="listElement">
                <div class="topContent">
                    
                    <div class="topContentLeft">
                        <div class="detailColumn">
                            <div class="detailHeader"><?=$this->data['reportData']['headers'][0]['heading']?></div>
                            <div class="detailContent"><?=$value[0];?></div>
                        </div>
                        
                        <div class="detailColumn">
                            <div class="detailHeader"><?=$this->data['reportData']['headers'][1]['heading']?></div>
                            <div class="detailContent"><?=$value[1];?></div>
                        </div>
                        
                        <div class="detailColumn">
                            <div class="detailHeader"><?=$this->data['reportData']['headers'][2]['heading']?></div>
                            <div class="detailContent"><?=$value[2];?></div>
                        </div>
                    </div>
                    <div class="topContentRight">
                        <div class="detailColumn">
                            <div class="detailContent"><?=$this->getHeader($this->data['reportData']['headers'][3]);?> #<?=$value[3];?></div>
                        </div>
                        
                        <div class="detailColumn">
                            <div class="detailContent"><?=$this->getHeader($this->data['reportData']['headers'][4]);?> : <?=$value[4];?></div>
                        </div>
                    </div>
                </div>
                
                
                <div class="bottomContent"> 
                    <div class="FundContent">
                        <img src = '<? 
                                if($value[7]){
                                    echo $photoUrl = $this-> CI -> model('custom/sponsorship_model')->getChildImg($value[7]);
                                }else{
                                    echo $photoUrl = $value[15];
                                }
                        ?>' />
                        <div class="FundDetail">
                            <ul>
                                <li><?=$info1 = ($value[6]) ? $value[6] : "Fund Code :".$value[13]?></li>
                                <li><?=$info2 = ($value[7]) ? "Student Reference Code: ".$value[7] : $value[14]?></li>
                                <li><?=$info3 = ($value[8]) ? "Gender: ".$value[8] : ""?></li>
                                <li><?=$info4 = ($value[9]) ? "Age: ".$value[9] : ""?></li>
                                <li><?=$info5 = ($value[10]) ? "Birthday: ".$value[10]."/".$value[11]."/".$value[12] : ""?></li>
                            </ul>
                        </div> 
                    </div>
                    <?
                      switch(true){
                        case $value[19] > 0:
                            $class = "currentPledge";
                            break;
                        case $value[19] <  0;
                            $class = "latePledge";
                            break;
                        default:
                            $class = "";
                            break;
                      }  
                      
                      switch($value[21]){
                        case "AMEX":
                            $payClass = "cc-amex";   
                            break;
                        case "Visa":
                            $payClass = "cc-visa";
                            break;
                        case "MasterCard":
                            $payClass = "cc-mastercard";
                            break;
                        case "Discover":
                            $payClass = "cc-discover";
                            break;
                        case "Checking":
                            $payClass = "bank-account"; 
                            break;
                      }
                    
                    ?>
                    <div class="PledgeStatus <?=$class?> ">
                        <ul>
                            <li><?=$this->getHeader($this->data['reportData']['headers'][16]);?> <?=$value[16];?></li>
                            <li><?=$this->getHeader($this->data['reportData']['headers'][18]);?> <?=$value[18];?></li>
                            <li><?=$this->getHeader($this->data['reportData']['headers'][19]);?> <?=($value[19]) ? "$ ".$value[19]: "$ 0.00";?></li>
                            <? if ($value[2] == "Active" || $value[2] == "Manual Pay" || $value[2] == "On Hold - Non Payment"){?>
                            <li class="paymethodInfo <?=$payClass?>">
                                <?=$value[22]?><?=($value[23])? "  $value[23]/$value[24]":""?>  <a id="changeMethodLink" name="changeMethodLink" href=/app/account/transactions/p_id/<?=$value[3];?>>Change...</a>
                            </li>
                            <?}?>
                        </ul>
                        <? if ($value[2] == "Active" || $value[2] == "Manual Pay" || $value[2] == "On Hold - Non Payment"){?>
                        <div class="paymentArea" >
                            <rn:widget path="custom/eventus/pledgepayment" pledgeID="#rn:php:$value[3]#" pledgeAmount="#rn:php:$value[1]#" aheadBehind="#rn:php:$value[19]#" label_button="Make a Donation" label_payment="Donate Now" />
                            
                        </div>
                        <?}?>
                        
                    </div>
                
                </div>
            </div>
            </rn:block>
        <? endforeach; ?>
        <rn:block id="bottomResultList"/>
        
        <rn:block id="postResultList"/>
        <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>
</div>
