<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class LogOrderStatusChange implements ObserverInterface
{

    protected $statusChangeLogHelper;

    public function __construct(
        \Branch8\Sales\Helper\StatusChangeLog $statusChangeLogHelper
    )
    {
        $this->statusChangeLogHelper = $statusChangeLogHelper;
    }

    /**
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $resourceOwner = $this->statusChangeLogHelper->detectResource();
            $dataObject = $observer->getEvent()->getData('data_object');
            $orderObj = $dataObject->getOrder();

            // If using Magento\Sales\Model\Order\Status\HistoryFactory and Magento\Sales\Api\OrderStatusHistoryRepositoryInterface to add order history comment,
            // the order object could be null.
            if ($orderObj === null) {
                return;
            }

            $statusBefore = $orderObj->getOrigData('status');
            if($statusBefore != $orderObj->getStatus()){
                $dataObject->setResourceOwner(json_encode($resourceOwner));
            }

        } catch (\Exception $exception) {

        }
    }
}
