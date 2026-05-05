<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{

    const ATTRIBUTE_CODE_LIMIT_PURCHASED_ENABLE                = "limit_purchased_enable";
    const ATTRIBUTE_CODE_LIMIT_PURCHASED_CUSTOMER_GROUP        = "limit_purchased_customer_group";
    const ATTRIBUTE_CODE_LIMIT_PURCHASED_QTY                   = "limit_purchased_qty";
    const ATTRIBUTE_CODE_LIMIT_PURCHASED_START_TIME            = "limit_purchased_start_time";
    const ATTRIBUTE_CODE_LIMIT_PURCHASED_END_TIME              = "limit_purchased_end_time";

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }
}

