<?php

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderComment;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;

interface NotifierInterface
{
    /**
     * @param ParentOrderInterface $parentOrder
     * @param string $comment
     * @return mixed
     */
    public function notify(
        ParentOrderInterface $parentOrder,
        string               $comment = ''
    );
}
