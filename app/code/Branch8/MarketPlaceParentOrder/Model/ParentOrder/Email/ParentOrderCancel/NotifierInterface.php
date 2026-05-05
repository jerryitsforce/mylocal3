<?php
namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;

interface NotifierInterface
{
    /**
     * @param ParentOrderInterface $parentOrder
     * @return mixed
     */
    public function notify(ParentOrderInterface $parentOrder);
}
