<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

/**
 * Interface \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface
 * @api
 *
 */
interface OrderViewAuthorizationInterface
{
    /**
     * Check if order can be viewed by user
     *
     * @param ParentOrder $order
     * @return bool
     */
    public function canView(ParentOrder $order);
}
