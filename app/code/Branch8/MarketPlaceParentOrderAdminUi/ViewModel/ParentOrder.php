<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\ViewModel;

use Branch8\MarketPlaceParentOrder\Model\Services\ParentOrderFinder;
use Magento\Sales\Model\Order;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class ParentOrder implements ArgumentInterface
{
    private ParentOrderFinder $parentOrderFinder;

    /**
     * @param ParentOrderFinder $parentOrderFinder
     */
    public function __construct(
        ParentOrderFinder $parentOrderFinder
    )
    {
        $this->parentOrderFinder = $parentOrderFinder;
    }

    /**
     * @param Order $order
     * @return \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
     */
    public function find(Order $order)
    {
        return $this->parentOrderFinder->find($order);
    }
}
