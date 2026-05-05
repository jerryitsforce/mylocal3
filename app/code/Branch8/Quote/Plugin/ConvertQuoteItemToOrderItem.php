<?php

declare(strict_types=1);

namespace Branch8\Quote\Plugin;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class convert quote item data to order item data.
 */
class ConvertQuoteItemToOrderItem
{
    /**
     * Convert quote item data to order item data.
     *
     * @param ToOrderItem $subject
     * @param OrderItemInterface $orderItem
     * @param AbstractItem $item
     * @param array $data
     *
     * @return OrderItemInterface
     */
    public function afterConvert(
        ToOrderItem        $subject,
        OrderItemInterface $orderItem,
        AbstractItem       $item,
        array              $data = []
    ): OrderItemInterface {
        $orderItem->setData('applied_rule_names', $item->getData('applied_rule_names'));
        $orderItem->setData('current_product_row_id', $item->getData('current_product_row_id'));
        $orderItem->setData('schedule_change_special_price', $item->getData('schedule_change_special_price'));
        $orderItem->setData('schedule_change_special_price_start', $item->getData('schedule_change_special_price_start'));
        $orderItem->setData('schedule_change_special_price_end', $item->getData('schedule_change_special_price_end'));
        return $orderItem;
    }
}
