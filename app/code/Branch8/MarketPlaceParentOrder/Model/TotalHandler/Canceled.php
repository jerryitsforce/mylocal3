<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;

class Canceled implements TotalHandlerInterface
{
    public function handle(ParentOrder $parentOrder)
    {
        $code = 'due';
        $label = 'Total Due';
        $canceled = 0;
        $baseCanceled = 0;
        $value = 0;
        $baseValue = 0;
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $value += $order->getTotalDue();
            $baseValue += $order->getBaseTotalDue();
            $canceled += $order->getTotalCanceled();
            $baseCanceled += $order->getBaseTotalCanceled();
        }
        if ($canceled > 0 && $baseCanceled > 0) {
            $code = 'canceled';
            $label = 'Total Canceled';
            $value = $canceled;
            $baseValue = $baseCanceled;
        }
        return [
            'code' => $code,
            'sort' => 400,
            'data' => new \Magento\Framework\DataObject([
                    'code' => 'due',
                    'strong' => true,
                    'value' => $value,
                    'base_value' => $baseValue,
                    'label' => __($label),
                    'area' => 'footer',
                    'sort' => 400,
                ]
            )
        ];
    }
}
