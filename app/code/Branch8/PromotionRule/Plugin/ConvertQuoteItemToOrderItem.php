<?php

declare(strict_types=1);

namespace Branch8\PromotionRule\Plugin;

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
    ): OrderItemInterface
    {
        $fields = [
            'applied_catalog_rule_ids',
            'seller_borne_catalogrule_amount',
            'seller_borne_salesrule_amount',
            'seller_borne_total_amount',
            'platform_borne_catalogrule_amount',
            'platform_borne_salesrule_amount',
            'platform_borne_total_amount',
            'cart_rules_borne_info',
            'catalog_rules_borne_info',
            'sale_rule_discount_breakdown',
            'main_category',
            'main_category_name',
            'special_price',
        ];
        foreach ($fields as $key) {
            $orderItem->setData($key, $item->getData($key));
        }
        return $orderItem;
    }
}
