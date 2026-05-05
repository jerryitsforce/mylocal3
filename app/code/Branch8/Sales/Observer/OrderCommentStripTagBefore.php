<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class OrderCommentStripTagBefore implements ObserverInterface
{

    /**
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $history = $observer->getEvent()->getDataObject();
            $comment = (string)$history->getComment();
            $comment = htmlentities($comment);
            $history->setComment($comment);
            
        } catch (\Exception $exception) {
            
        }
    }
}
