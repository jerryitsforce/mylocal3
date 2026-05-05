<?php
namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;

class Notifier implements NotifierInterface
{
    private $senders;

    /**
     * @param array $senders
     */
    public function __construct(array $senders = [])
    {
        $this->senders = $senders;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return void
     */
    public function notify(ParentOrderInterface $parentOrder)
    {
        foreach ($this->senders as $sender) {
            $sender->send($parentOrder);
        }
    }
}
