<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use  Magento\Framework\DataObject;

class Paid implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        $total = [
            'code' => 'paid',
            'strong' => true,
            'value' => 0,
            'base_value' => 0,
            'label' => __('Total Paid'),
            'area' => 'footer',
        ];
        /**u
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getTotalPaid();
            $total['base_value'] += $order->getBaseTotalPaid();
        }
        $total['sort'] = 200;
        return ['code' => 'paid', 'sort' => 200, 'data' => new DataObject($total)];
    }

}
