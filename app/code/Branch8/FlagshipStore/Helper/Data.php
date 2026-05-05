<?php

namespace Branch8\FlagshipStore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const FLAGSHIP_CATEGORY_CONFIG = 'seller_flagship/general/categories';

    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);
    }
}