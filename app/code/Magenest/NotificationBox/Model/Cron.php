<?php

namespace Magenest\NotificationBox\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use \Magento\Reports\Model\ResourceModel\Quote\CollectionFactory as AbandonedCart;
use \Magento\Store\Model\StoreManagerInterface as StoreManage;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollection;
use Magento\Framework\Serialize\Serializer\Json;
use \Magento\Framework\Stdlib\DateTime\DateTime as DateTime;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken;
use Magenest\NotificationBox\Model\CustomerTokenFactory;
use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken\CollectionFactory;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\NotificationFactory;
use Magenest\NotificationBox\Model\ResourceModel\Notification;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue\Collection as NotificationQueueCollection;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use \Magenest\NotificationBox\Model\Notification as NotificationModel;
use \Magento\Framework\Message\ManagerInterface;
use Magenest\NotificationBox\Model\CustomerToken as CustomerTokenModel;
use \Magento\Store\Api\StoreRepositoryInterface;

class Cron
{
    /** @var \Magenest\NotificationBox\Model\CustomerTokenFactory */
    protected $customerTokenFactory;

    /** @var CustomerToken */
    protected $customerTokenResource;

    /** @var AbandonedCart */
    protected $abandonedCart;

    /** @var StoreManage */
    protected $listStore;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var NotificationCollection */
    protected $notificationCollection;

    /** @var Json */
    protected $serialize;

    /** @var DateTime */
    protected $dateTime;

    /** @var CustomerNotificationFactory */
    protected $customerNotificationFactory;

    /** @var Helper */
    protected $helper;

    /** @var Collection */
    protected $customerTokenCollection;

    /** @var CustomerNotification */
    protected $customerNotificationResource;

    /** @var \Magenest\NotificationBox\Model\NotificationFactory */
    protected $notificationFactory;

    /** @var Notification */
    protected $notificationResource;

    /** @var NotificationQueueCollection */
    protected $notificationQueueCollection;

    /**
     * @var \Magenest\NotificationBox\Model\NotificationQueueFactory
     */
    protected $notificationQueueFactory;

    /** @var NotificationQueue */
    protected $notificationQueue;

    /** @var ManagerInterface  */
    protected $managerInterface;

    /** @var StoreRepositoryInterface  */
    protected $storeRepositoryInterface;

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
     * @param \Magenest\NotificationBox\Model\CustomerTokenFactory $customerTokenFactory
     * @param CustomerNotification $customerNotificationResource
     * @param \Magenest\NotificationBox\Model\NotificationFactory $notificationFactory
     * @param Notification $notificationResource
     * @param NotificationQueueCollection $notificationQueueCollection
     * @param \Magenest\NotificationBox\Model\NotificationQueueFactory $notificationQueueFactory
     * @param NotificationQueue $notificationQueue
     * @param ManagerInterface $managerInterface
     * @param StoreRepositoryInterface $storeRepositoryInterface
     */
    public function __construct(
        Helper $helper,
        AbandonedCart $abandonedCart,
        StoreManage $storeManager,
        \Psr\Log\LoggerInterface $logger,
        NotificationCollection $notificationCollection,
        Json $serialize,
        DateTime $dateTime,
        CustomerNotificationFactory $customerNotificationFactory,
        CollectionFactory $customerTokenCollection,
        CustomerToken $customerTokenResource,
        CustomerTokenFactory $customerTokenFactory,
        CustomerNotification $customerNotificationResource,
        NotificationFactory $notificationFactory,
        Notification $notificationResource,
        NotificationQueueCollection $notificationQueueCollection,
        NotificationQueueFactory $notificationQueueFactory,
        NotificationQueue $notificationQueue,
        ManagerInterface $managerInterface,
        StoreRepositoryInterface $storeRepositoryInterface
    )
    {
        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->managerInterface = $managerInterface;
        $this->notificationQueue = $notificationQueue;
        $this->notificationQueueFactory = $notificationQueueFactory;
        $this->notificationQueueCollection = $notificationQueueCollection;
        $this->notificationFactory = $notificationFactory;
        $this->notificationResource = $notificationResource;
        $this->customerNotificationResource = $customerNotificationResource;
        $this->customerTokenResource = $customerTokenResource;
        $this->customerTokenFactory = $customerTokenFactory;
        $this->customerTokenCollection = $customerTokenCollection;
        $this->helper = $helper;
        $this->listStore = $storeManager;
        $this->abandonedCart = $abandonedCart;
        $this->logger = $logger;
        $this->notificationCollection = $notificationCollection;
        $this->serialize = $serialize;
        $this->dateTime = $dateTime;
        $this->customerNotificationFactory = $customerNotificationFactory;
    }

