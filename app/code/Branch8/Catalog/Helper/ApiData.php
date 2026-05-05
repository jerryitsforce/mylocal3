<?php

namespace Branch8\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ApiData extends AbstractHelper
{
    public function __construct(
        Context $context,

    ){

    }
    public function validateViewedProductParam($member_seq, $sku)
    {
        $isError = false;
        $errorMsg = [];
        if(trim($member_seq) == '' || trim($sku) == ''){
            $isError = true;
            $errorMsg[] = 'Invalid param';
        }
        //validate customer

        //validate product

    }
}