<?php

namespace Branch8\AppNotification\Plugin;

use Branch8\AppNotification\Api\Data\NotificationMessageInterfaceFactory;
use Branch8\AppNotification\Model\Queue\SendAppNotification;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken;
use Branch8\AppNotification\Model\Service\FcmNotificationService;
use Magenest\NotificationBox\Helper\Helper;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationBoxPlugin
{
    protected CustomerFcmToken $customerFcmTokenResourceModel;

    protected Json $json;

    protected CollectionFactory $customerCollectionFactory;

    protected NotificationMessageInterfaceFactory $notificationMessageInterfaceFactory;

    protected StoreManagerInterface $storeManagerInterface;

    protected PublisherInterface $publisher;

    protected LoggerInterface $logger;

    public function __construct(
        CustomerFcmToken $customerFcmTokenResourceModel,
        CollectionFactory $customerCollectionFactory,
        NotificationMessageInterfaceFactory $notificationMessageInterfaceFactory,
        StoreManagerInterface $storeManager,
        PublisherInterface $publisher,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->customerFcmTokenResourceModel = $customerFcmTokenResourceModel;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->notificationMessageInterfaceFactory = $notificationMessageInterfaceFactory;
        $this->storeManagerInterface = $storeManager;
        $this->publisher = $publisher;
        $this->json = $json;
        $this->logger = $logger;
    }


    /**
     * @param Helper $subject
     * @param null $result
     * @param $notification
     * @return void
     */
    public function afterSendNotificationInMagento(Helper $subject, $result, $notification)
    {
        $listCustomerGroup = $this->json->unserialize($notification['customer_group']);
        $listStore = $this->json->unserialize($notification['store_view']);

        $page = 1;
        //Log debug
        $this->logger->debug('afterSendNotificationInMagento');
        $this->logger->debug(json_encode($notification));
        do {
            $collection = $this->getActiveFcmTokens($listCustomerGroup, $listStore,  FcmNotificationService::MAX_PER_BATCH, $page);

            $tokens = [];
            $tokenCustomerIds = [];

            foreach ($collection as $item) {
                $tokens[] = $item->getData('token');
                $tokenCustomerIds[$item->getData('token')] = $item->getData('customer_id');
            }
            if (!empty($tokens)) {
                //Log debug
                $this->logger->debug('Send Notification Tokens: ' . implode(',', $tokens));
                $urlImage = $subject->getImageByNotificationType($notification);
//                $baseUrl = $this->storeManagerInterface->getStore()->getBaseUrl() . "notibox/handleNotification/clickToNotification?notificationId=" . $notification['id'] . "&url=";
//                $url = $notification['redirect_url'] ?? "";

                $messageData = $this->notificationMessageInterfaceFactory->create()
                    ->setTokens(implode(',', $tokens))
                    ->setTitle($notification['name'])
                    ->setBody($notification['description'])
                    ->setData($this->json->serialize([
                        "notificationId" => $notification['id'],
                        "title" => $notification['name'],
                        "body" => $notification['description'],
                        "icon" => $urlImage,
                        "target" => "notification", //TODO: Order | CMS page...etc
                        "activeTab" => 0, //TODO all: 0, personal: 1
                        "orderId" => null,
                    ]))
                    ->setAdditionalData($this->json->serialize([
                        "token_customer_ids" => $tokenCustomerIds
                    ]));


                $this->publisher->publish(SendAppNotification::TOPIC_NAME, $messageData);
            }
            $page++;
        } while (count($tokens) === 500);
    }

    public function getActiveFcmTokens($groupIds = [], $storeIds = [], $limit = FcmNotificationService::MAX_PER_BATCH, $page = 1)
    {
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
        $collection = $this->customerCollectionFactory->create();

        $collection->getSelect()
            ->join(
                ['fcm_token' => $collection->getTable('customer_fcm_token')],
                'e.entity_id = fcm_token.customer_id',
                []
            )
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['fcm_token.token', 'fcm_token.customer_id'])
            ->where('fcm_token.is_subscribed = ?', 1);

        if (!empty($storeIds) && !in_array('0', $storeIds)) {
            $collection->getSelect()->where('fcm_token.store_id IN (?)', $storeIds);
        }

        if (!empty($groupIds)) {
            $collection->addFieldToFilter('group_id', ['in' => $groupIds]);
        }

        $collection->setPageSize($limit);
        $collection->setCurPage($page);

        return $collection;
    }


}
