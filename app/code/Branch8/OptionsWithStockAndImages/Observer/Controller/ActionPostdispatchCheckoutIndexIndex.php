<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Observer\Controller;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class ActionPostdispatchCheckoutIndexIndex implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
 
    /**
     * @param \Magento\Framework\Registry $registry,
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->registry->register('reload_cart', false);
    }
}