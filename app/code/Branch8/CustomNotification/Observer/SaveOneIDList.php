<?php

namespace Branch8\CustomNotification\Observer;

use Branch8\CustomNotification\Model\ConfigData;
use Branch8\CustomNotification\Model\OneIdImportHistoryFactory;
use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;

class SaveOneIDList implements ObserverInterface
{
    private ConfigData $configData;

    private OneIdListImportHandler $oneIdListImportHandler;

    private Json $serialize;
    private OneIdImportHistoryFactory $oneIdImportHistoryFactory;

    /**
     * @param ConfigData $configData
     * @param Json $serialize
     * @param OneIdListImportHandler $oneIdListImportHandler
     * @param OneIdImportHistoryFactory $oneIdImportHistoryFactory
     */
    public function __construct(
        ConfigData                $configData,
        Json                      $serialize,
        OneIdListImportHandler    $oneIdListImportHandler,
        OneIdImportHistoryFactory $oneIdImportHistoryFactory
    )
    {
        $this->oneIdImportHistoryFactory = $oneIdImportHistoryFactory;
        $this->serialize = $serialize;
        $this->oneIdListImportHandler = $oneIdListImportHandler;
        $this->configData = $configData;
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
        $oneIdGroup = $this->configData->getOneIdGroup();
        $customerLevel = $notification->getData('customer_levels');
        $customerLevel = $this->serialize->unserialize($customerLevel);
        $history = $this->oneIdImportHistoryFactory->create()->load(
            $notification->getData('oneid_import_history')
        );
        if (!is_array($customerLevel)) {
            $customerLevel = [$customerLevel];
        }
        if (!in_array($oneIdGroup, $customerLevel)
        ) {
            $notification->setData('oneid_list', '');
            $notification->setData('oneid_import_history', '');
            return;
        }
        if (empty($history->getId() || !$history->getData('oneid_list'))) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No oneID list available'));
        }
        if ($notification->isObjectNew()) {
            $notification->setData('oneid_list', $history->getData('oneid_list'));
            return;
        }
        if ($notification->getData('oneid_import_history') !== $notification->getOrigData('oneid_import_history')) {
            $notification->setData('oneid_list', $history->getData('oneid_list'));
        }
    }
}
