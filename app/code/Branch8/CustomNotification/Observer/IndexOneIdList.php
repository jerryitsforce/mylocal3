<?php

namespace Branch8\CustomNotification\Observer;

use Branch8\CustomNotification\Model\ConfigData;
use Branch8\CustomNotification\Model\OneId\Index;
use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;

class IndexOneIdList implements ObserverInterface
{
    private ConfigData $configData;

    private OneIdListImportHandler $oneIdListImportHandler;

    private Json $serialize;
    private Index $index;

    /**
     * @param ConfigData $configData
     * @param Json $serialize
     * @param OneIdListImportHandler $oneIdListImportHandler
     */
    public function __construct(
        ConfigData             $configData,
        Json                   $serialize,
        OneIdListImportHandler $oneIdListImportHandler,
        Index                  $index
    )
    {
        $this->serialize = $serialize;
        $this->oneIdListImportHandler = $oneIdListImportHandler;
        $this->configData = $configData;
        $this->index = $index;
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
        if (!$notification->hasDataChanges()) {
            return;
        }
        if (is_null($customerLevel)) {
            return;
        }
        $customerLevel = $this->serialize->unserialize($customerLevel);
        if (!is_array($customerLevel)) {
            $customerLevel = [$customerLevel];
        }
        if (in_array($oneIdGroup, $customerLevel)
        ) {
            $list = (string)$notification->getData('oneid_list');
            if ($list) {
                $this->index->index($notification->getId(), explode(',', $list));
            }
        } else {
            $this->index->remove($notification->getId());
        }
    }
}
