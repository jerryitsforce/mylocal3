<?php
namespace Branch8\AppNotification\Controller\Notification;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollection;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;

class View extends Action
{
    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var AdapterInterface
     */
    protected $connection;
    const NEWS_TYPE = 4;
    /**
     * @var NotificationCollection
     */
    protected $collectionFactory;
    private CustomerNotification $customerNotificationResource;
    private Session $customerSession;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerNotificationFactory $customerNotificationFactory
     * @param CustomerNotification $customerNotificationResource
     * @param NotificationCollection $collectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerNotificationFactory $customerNotificationFactory,
        CustomerNotification $customerNotificationResource,
        NotificationCollection $collectionFactory,
        ResourceConnection $resource,
        Session $customerSession
    ) {
        $this->customerNotificationResource = $customerNotificationResource;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();
        $notificationId = $params['id'];
        $connection = $this->customerNotificationResource->getConnection();
        $select = $connection->select()->from('magenest_customer_notification')->where(
            'notification_id = ?',
            $notificationId
        )->where(
            'customer_id = ?',
            $this->customerSession->getCustomerId()
        );

        $notification = $connection->fetchRow($select);

        if (!$notification) {
            $resultRedirect->setUrl('/');
            return $resultRedirect;
        }

        if(!in_array($notification['notification_type'], ['review_reminders', 'order_status_update', 'abandoned_cart_reminds', 'return_exchange', 'spin_to_win'])){
            $collectionData = $this->collectionFactory->create()->addFieldToFilter('id', $notification['notification_id'])->getFirstItem()->getData();
            if(!empty($collectionData)){
                $resultRedirect->setPath($collectionData['url_key']);
                $this->markNotificationAsRead($notification);
                return $resultRedirect;
            }else{
                $resultRedirect->setUrl('/');
                return $resultRedirect;
            }
        }
        if($notification['notification_type'] == 'order_status_update' || $notification['notification_type'] == 'return_exchange'){
            $url = $this->_url->getUrl('sales/parentOrder/history');
            if(!empty($notification['order_id'])){
                $childId = $notification['order_id'];
                $hotaiParentOrderNumber = $this->getParentOrderInfo($childId);
                if($hotaiParentOrderNumber){
                    $url = $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
                    $resultRedirect->setUrl($url);
                    $this->markNotificationAsRead($notification);
                    return $resultRedirect;
                }
            }
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if($notification['notification_type'] == 'review_reminders' && !empty($notification['order_id'])){
            $url = $this->_url->getUrl('sales/parentOrder/history');
            if(!empty($notification['order_id'])){
                $childId = $notification['order_id'];
                $hotaiParentOrderNumber = $this->getParentOrderInfo($childId);
                if($hotaiParentOrderNumber){
                    $url = $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
                    $resultRedirect->setUrl($url);
                    $this->markNotificationAsRead($notification);
                    return $resultRedirect;
                }
            }
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if($notification['notification_type'] == 'spin_to_win' && !empty($notification['segment_report_id'])){
            $url = $this->_url->getUrl('spintowin/notification/view', ['smrpid' => $notification['segment_report_id']]);
            $this->markNotificationAsRead($notification);
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        $url = $notification['redirect_url'];
        $this->markNotificationAsRead($notification);

        $resultRedirect->setPath($url);
        return $resultRedirect;
    }


    private function markNotificationAsRead($notification)
    {
        $connection = $this->customerNotificationResource->getConnection();
        if(!$notification['status']){
            $connection->update(
                'magenest_customer_notification',
                ['status' => CustomerNotificationModel::STATUS_READ],
                [
                    'entity_id = ?' => $notification['entity_id'],
                ]
            );
        }
    }

    /**
     * @param $childId
     * @return false|mixed
     */
    public function getParentOrderInfo($childId){
        try {
            // Initialize the database connection
            $connection = $this->getConnection();
            // Build the select query
            $select = $connection->select()
                ->from(['spoc' => $this->resource->getTableName('sales_parent_order_children')],[])
                ->joinLeft(
                    ['spod' => $this->resource->getTableName('sales_parent_order_detail')],
                    'spoc.parent_id = spod.parent_id',
                    ['increment_id']
                )
                ->where('spoc.children_id = ?', $childId);

            // Fetch the results
            $result = $connection->fetchOne($select);

            // Check the condition when hotai_parent_order_number is empty
            if (empty($result)) {
                return false;
            } else {
                return $result;
            }

        } catch (\Exception $e) {
            return false;
        }

    }



    /**
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }
        return $this->connection;
    }
}

