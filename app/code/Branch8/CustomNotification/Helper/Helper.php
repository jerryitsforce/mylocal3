<?php

namespace Branch8\CustomNotification\Helper;

use Branch8\CustomNotification\Model\ConfigData;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\CustomerToken as CustomerTokenModel;
use Magenest\NotificationBox\Model\CustomerTokenFactory;
use Magenest\NotificationBox\Model\NotificationFactory;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken\Collection as CustomerTokenCollection;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\Notification;
use Magenest\NotificationBox\Model\ResourceModel\Notification\Collection as NotificationCollection;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\Collection as NotificationTypeCollection;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Helper extends \Magenest\NotificationBox\Helper\Helper
{
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;
    private ConfigData $configData;
    private ?array $personalNotificationTypes = null;

    /**
     * @param Json $json
     * @param Context $context
     * @param UrlHelper $urlHelper
     * @param FormKey $formKey
     * @param CustomerNotificationFactory $customerNotificationFactory
     * @param CustomerNotification $customerNotification
     * @param Session $session
     * @param CustomerTokenFactory $customerTokenFactory
     * @param CustomerToken $customerNotificationResource
     * @param NotificationTypeCollection $notificationTypeCollection
     * @param NotificationCollection $notificationCollection
     * @param NotificationTypeFactory $notificationTypeFactory
     * @param NotificationType $notificationType
     * @param CustomerCollection $customerCollection
     * @param CustomerTokenCollection $customerTokenCollection
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResource
     * @param StoreManagerInterface $storeManagerInterface
     * @param NotificationFactory $notificationFactory
     * @param Notification $notificationResource
     * @param Repository $repository
     * @param LoggerInterface $logger
     * @param CustomerGroup $customerGroup
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param PublisherInterface $publisher
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Json                        $json,
        Context                     $context,
        UrlHelper                   $urlHelper,
        FormKey                     $formKey,
        CustomerNotificationFactory $customerNotificationFactory,
        CustomerNotification        $customerNotification,
        Session                     $session,
        CustomerTokenFactory        $customerTokenFactory,
        CustomerToken               $customerNotificationResource,
        NotificationTypeCollection  $notificationTypeCollection,
        NotificationCollection      $notificationCollection,
        NotificationTypeFactory     $notificationTypeFactory,
        NotificationType            $notificationType,
        CustomerCollection          $customerCollection,
        CustomerTokenCollection     $customerTokenCollection,
        CustomerFactory             $customerFactory,
        Customer                    $customerResource,
        StoreManagerInterface       $storeManagerInterface,
        NotificationFactory         $notificationFactory,
        Notification                $notificationResource,
        Repository                  $repository,
        LoggerInterface             $logger,
        CustomerGroup               $customerGroup,
        CollectionFactory           $collectionFactory,
        ResourceConnection          $resource,
        PublisherInterface          $publisher,
        TimezoneInterface           $timezone,
        ConfigData                  $configData
    )
    {
        parent::__construct($json, $context, $urlHelper, $formKey, $customerNotificationFactory, $customerNotification,
            $session, $customerTokenFactory, $customerNotificationResource, $notificationTypeCollection,
            $notificationCollection, $notificationTypeFactory, $notificationType, $customerCollection,
            $customerTokenCollection, $customerFactory, $customerResource, $storeManagerInterface, $notificationFactory,
            $notificationResource, $repository, $logger, $customerGroup, $collectionFactory, $resource, $publisher);
        $this->timezone = $timezone;
        $this->configData = $configData;
    }

    /**
     * Send notification in magento.
     *
     * @param array $notification
     *
     * @return void
     */
    public function sendNotificationInMagento($notification): void
    {
        $pageSize = 5000;
        $tableName = $this->resource->getTableName('magenest_customer_notification');
        $listCustomerLevels = $this->json->unserialize($notification['customer_levels']);
        $listStore = $this->json->unserialize($notification['store_view']);
        $isIncludeOneIdList = in_array($this->configData->getOneIdGroup(), $listCustomerLevels);
        $data = [];
        $customers = array_unique($this->getCustomers($listCustomerLevels, $listStore, $notification['id'], $isIncludeOneIdList));
        foreach ($customers as $customerId) {
            $finalNotification = $notification;
            $finalNotification['customer_id'] = (int)$customerId;
            $finalNotification['star'] = 0;
            $finalNotification['status'] = 0;
            $finalNotification['notification_id'] = $notification['id'];
            $finalNotification = $this->unsetDataBeforeSave($finalNotification);
            $data[] = $finalNotification;

            if (count($data) >= $pageSize) {
                $this->connection->insertMultiple($tableName, $data);
                $data = [];
            }
        }

        if (!empty($data)) {
            $this->connection->insertMultiple($tableName, $data);
        }
    }

    /**
     * @param $groupId
     * @param $storeIds
     * @param $notificationId
     * @param $isIncludeOneListId
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    private function getCustomers($groupId, $storeIds, $notificationId, $isIncludeOneListId = false): array
    {
        $this->resource->getConnection();
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('customer_entity');

        $select = $connection->select()
            ->from($tableName, ['entity_id']);

        if (is_array($groupId)) {
            $select->where('group_id IN (?)', $groupId);
        }

        if (is_array($storeIds) && !in_array('0', $storeIds)) {
            $select->where('store_id IN (?)', $storeIds);
        }
        if ($isIncludeOneListId) {
            $selectLeft = clone $select;
            $oneIdListSelect = clone $select;
            $oneIdListSelect->reset();
            $oneIdListSelect->from('magenest_notification_customer_index',
                ['entity_id' => 'customer_id'])->where(
                'notification_id = (?)', $notificationId
            );
            $select->reset()->union([$selectLeft, $oneIdListSelect]);
        }
        return $connection->fetchCol($select);
    }

    /**
     * Send notification via firebase.
     *
     * @param array $notification
     * @param DataObject|null $token
     *
     * @return void
     */
    public function sendNotificationWithFireBase($notification, $token = null): void
    {
        $allCustomerToken = [];
        $listStore = $this->json->unserialize($notification['store_view']);
        try {
            //get all token
            if (null !== $token) {
                //only send notification to 1 token
                if ($token->getIsActive()) {
                    $allCustomerToken = ['token' => $token->getData()];
                }
            } else {
                //send to all token
                $allCustomerToken = $this->customerTokenCollection->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED)
                    ->addFieldToFilter('is_active', CustomerTokenModel::IS_ACTIVE);
                if (!in_array('0', $listStore)) {
                    $allCustomerToken->addFieldToFilter('store_id', ['in' => $listStore]);
                }
                $allCustomerToken = $allCustomerToken->getData();
            }
            $maximumNumberNotificationPerDay = $this->getMaximumNotification();
            if (count($allCustomerToken)) {
                $listCustomerLevels = $this->json->unserialize($notification['customer_levels']);
                $customerGroupIds = $this->getCustomerGroupIds();
                $isSent = [];
                foreach ($allCustomerToken as $token) {
                    //prevent sending multiple same notification to 1 customer
                    if (in_array($token['token'], $isSent)) {
                        continue;
                    }
                    $limit = $this->getLimitNotification($token);
                    if ($limit < $maximumNumberNotificationPerDay) {
                        if (!in_array('0', $listStore) && !in_array($token['store_id'], $listStore)) {
                            continue;
                        }
                        $customerId = $token['customer_id'] ?? null;
                        if (!$customerId) { //guest token
                            continue;
                        }
                        //customer token
                        if (is_array($listCustomerLevels)) {
                            $customerLevelId = $customerGroupIds[$customerId] ?? null;
                            if (!$customerLevelId || !in_array($customerLevelId, $listCustomerLevels)) {
                                continue;
                            }
                        }

                        $urlImage = $this->getImageByNotificationType($notification);
                        $baseUrl = $this->storeManagerInterface->getStore()->getBaseUrl() .
                            "notibox/handleNotification/clickToNotification?notificationId=" .
                            $notification['id'] . "&url=";
                        $url = $notification['redirect_url'] ?? '';
                        $data = [
                            "notification" => [
                                "title" => $notification['name'],
                                "body" => $notification['description'],
                                "icon" => $urlImage,
                                "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                            ],
                            "to" => $token['token'],
                            "data" => [
                                "id" => $notification['id'],
                                "title" => $notification['name'],
                                "body" => $notification['description'],
                                "icon" => $urlImage,
                                "click_action" => $baseUrl . $url
                            ]
                        ];
                        $data_string = json_encode($data);
                        $this->sendNotification($data_string, $token['token'], $notification['id']);
                        $isSent[] = $token['token'];
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('There was an error sending notifications via firebase :' . $exception->getMessage());
        }
    }

    /**
     * Get all customer group IDs in a single query
     *
     * @return array
     */
    public function getCustomerGroupIds(): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('customer_entity');

        $select = $connection->select()
            ->from($tableName, ['entity_id', 'group_id']);

        return $connection->fetchPairs($select);
    }

    /**
     * Get all personal notification types from hardcoded defaults + database.
     * Single source of truth used by both frontend Block and REST API.
     *
     * @return array
     */
    public function getPersonalNotificationTypes(): array
    {
        if ($this->personalNotificationTypes === null) {
            $this->personalNotificationTypes = [
                'review_reminders',
                'order_status_update',
                'abandoned_cart_reminds',
                'return_exchange',
                'spin_to_win',
                'automated_reward',
            ];
            $notificationTypes = $this->notificationTypeCollection->addFieldToFilter('is_personal', 1);
            foreach ($notificationTypes as $item) {
                $key = ($item->getDefaultType() != 'null') ? $item->getDefaultType() : $item->getEntityId();
                if (!in_array($key, $this->personalNotificationTypes)) {
                    $this->personalNotificationTypes[] = $key;
                }
            }
        }
        return $this->personalNotificationTypes;
    }

    /**
     * Format time.
     *
     * @param mixed $time
     *
     * @return string
     */
    public function showTime($time): string
    {
        if (empty($time)) {
            return '';
        }
        return (string)$this->timezone->date($time)->format('Y-m-d H:i:s');
    }
}
