<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;

class CustomDiscount implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        if ($parentOrder->getDetail()->getDiscountDescription()) {
            $discountLabel = __('Discount (%1)', $parentOrder->getDetail()->getDiscountDescription());
        } else {
            $discountLabel = __('Discount');
        }
        $total = [
            'code' => 'custom_discount',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'label' => $discountLabel,
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getCustomDiscount();
            $total['base_value'] += $order->getBaseCustomDiscount();
        }
        $total['value'] = -$total['value'];
        $total['base_value'] = -$total['base_value'];
        $total['sort'] = 30;
        return ['code' => 'custom_discount', 'sort' => 30, 'data' => new DataObject($total)];
    }
}
