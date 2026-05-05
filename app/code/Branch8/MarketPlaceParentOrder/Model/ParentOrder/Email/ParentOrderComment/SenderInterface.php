<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderComment;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

interface SenderInterface
{
    /**
     * @param ParentOrder $parentOrder
     * @param string $comment
     * @return mixed
     */
    public function send(ParentOrder $parentOrder, string $comment = '');
}
