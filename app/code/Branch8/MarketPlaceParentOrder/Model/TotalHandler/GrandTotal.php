<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;

class GrandTotal implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        $total = [
            'code' => 'grand_total',
            'strong' => true,
            'value' => 0,
            'base_value' => 0,
            'label' => __('Grand Total'),
            'area' => 'footer'
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getGrandTotal();
            $total['base_value'] += $order->getBaseGrandTotal();
        }
        $total['sort'] = 100;
        return ['code' => 'grand_total', 'sort' => 100, 'data' => new DataObject($total)];
    }
}
