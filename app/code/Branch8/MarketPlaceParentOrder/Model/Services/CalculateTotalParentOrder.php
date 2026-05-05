<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\TotalHandler\TotalHandlerInterface;

class CalculateTotalParentOrder
{
    /**
     * @var \Magento\Tax\Helper\Data
     */
    private $taxHelper;
    /**
     * @var array
     */
    private $totalByIndex = [];
    private array $totalHandlers;

    /**
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param array $totalHandlers
     */
    public function __construct(
        \Magento\Tax\Helper\Data $taxHelper,
        array                    $totalHandlers
    )
    {
        $this->taxHelper = $taxHelper;
        $this->totalHandlers = $totalHandlers;
    }

    public function shipping(ParentOrder $parentOrder)
    {
        $isShown = $this->taxHelper->displayShippingPriceIncludingTax() || $this->taxHelper->displayShippingBothPrices();

        $amount = [
            'excl' => 0,
            'incl' => 0,
            'baseShippingIncludeTax' => 0,
            'baseShippingAmount' => 0,
            'shippingAmount' => 0,
            'isDisplayIncludeTax' => (bool)$isShown
        ];
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($parentOrder->getSubOrders() as $order) {
            $amount['excl'] += $order->getShippingAmount();
            $amount['incl'] += $order->getShippingInclTax();
            $amount['baseShippingIncludeTax'] += $order->getBaseShippingInclTax();
            $amount['baseShippingAmount'] += $order->getBaseShippingAmount();
            $amount['shippingAmount'] += $order->getShippingAmount();
        }

        return $amount;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array|mixed
     */
    public function total(ParentOrder $parentOrder)
    {
        if (isset($this->totalByIndex[$parentOrder->getIndexId()])) {
            return $this->totalByIndex[$parentOrder->getIndexId()];
        }
        $totals = [];
        /**
         * @var $handler TotalHandlerInterface
         */
        foreach ($this->totalHandlers as $handler) {
            $total = $handler->handle($parentOrder);
            $totals[$total['code']] = $total['data'];
        }
        $this->totalByIndex[$parentOrder->getIndexId()] = $totals;
        return $this->totalByIndex[$parentOrder->getIndexId()];
    }
}
