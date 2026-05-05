<?php

namespace Branch8\MarketPlaceParentOrder\Model\TotalHandler;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

interface TotalHandlerInterface
{
    /**
     * Return ['code','data']
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function handle(ParentOrder $parentOrder);
}