    /**
     * Resets the notification limit received per day by customer
     */
    public function resetLimitNumberOfNotification()
    {
        if (!$this->helper->getEnableModule()) {
            return;
        }
        $allCustomerToken = $this->customerTokenCollection->create();
        foreach ($allCustomerToken as $token) {
            try {
                $tokenModel = $this->customerTokenFactory->create();
                $this->customerTokenResource->load($tokenModel, $token->getEntityId());
                $tokenModel->setData('limit', 0);
                $this->customerTokenResource->save($tokenModel);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    /** send notification reminder abandoned cart */
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
                if(isset($notification['time_sent'])){
                    $listTimeSent = $this->serialize->unserialize($notification['time_sent']);
                    //$listTimeSent[$customerId]: the latest reminder time
                    if(isset($listTimeSent[$customerId]) && $remindDay < $listTimeSent[$customerId]){
                        $allowSend = false;
                    }
                }

                if ($allowSend && strtotime($remindDay) >= $timeUpdate) {
                    $notificationModel = $this->notificationFactory->create();
                    $this->notificationResource->load($notificationModel,$notification['id']);

                    $listStoreView = $this->serialize->unserialize($notification['store_view']);
                    $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
                    if (!in_array('0', $listStoreView) && !in_array($storeId, $listStoreView) ||
                        !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup)) {
                        continue;
                    }
                    //sent notification via firebase
                    $customerToken = $this->customerTokenCollection->create()->addFieldToFilter('customer_id', $customerId)
                                    ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
                                    ->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED);
                    if($customerToken){
                        $tokenSent = [];
                        foreach ($customerToken as $token){
                            $currentToken =  ['token' => $token->getToken(), 'id'=>$token->getGuestId()];
                            if(!in_array($currentToken,$tokenSent)){
                                $this->helper->sendNotificationWithFireBase($notification,$token);
                            }
                            $tokenSent[] = $currentToken;
                        }
                    }
                    unset($notification['id']);
                    unset($notification['created_at']);
                    $this->saveCustomerNotification($notification,$customerId);
                    //update time sent
                    if(isset($notificationModel['time_sent']) && $notificationModel['time_sent']!== null) {
                        $listTimeSent = $this->serialize->unserialize($notificationModel->getTimeSent());
                    }
                    $listTimeSent[$customerId] = $now;
                    $notificationModel->setTimeSent($this->serialize->serialize($listTimeSent));
                    $this->notificationResource->save($notificationModel);
                }
            }
        }
    }

    /** return array */
    public function getListStore()
    {
        $options = [];
        //$listStore = $this->listStore->getGroups();
        $listStore = $this->storeRepositoryInterface->getList();
        foreach ($listStore as $store) {
            $options[] = $store->getId();
        }
        return $options;
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
        $listNotificationType = [NotificationModel::REVIEW_REMINDERS, NotificationModel::ORDER_STATUS_UPDATE, NotificationModel::ABANDONED_CART_REMINDS];

        //get all the notices to send
        $listNotification = $this->notificationCollection->create()
            ->addFieldToFilter('notification_type', array('nin' => $listNotificationType))
            ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
            ->addFieldToFilter('is_sent', NotificationModel::IS_NOT_SENT)
            ->addFieldToFilter('send_time', ['neq' => 'send_immediately'])
            ->getData();
        $listNotificationQueue = $this->notificationQueueCollection->addFieldToFilter('is_sent', NotificationModel::IS_NOT_SENT)->getData();

        $allCustomer = $this->helper->getAllCustomer();

        $now = $this->dateTime->gmtDate();

        //Send custom notice
        foreach ($listNotification as $notification) {
            $notificationSent[] = $this->sendNotificationViaMagentoAndFireBase($notification,$now,$allCustomer);
        }

        //Send queue notice
        foreach ($listNotificationQueue as $notification) {
            $notificationQueue[] = $this->sendNotificationViaMagentoAndFireBase($notification,$now,$notification['customer_id']);
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
                if(isset($item))
                {
                    $notificationQueueModel = $this->notificationQueueFactory->create();
                    $this->notificationQueue->load($notificationQueueModel, $item,'id');
                    try {
                        $this->notificationQueue->delete($notificationQueueModel);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }
    }

    public function sendNotificationAfterSave(){
        if (!$this->helper->getEnableModule()) {
            return;
        }
        $listNotificationType = [NotificationModel::REVIEW_REMINDERS, NotificationModel::ORDER_STATUS_UPDATE, NotificationModel::ABANDONED_CART_REMINDS];
        $listNotification = $this->notificationCollection->create()
            ->addFieldToFilter('notification_type', array('nin' => $listNotificationType))
            ->addFieldToFilter('is_active', NotificationModel::ACTIVE)
            ->addFieldToFilter('is_sent', NotificationModel::IS_NOT_SENT)
            ->addFieldToFilter('send_time', ['eq' => 'send_immediately']);
        foreach ($listNotification as $notification){
            $this->helper->sendNotificationInMagento($notification->getData());
            $this->helper->sendNotificationWithFireBase($notification->getData());
            $notification->setData('is_sent',NotificationModel::IS_SENT);
            $this->notificationResource->save($notification);
        }
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

    /**
     * send notification via Firebase and Magento
     * return list notification id sent
     * @param $notification
     * @param $now
     * @param $allCustomer
     * @return array
     * @throws AlreadyExistsException
     */
    private function sendNotificationViaMagentoAndFireBase($notification,$now,$allCustomer){
        $id = null;
        try{
            //Send notifications to tokens that satisfy the condition via firebase
            $timeToSend = $this->getTimeToSendNotification($notification);
            if (isset($timeToSend) && $now >= $timeToSend) {
                if(is_string($allCustomer)){
                    //send notice via magento
                    $this->saveCustomerNotification($notification,$notification['customer_id']);
                    //send notice via firebase
                    $tokens = ($notification['token'])?$notification['token'] : $this->helper->getToken($notification);
                    $tokenSent = [];
                    foreach ($tokens as $token){
                        $currentToken =  ['token' => $token->getToken(), 'id'=>$token->getGuestId()];
                        if(!in_array($currentToken,$tokenSent)){
                            $this->helper->sendNotificationWithFireBase($notification,$token);
                        }
                        $tokenSent[] = $currentToken;
                    }
                    $id = $notification['id'];
                }
                elseif (isset($allCustomer)){
                    //send to guest
                    $this->helper->sendNotificationWithFireBase($notification);
                    //send to customer
                    foreach ($allCustomer as $item) {
                        $customerId = $item->getEntityId();
                        $customerGroupId = $item->getGroupId();
                        $storeId = $item->getStoreId();

                        $listStoreView = $this->serialize->unserialize($notification['store_view']);
                        $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
                        if (!in_array('0', $listStoreView) && !in_array($storeId, $listStoreView) ||
                            !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup))
                        {
                            continue;
                        }
                        $this->saveCustomerNotification($notification,$customerId);
                    }
                    $id = $notification['id'];
                }else{
                    if(isset($notification['token'])){
                        $customerToken = $this->customerTokenCollection->create()
                            ->addFieldToFilter('token',$notification['token'])
                            ->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED)
                            ->addFieldToFilter('is_active', CustomerTokenModel::IS_ACTIVE);
                        $this->helper->sendNotificationWithFireBase($notification,$customerToken->getFirstItem());
                        $id = $notification['id'];
                    }
                }
            }
        }catch (\Exception $e){
            $this->managerInterface->addErrorMessage($e->getMessage());
        }
        return $id;
    }

    /**
     * @param $notification
     * @param $customerId
     */
    private function saveCustomerNotification($notification,$customerId){
        try{
            unset($notification['created_at']);
            unset($notification['entity_id']);
            $notification['customer_id'] = $customerId;
            $notification['icon'] = $notification['image'];
            $notification['star'] = CustomerNotificationModel::UNSTAR;
            $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
            $customerNotification = $this->customerNotificationFactory->create();
            $customerNotification->addData($notification);
            $this->customerNotificationResource->save($customerNotification);
        }catch (\Exception $exception){
            $this->logger->error($exception->getMessage());
        }
    }
}
