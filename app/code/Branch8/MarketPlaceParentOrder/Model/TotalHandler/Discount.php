<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;

class Discount implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        if ($parentOrder->getDetail()->getDiscountDescription()) {
            $discountLabel = __('Discount (%1)', $parentOrder->getDetail()->getDiscountDescription());
        } else {
            $discountLabel = __('Discount');
        }
        $total = [
            'code' => 'discount',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'label' => $discountLabel,
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getDiscountAmount();
            $total['base_value'] += $order->getBaseDiscountAmount();
        }
        $total['sort'] = 50;
        return ['code' => 'discount', 'sort' => 20, 'data' => new DataObject($total)];
    }
}
