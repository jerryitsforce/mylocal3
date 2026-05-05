<?php

namespace Branch8\PointMoneyCollect\Observer;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\ObserverInterface;

class QuoteOrderFieldObserver implements ObserverInterface
{

    /**
     * List of item attributes that should be added to order item.
     *
     * @var array
     */
    private array $itemAttributes = [
        'row_total_point_used',
        'row_total_point_discount',
    ];

    /**
     * List of order attributes that should be added to order.
     *
     * @var array
     */
    private array $orderAttributes = [
        'point_used_total',
        'point_discount_total',
        'referrer_code',
        'order_note'
    ];

    /**
     * @var Copy
     */
    protected Copy $objectCopyService;

    public function __construct(
        Copy $objectCopyService,
    ) {
        $this->objectCopyService = $objectCopyService;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');
        //$this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);

        foreach ($this->orderAttributes as $orderAttribute) {
            if ($quote->hasData($orderAttribute)) {
                $order->setData($orderAttribute, $quote->getData($orderAttribute));
            }
        }

        foreach ($quote->getAllItems() as $quoteItem) {
            $orderItem = $order->getItemByQuoteItemId($quoteItem->getId());
            if ($orderItem) {
                foreach ($this->itemAttributes as $itemAttribute) {
                    if ($quoteItem->hasData($itemAttribute)) {
                        $orderItem->setData($itemAttribute, $quoteItem->getData($itemAttribute));
                    }
                }
            }
        }

        return $this;
    }
}
