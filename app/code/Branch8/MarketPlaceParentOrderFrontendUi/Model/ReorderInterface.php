<?php

namespace Branch8\MarketPlaceParentOrderFrontendUi\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

interface ReorderInterface
{
    /**
     * @param ParentOrderInterface $parentOrder
     * @return mixed
     */
    public function execute(ParentOrderInterface $parentOrder);
}
