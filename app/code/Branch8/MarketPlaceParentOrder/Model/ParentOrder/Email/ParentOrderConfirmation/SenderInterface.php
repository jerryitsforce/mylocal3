<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderConfirmation;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

interface SenderInterface
{
    /**
     * @param ParentOrder $parentOrder
     * @return mixed
     */
    public function send(ParentOrder $parentOrder);
}
