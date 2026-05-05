<?php
namespace Branch8\AppNotification\Model;


use Branch8\AppNotification\Api\CustomerNotificationManagementInterface;

use Branch8\AppNotification\Api\Data\CustomerNotificationResponseInterface;
use Branch8\AppNotification\Api\Data\MarkAsReadResponseInterfaceFactory;
use Branch8\AppNotification\Api\Data\CustomerNotificationResponseInterfaceFactory;
use Branch8\AppNotification\Api\Data\NotificationInterface;
use Branch8\AppNotification\Api\Data\NotificationInterfaceFactory;
use Branch8\CustomNotification\Helper\Helper;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\CollectionFactory as NotificationTypeCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class CustomerNotificationManagement implements CustomerNotificationManagementInterface
{
    const PERSONAL_TYPE = 2;

    protected CollectionFactory $collectionFactory;
    protected CustomerNotificationResponseInterfaceFactory $responseInterfaceFactory;
    protected NotificationInterfaceFactory $notificationInterfaceFactory;
    protected Helper $helper;
    protected NotificationTypeCollection $notificationTypeCollection;

    protected $countAll = null;
    protected $countPersonal = null;
    protected int $customerId;
    protected $personalNotificationTypes = null;
    private NotificationType $notificationTypeResource;
    private NotificationTypeFactory $notificationTypeFactory;
    private StoreManagerInterface $storeManagerInterface;
    private ResourceConnection $resource;
    private CustomerNotification $customerNotificationResource;
    private MarkAsReadResponseInterfaceFactory $markAsReadResponseInterfaceFactory;

    public function __construct(
        CollectionFactory $collectionFactory,
        CustomerNotificationResponseInterfaceFactory $responseInterfaceFactory,
        NotificationInterfaceFactory $notificationInterfaceFactory,
        NotificationTypeCollection $notificationTypeCollection,
        Helper $helper,
        NotificationType $notificationTypeResource,
        NotificationTypeFactory $notificationTypeFactory,
        StoreManagerInterface $storeManagerInterface,
        ResourceConnection $resource,
        CustomerNotification $customerNotificationResource,
        MarkAsReadResponseInterfaceFactory $markAsReadResponseInterfaceFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->responseInterfaceFactory = $responseInterfaceFactory;
        $this->notificationInterfaceFactory = $notificationInterfaceFactory;
        $this->helper = $helper;
        $this->notificationTypeResource = $notificationTypeResource;
        $this->notificationTypeFactory = $notificationTypeFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->notificationTypeCollection = $notificationTypeCollection;
        $this->resource = $resource;
        $this->customerNotificationResource = $customerNotificationResource;
        $this->markAsReadResponseInterfaceFactory = $markAsReadResponseInterfaceFactory;
    }


    /**
     * @param $customerId
     * @param $pageSize
     * @param $curPage
     * @param $type
     * @return CustomerNotificationResponseInterface
     */
    public function getList($customerId, $pageSize = 10, $curPage = 1, $type = 1)
    {
        $this->customerId = $customerId;

        $notificationCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);

        $types = $this->getPersonalTypes();
        // Apply filters to the main collection for display based on the type
        if ($type == self::PERSONAL_TYPE) {
            $notificationCollection->addFieldToFilter('notification_type', ['in' => $types]);
        }else{
            $notificationCollection->addFieldToFilter('notification_type', ['nin' => $types]);
        }

        $notificationCollection->setOrder('entity_id','DESC');
        $notificationCollection->setPageSize($pageSize);
        $notificationCollection->setCurPage($curPage);

        /** @var NotificationInterface[] $items */
        $items = [];
        $orderIds = [];
        foreach ($notificationCollection as $notification)
        {
            $notificationModel = $this->notificationTypeFactory->create();
            $notificationType = $notification->getData('notification_type');
            if( in_array($notificationType, [
                    NotificationModel::ORDER_STATUS_UPDATE,
                    NotificationModel::ABANDONED_CART_REMINDS,
                    'return_exchange',
                    'news',
                    'review_reminders'
                ]
            )) {
                $this->notificationTypeResource->load($notificationModel,$notification['notification_type'],'default_type');
            }
            else{
                $this->notificationTypeResource->load($notificationModel,$notification['notification_type'],'entity_id');
            }
            $notification->setData('notification_type', $notificationModel->getName());
            $notification->setData('notification_type_id', $notificationType);


            $iconFullUrl = $this->helper->getImageByNotificationType(['notification_type' => $notificationType]);
            $notification->setData('icon', $iconFullUrl);

            if ($notification->getOrderId()) {
                $orderIds[] = $notification->getOrderId();

            }

            /** @var NotificationInterface $notificationData */
            $notificationData = $this->notificationInterfaceFactory->create();

            $notificationData->setData($notification->getData());
            $notificationData->setCreatedAt($this->helper->showTime($notificationData->getCreatedAt()));

            $items[] = $notificationData;
        }


        if (!empty($orderIds)) {
            $hotaiParentOrderNumbers = $this->getHotaiParentOrderNumbers($orderIds);

            foreach ($items as $item) {
                if (isset($hotaiParentOrderNumbers[$item->getOrderId()])) {
                    $item->setOrderId($hotaiParentOrderNumbers[$item->getOrderId()]);
                } else  {
                    $item->setOrderId(null);
                }
            }
        }


        /** @var CustomerNotificationResponseInterface $response */
        $response = $this->responseInterfaceFactory->create();
        $response->setItems($items)
            ->setTotalCount($notificationCollection->getSize())
            ->setCurPage($curPage)
            ->setPageSize($pageSize)
            ->setUnreadCount($type == self::PERSONAL_TYPE ? $this->getUnreadPersonalCount() : $this->getUnreadNonPersonalCount());
        return $response;
    }


    /**
     * @param string[] $childIds
     * @return array
     */
    public function getHotaiParentOrderNumbers($childIds)
    {
        if (empty($childIds)) {
            return [];
        }

        try {
            $connection = $this->resource->getConnection();
            $tableChildren = $this->resource->getTableName('sales_parent_order_children');
            $tableDetail = $this->resource->getTableName('sales_parent_order_detail');

            $select = $connection->select()
                ->from(['spoc' => $tableChildren], ['children_id'])
                ->joinLeft(
                    ['spod' => $tableDetail],
                    'spoc.parent_id = spod.parent_id',
                    ['increment_id']
                )
                ->where('spoc.children_id IN (?)', $childIds);

            return $connection->fetchPairs($select) ?: [];

        } catch (\Exception $e) {
            // Log exception if needed
            return [];
        }
    }

    /**
     * @param int $customerId
     * @return int
     */
    public function getUnreadCount($customerId)
    {
        $allNotificationsCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status',0);
        return $allNotificationsCollection->getSize();
    }

    /**
     * @param array<int|string> $customerIds
     * @return array<string,int> customer_id => unread_count
     */
    public function getUnreadCountByCustomerIds(array $customerIds): array
    {
        $ids = array_unique($customerIds);
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = 0;
        }
        if (!$ids) {
            return $result;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 0);
        $collection->addFieldToFilter('customer_id', ['in' => $ids]);

        $select = $collection->getSelect();
        $select->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'customer_id',
                'unread' => new \Zend_Db_Expr('COUNT(main_table.entity_id)')
            ])
            ->group('customer_id');

        foreach ($collection as $row) {
            $cid = (string)$row->getData('customer_id');
            $result[$cid] = (int)$row->getData('unread');
        }

        return $result;
    }

    /**
     * @param int $customerId
     * @param int $notificationId
     * @param string $entityIdType
     * @return \Branch8\AppNotification\Api\Data\MarkAsReadResponseInterface
     */
    public function markAsRead($customerId, $notificationId, $entityIdType)
    {
        $connection = $this->customerNotificationResource->getConnection();
        $tableName = $this->customerNotificationResource->getMainTable();
        $this->customerId = $customerId;

        if ($entityIdType == 'customer_notification_id') {
            $where = [
                'customer_id = ?' => $customerId,
                'entity_id = ?'   => $notificationId,
                'status = ?'      => CustomerNotificationModel::STATUS_UNREAD
            ];
        } else {
            $where = [
                'customer_id = ?' => $customerId,
                'notification_id = ?' => $notificationId,
                'status = ?'      => CustomerNotificationModel::STATUS_UNREAD
            ];
        }


        $data = ['status' => CustomerNotificationModel::STATUS_READ];

        $connection->update($tableName, $data, $where);

        return $this->markAsReadResponseInterfaceFactory->create()
            ->setAllUnreadCount($this->getUnreadNonPersonalCount())
            ->setPersonalUnreadCount($this->getUnreadPersonalCount());
    }


    public function getUnreadNonPersonalCount()
    {
        $types = $this->getPersonalTypes();
        $allNotificationsCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $this->customerId)
            ->addFieldToFilter('notification_type', ['nin' => $types])
            ->addFieldToFilter('status',0);

        return $allNotificationsCollection->getSize();
    }


    public function getUnreadPersonalCount()
    {
        $types = $this->getPersonalTypes();
        $personalNotificationsCollection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $this->customerId)
            ->addFieldToFilter('notification_type', ['in' => $types])
            ->addFieldToFilter('status',0);
        return $personalNotificationsCollection->getSize();
    }

    public function getPersonalTypes()
    {
        if (!$this->personalNotificationTypes) {
            $this->personalNotificationTypes = $this->helper->getPersonalNotificationTypes();
        }
        return $this->personalNotificationTypes;
    }
}
