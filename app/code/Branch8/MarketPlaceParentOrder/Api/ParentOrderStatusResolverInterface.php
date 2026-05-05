<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Api;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
interface ParentOrderStatusResolverInterface
{

    /**
     * @param ParentOrderInterface $parentOrder
     * @return ParentOrderInterface
     * @throw \Exception
     */
    public function resolve(ParentOrderInterface $parentOrder);
}
