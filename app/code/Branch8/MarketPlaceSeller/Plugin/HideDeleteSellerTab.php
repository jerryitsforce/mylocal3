<?php

namespace Branch8\MarketPlaceSeller\Plugin;

class HideDeleteSellerTab
{

    public function afterCanShowTab($subject, $result){
        //hide tab delete seller
        return false;
    }
}