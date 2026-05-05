<?php

namespace Branch8\CustomNotification\Observer;

use Branch8\CustomNotification\Model\ConfigData;
use Branch8\CustomNotification\Model\DeleteImportHistoryByNotificationId;
use Branch8\CustomNotification\Model\OneIdImportHistoryFactory;
use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;

class DeleteAfter implements ObserverInterface
{
    private $deleteImportHistoryByNotificationId;

    /**
     * @param OneIdImportHistoryFactory $oneIdImportHistoryFactory
     */
    public function __construct(
        DeleteImportHistoryByNotificationId $deleteImportHistoryByNotificationId,
    )
    {
        $this->deleteImportHistoryByNotificationId = $deleteImportHistoryByNotificationId;

    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $notification Notification
         */
        $notification = $observer->getData('data_object');
        if ($notification->getData('oneid_import_history')) {
            $this->deleteImportHistoryByNotificationId->execute($notification->getId());
        }
    }
}
