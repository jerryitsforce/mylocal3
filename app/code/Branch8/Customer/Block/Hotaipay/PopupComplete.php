<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Block\Hotaipay;

class PopupComplete extends Index
{
    public function getCompleteMsg(){
        $cards = $this->getCreditcardList();
        $params = $this->getParams();

        if(count($cards) && count($params)){
            if(isset($params['TokenID']) && isset($cards[$params['TokenID']])){
                $cardType = $cards[$params['TokenID']]['CardType'];
                $cardNumber = $cards[$params['TokenID']]['CardNoMask'];
                return __('已成功綁定一張信用卡 %1 %2, 若需要編輯/新增信用卡請至會員中心。', $cardType, $cardNumber);
            }
        }
        return __('已成功綁定一張信用卡, 若需要編輯/新增信用卡請至會員中心。');
    }
}

