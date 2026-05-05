<?php

declare(strict_types=1);

namespace Branch8\PromotionRule\Observer;

use Branch8\PromotionRule\Model\Actions\GetCurrentAffectCatalogRuleForProducts;
use \Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 *
 */
class AddCatalogRuleIds implements ObserverInterface
{
    private GetCurrentAffectCatalogRuleForProducts $actions;

    /**
     * @param GetCurrentAffectCatalogRuleForProducts $action
     */
    public function __construct(
        GetCurrentAffectCatalogRuleForProducts $action
    )
    {
        $this->actions = $action;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /**
         * @var $item \Magento\Quote\Model\Quote\Item
         */
        $item = $observer->getEvent()->getData('quote_item');
        $quote= $item->getQuote();
        if ($quote) {
            $appliedRuleIds = $this->actions->get($item, $item->getQuote()->getStore(), $item->getQuote()->getCustomerGroupId());
            $item->setData('applied_catalog_rule_ids', $appliedRuleIds ? join(',', $appliedRuleIds) : null);
        }
    }
}
