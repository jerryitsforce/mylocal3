<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use  Magento\Framework\DataObject;

class Tax implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        $total = [
            'code' => 'tax',
            'strong' => false,
            'value' => 0,
            'base_value' => 0,
            'label' => __('Tax'),
            'area' => '',
            'not_show' => true
        ];
        /**u
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $total['value'] += $order->getTaxAmount();
            $total['base_value'] += $order->getBaseTaxAmount();
        }
        $total['sort'] = 60;
        return ['code' => 'tax', 'sort' => 6, 'data' => new DataObject($total)];
    }

}
