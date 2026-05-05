<?php

declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order;

/**
 * Class for adding applied rule IDs.
 */
class OrderItemRuleApplier implements ObserverInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * OrderItemRuleApplier constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        $quoteRuleIds = [];
        /** @var QuoteItem $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteRuleIds[$quoteItem->getData('product_id')] = $quoteItem->getAppliedRuleIds();
        }

        foreach ($order->getAllItems() as $orderItem) {
            $productId = $orderItem->getProductId();
            if ($productId && isset($quoteRuleIds[$productId])) {
                $ruleIds = $quoteRuleIds[$productId] ?: null;
                if (empty($ruleIds)) {
                    continue;
                }
                if (is_array($ruleIds)) {
                    $ruleIds = implode(',', $ruleIds);
                }
                $this->connection->update(
                    'sales_order_item',
                    ['applied_rule_ids' => $ruleIds],
                    ['item_id = ?' => $orderItem->getId()]
                );
            }
        }
    }
}
