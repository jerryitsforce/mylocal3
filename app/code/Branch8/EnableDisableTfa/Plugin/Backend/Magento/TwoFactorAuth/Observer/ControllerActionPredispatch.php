<?php
/**
 * Copyright © cleargo All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\EnableDisableTfa\Plugin\Backend\Magento\TwoFactorAuth\Observer;

use Magento\Framework\Event\Observer;
use Magento\TwoFactorAuth\Observer\ControllerActionPredispatch as MagentoControllerActionPredispatch;

class ControllerActionPredispatch
{
    protected $tfaHelper;

    public function __construct(
        \Branch8\EnableDisableTfa\Helper\Data $tfaHelper
    ) {
        $this->tfaHelper = $tfaHelper;
    }

    public function aroundExecute(
        MagentoControllerActionPredispatch $subject,
        callable $proceed,
        Observer $observer
    ) {
        if($this->tfaHelper->isEnabled()) {
            $proceed($observer);
        }
    }
}

