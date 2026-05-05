<?php

namespace Branch8\CustomNotification\Model;

use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\CustomerToken as CustomerTokenModel;
use Magenest\NotificationBox\Model\CustomerTokenFactory;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magenest\NotificationBox\Model\NotificationFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\Notification;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollection;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue\Collection as NotificationQueueCollection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTime;
use Magento\Reports\Model\ResourceModel\Quote\CollectionFactory as AbandonedCart;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManage;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
class Cron extends \Magenest\NotificationBox\Model\Cron
{
    private PersonalNotificationTypes $personalNotificationTypes;

    /**
     * @param Helper $helper
     * @param AbandonedCart $abandonedCart
     * @param StoreManage $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param NotificationCollection $notificationCollection
     * @param Json $serialize
     * @param DateTime $dateTime
     * @param CustomerNotificationFactory $customerNotificationFactory
     * @param CollectionFactory $customerTokenCollection
     * @param CustomerToken $customerTokenResource
     * @param CustomerTokenFactory $customerTokenFactory
     * @param CustomerNotification $customerNotificationResource
     * @param NotificationFactory $notificationFactory
     * @param Notification $notificationResource
     * @param NotificationQueueCollection $notificationQueueCollection
     * @param NotificationQueueFactory $notificationQueueFactory
     * @param NotificationQueue $notificationQueue
     * @param ManagerInterface $managerInterface
     * @param StoreRepositoryInterface $storeRepositoryInterface
     * @param PersonalNotificationTypes $personalNotificationTypes
     */
    public function __construct(
        Helper                      $helper,
        AbandonedCart               $abandonedCart,
        StoreManage                 $storeManager,
        \Psr\Log\LoggerInterface    $logger,
        NotificationCollection      $notificationCollection,
        Json                        $serialize,
        DateTime                    $dateTime,
        CustomerNotificationFactory $customerNotificationFactory,
        CollectionFactory           $customerTokenCollection,
        CustomerToken               $customerTokenResource,
        CustomerTokenFactory        $customerTokenFactory,
        CustomerNotification        $customerNotificationResource,
        NotificationFactory         $notificationFactory,
        Notification                $notificationResource,
        NotificationQueueCollection $notificationQueueCollection,
        NotificationQueueFactory    $notificationQueueFactory,
        NotificationQueue           $notificationQueue,
        ManagerInterface            $managerInterface,
        StoreRepositoryInterface    $storeRepositoryInterface,
        PersonalNotificationTypes   $personalNotificationTypes,
    )
    {
        parent::__construct(
            $helper,
            $abandonedCart,
            $storeManager,
            $logger,
            $notificationCollection,
            $serialize,
            $dateTime,
            $customerNotificationFactory,
            $customerTokenCollection,
            $customerTokenResource,
            $customerTokenFactory,
            $customerNotificationResource,
            $notificationFactory,
            $notificationResource,
            $notificationQueueCollection,
            $notificationQueueFactory,
            $notificationQueue,
            $managerInterface,
            $storeRepositoryInterface,
        );
        $this->personalNotificationTypes = $personalNotificationTypes;
    }
    /**
     * send scheduled and queue announcements to customers and guests
     */
    public function sendNotification()
    {
        if (!$this->helper->getEnableModule()) {
            return;
        }
        $notificationSent = [];
        $notificationQueue = [];
        $listNotificationType = $this->personalNotificationTypes->getList();
        //get all the notices to send
        $collection = $this->notificationCollection->create()
            ->addFieldToFilter('notification_type', array('nin' => $listNotificationType))
            ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
            ->addFieldToFilter('is_sent', NotificationModel::IS_NOT_SENT)
            ->addFieldToFilter('send_time', ['neq' => 'send_immediately']);
        $listNotification = $collection->getData();
        $collectionQueque = $this->notificationQueueCollection->addFieldToFilter(
            'is_sent',
            NotificationModel::IS_NOT_SENT
        );
        $listNotificationQueue = $collectionQueque->getData();

        $allCustomer = $this->helper->getAllCustomer();

        $now = $this->dateTime->gmtDate();

        //Send custom notice
        foreach ($listNotification as $notification) {
            $notificationSent[] = $this->sendNotificationViaMagentoAndFireBase($notification, $now, $allCustomer);
        }

        //Send queue notice
        foreach ($listNotificationQueue as $notification) {
            $notificationQueue[] = $this->sendNotificationViaMagentoAndFireBase($notification, $now, $notification['customer_id']);
        }

        //only send once
        if (count($notificationSent)) {
            foreach ($notificationSent as $item) {
                $notificationModel = $this->notificationFactory->create();
                $this->notificationResource->load($notificationModel, $item);
                if (count($notificationModel->getData()) > 0) {
                    if ($notificationModel->getIsSent() == NotificationModel::IS_NOT_SENT) {
                        $notificationModel->setData('is_sent', NotificationModel::IS_SENT);
                        try {
                            $this->helper->sendNotificationInMagento($notificationModel->getData());
                            $this->notificationResource->save($notificationModel);
                        } catch (\Exception $e) {
                            $this->logger->error($e->getMessage());
                        }
                    }
                }
            }
        }
        if (count($notificationQueue)) {
            foreach ($notificationQueue as $item) {
                if (isset($item)) {
                    $notificationQueueModel = $this->notificationQueueFactory->create();
                    $this->notificationQueue->load($notificationQueueModel, $item, 'id');
                    try {
                        $this->notificationQueue->delete($notificationQueueModel);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * send notification via Firebase and Magento
     * return list notification id sent
     * @param $notification
     * @param $now
     * @param $allCustomer
     * @return array
     * @throws AlreadyExistsException
     */
    private function sendNotificationViaMagentoAndFireBase($notification, $now, $allCustomer)
    {
        $id = null;
        try {
            //Send notifications to tokens that satisfy the condition via firebase
            $timeToSend = $this->getTimeToSendNotification($notification);
            if (isset($timeToSend) && $now >= $timeToSend) {
                if (is_string($allCustomer)) {
                    //send notice via magento
                    $this->saveCustomerNotification($notification, $notification['customer_id']);
                    //send notice via firebase
                    $tokens = ($notification['token']) ? $notification['token'] : $this->helper->getToken($notification);
                    $tokenSent = [];
                    foreach ($tokens as $token) {
                        $currentToken = ['token' => $token->getToken(), 'id' => $token->getGuestId()];
                        if (!in_array($currentToken, $tokenSent)) {
                            $this->helper->sendNotificationWithFireBase($notification, $token);
                        }
                        $tokenSent[] = $currentToken;
                    }
                    $id = $notification['id'];
                } elseif (isset($allCustomer)) {
                    //send to guest
                    $this->helper->sendNotificationWithFireBase($notification);
                    $id = $notification['id'];
                } else {
                    if (isset($notification['token'])) {
                        $customerToken = $this->customerTokenCollection->create()
                            ->addFieldToFilter('token', $notification['token'])
                            ->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED)
                            ->addFieldToFilter('is_active', CustomerTokenModel::IS_ACTIVE);
                        $this->helper->sendNotificationWithFireBase($notification, $customerToken->getFirstItem());
                        $id = $notification['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->managerInterface->addErrorMessage($e->getMessage());
        }
        return $id;
    }


    /**
     * get time to send from notification
     * return date time
     * @param $notification
     * @return false|string
     */
    private function getTimeToSendNotification($notification)
    {
        if ($notification['send_time'] == 'schedule_time' && isset($notification['schedule'])) {
            $timeToSend = $notification['schedule'];
            $timeToSend = date("Y-m-d H:i:s", strtotime($timeToSend));
        } elseif ($notification['send_time'] == 'send_after_the_trigger_condition') {
            $scheduleTo = $this->serialize->unserialize($notification['schedule']);
            $sendAfter = $scheduleTo['send_after'];
            $unit = $scheduleTo['unit'];
            $timeToSend = date('Y-m-d H:i', strtotime('+' . $sendAfter . $unit, strtotime($notification['update_at'])));
        }
        return $timeToSend;
    }

    public function sendNotificationAfterSave()
    {
        if (!$this->helper->getEnableModule()) {
            return;
        }
        $listNotificationType = $this->personalNotificationTypes->getList();
        $listNotification = $this->notificationCollection->create()
            ->addFieldToFilter('notification_type', array('nin' => $listNotificationType))
            ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
            ->addFieldToFilter('is_sent', NotificationModel::IS_NOT_SENT)
            ->addFieldToFilter('send_time', ['eq' => 'send_immediately']);
        foreach ($listNotification as $notification) {
            $this->helper->sendNotificationInMagento($notification->getData());
            $this->helper->sendNotificationWithFireBase($notification->getData());
            $notification->setData('is_sent', NotificationModel::IS_SENT);
            $this->notificationResource->save($notification);
        }
    }

    /**
     * overwrite function reminderAbandonedCart
     * send notification reminder abandoned cart
     * @var \Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory
     */
    public function reminderAbandonedCart()
    {
        if (!$this->helper->getEnableModule()) {
            return;
        }
        $listNotification = $this->notificationCollection->create();
        $listNotification = $listNotification->addFieldToFilter('notification_type', 'abandoned_cart_reminds')
            ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
            ->getData();

        $listStore = $this->getListStore();
        $allAbandonedCart = $this->abandonedCart->create()->prepareForAbandonedReport($listStore);

        //get current store date time
        $now = $this->dateTime->gmtDate();
        foreach ($allAbandonedCart as $item) {
            $customerId = $item->getCustomerId();
            $customerGroupId = $item->getCustomerGroupId();
            $storeId = $item->getStoreId();
            $timeUpdate = strtotime($item->getUpdatedAt());
            foreach ($listNotification as $notification) {
                $allowSend = true;
                $listTimeSent = [];
                $hour = $notification['condition'];
                //Get the time --$hour-- hour ago
                $remindDay = date('Y-m-d H:i:s', strtotime('-' . $hour . ' hour', strtotime($now)));
                if (isset($notification['time_sent'])) {
                    $listTimeSent = $this->serialize->unserialize($notification['time_sent']);
                    //$listTimeSent[$customerId]: the latest reminder time
                    if (isset($listTimeSent[$customerId]) && $remindDay < $listTimeSent[$customerId]) {
                        $allowSend = false;
                    }
                }

                if ($allowSend && strtotime($remindDay) >= $timeUpdate) {
                    $notificationModel = $this->notificationFactory->create();
                    $this->notificationResource->load($notificationModel, $notification['id']);

                    $listStoreView = $this->serialize->unserialize($notification['store_view']);
                    $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
                    if (!in_array('0', $listStoreView) && !in_array($storeId, $listStoreView)) {
                        continue;
                    }
                    //sent notification via firebase
                    $customerToken = $this->customerTokenCollection->create()->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
                        ->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED);
                    if ($customerToken) {
                        $tokenSent = [];
                        foreach ($customerToken as $token) {
                            $currentToken = ['token' => $token->getToken(), 'id' => $token->getGuestId()];
                            if (!in_array($currentToken, $tokenSent)) {
                                $this->helper->sendNotificationWithFireBase($notification, $token);
                            }
                            $tokenSent[] = $currentToken;
                        }
                    }
                    // unset($notification['id']);
                    unset($notification['created_at']);
                    $this->saveCustomerNotification($notification, $customerId);
                    //update time sent
                    if (isset($notificationModel['time_sent']) && $notificationModel['time_sent'] !== null) {
                        $listTimeSent = $this->serialize->unserialize($notificationModel->getTimeSent());
                    }
                    $listTimeSent[$customerId] = $now;
                    $notificationModel->setTimeSent($this->serialize->serialize($listTimeSent));
                    $this->notificationResource->save($notificationModel);
                }
            }
        }
    }

    /**
     * OverWrite function saveCustomerNotification
     * @param $notification
     * @param $customerId
     */
    private function saveCustomerNotification($notification, $customerId)
    {
        try {
            unset($notification['created_at']);
            unset($notification['entity_id']);
            $notification['customer_id'] = $customerId;
            if (isset($notification['id'])) {
                $notification['notification_id'] = $notification['id'];
                unset($notification['id']);
            }
            $notification['icon'] = $notification['image'];
            $notification['star'] = CustomerNotificationModel::UNSTAR;
            $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
            $customerNotification = $this->customerNotificationFactory->create();
            $customerNotification->addData($notification);
            $this->customerNotificationResource->save($customerNotification);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
