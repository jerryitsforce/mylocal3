<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;

class Refunded implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        $total = [
            'code' => 'refunded',
            'strong' => true,
            'value' => 0,
            'base_value' => 0,
            'label' => __('Total Refunded'),
            'area' => 'footer'
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getTotalRefunded();
            $total['base_value'] += $order->getBaseTotalRefunded();
        }
        $total['sort'] = 300;
        return ['code' => 'refunded', 'sort' => 300, 'data' => new DataObject($total)];
    }
}
