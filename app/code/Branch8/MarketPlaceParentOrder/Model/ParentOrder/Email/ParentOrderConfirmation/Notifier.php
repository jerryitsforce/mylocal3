<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderConfirmation;

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
    public function notify(
        ParentOrderInterface $parentOrder
    )
    {
        /**
         * @var $sender SenderInterface
         */
        foreach ($this->senders as $sender) {
            $sender->send($parentOrder);
        }
    }
}
