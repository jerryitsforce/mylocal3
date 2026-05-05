<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Magento\CatalogInventory\Model\Quote\Item;

use Amasty\Groupcat\Model\ProductRuleProvider;

class QuantityValidator
{

    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductRuleProvider $ruleProvider
    )
    {
        $this->ruleProvider = $ruleProvider;
    }

    public function aroundValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        $item = $observer->getEvent()->getItem();
        if (!$item->getId() || !$item->getProduct()) return $proceed($observer);
        $ids = $this->ruleProvider->getRestrictedProductIds();
        if(in_array($item->getProduct()->getId(), $ids)){
            $item->getProduct()->setStatus(0);
        }
        return $proceed($observer);
    }
}
