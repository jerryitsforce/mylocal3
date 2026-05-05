<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider;

class ReorderHelper
{
    /**
     * @var \Magento\Sales\Helper\Reorder
     */
    private \Magento\Sales\Helper\Reorder $reorderHelper;
    /**
     * @var UnavailableProductsProvider
     */
    private UnavailableProductsProvider $unavaiableProductsProvider;

    /**
     * @param \Magento\Sales\Helper\Reorder $reorderHelper
     * @param UnavailableProductsProvider $unavailableProductsProvider
     */
    public function __construct(
        \Magento\Sales\Helper\Reorder $reorderHelper,
        UnavailableProductsProvider   $unavailableProductsProvider
    )
    {
        $this->unavaiableProductsProvider = $unavailableProductsProvider;
        $this->reorderHelper = $reorderHelper;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return bool
     */
    public function canReorder(ParentOrder $parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        if (!count($subOrders)) {
            return false;
        }
        $canReorder = true;
        /**
         * @var $order Order
         */
        foreach ($subOrders as $order) {
            if (!$this->reorderHelper->canReorder($order->getId())) {
                $canReorder = false;
                break;
            }
        }
        return $canReorder;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function getUnavaiableProducts(ParentOrder $parentOrder)
    {
        $unavailableProducts = [];
        foreach ($parentOrder->getSubOrders() as $order) {
            $unavailableProducts = array_merge(
                $this->unavaiableProductsProvider->getForOrder($order),
                $unavailableProducts
            );
        }
        return $unavailableProducts;
    }
}
