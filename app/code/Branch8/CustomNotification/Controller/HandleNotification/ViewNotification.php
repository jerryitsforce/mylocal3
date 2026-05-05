<?php

namespace Branch8\CustomNotification\Controller\HandleNotification;

use Branch8\CustomNotification\Model\PersonalNotificationTypes;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Result\PageFactory;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollection;

class ViewNotification extends \Magenest\NotificationBox\Controller\HandleNotification\ViewNotification
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

    protected $personalNotificationTypes;
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerNotificationFactory $customerNotificationFactory
     * @param CustomerNotification $customerNotificationResource
     * @param NotificationCollection $collectionFactory
     * @param ResourceConnection $resource
     * @param PersonalNotificationTypes $personalNotificationTypes
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerNotificationFactory $customerNotificationFactory,
        CustomerNotification $customerNotificationResource,
        NotificationCollection $collectionFactory,
        ResourceConnection $resource,
        PersonalNotificationTypes $personalNotificationTypes,
    ) {
        $this->personalNotificationTypes = $personalNotificationTypes;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        parent::__construct($context, $resultPageFactory, $customerNotificationFactory, $customerNotificationResource);
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
        $notificationModel = $this->customerNotificationFactory->create();
        $customerNotificationResource = $this->customerNotificationResource->load($notificationModel,$notificationId);
        $personalTypes = $this->personalNotificationTypes->getList();
        if (!in_array($notificationModel['notification_type'], $personalTypes)) {
            $collectionData = $this->collectionFactory->create()->addFieldToFilter('id', $notificationModel['notification_id'])->getFirstItem()->getData();
            if(!empty($collectionData)){
                $resultRedirect->setPath($collectionData['url_key']);
                if(!$notificationModel->getStatus()){
                    $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
                    $customerNotificationResource->save($notificationModel);
                }
                return $resultRedirect;
            }else{
                $resultRedirect->setUrl('/');
                return $resultRedirect;
            }
        }
        if($notificationModel['notification_type'] == 'order_status_update' || $notificationModel['notification_type'] == 'return_exchange'){
                $url = $this->_url->getUrl('sales/parentOrder/history');
                if($childId = $notificationModel->getOrderId()){
                    $hotaiParentOrderNumber = $this->getParentOrderInfo($childId);
                    if($hotaiParentOrderNumber){
                        $url = $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
                        $resultRedirect->setUrl($url);
                        if(!$notificationModel->getStatus()){
                            $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
                            $this->customerNotificationResource->save($notificationModel);
                        }
                        return $resultRedirect;
                    }
                }
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if($notificationModel['notification_type'] == 'review_reminders' && !empty($notificationModel['order_id'])){
            $url = $this->_url->getUrl('sales/parentOrder/history');
            if($childId = $notificationModel->getOrderId()){
                $hotaiParentOrderNumber = $this->getParentOrderInfo($childId);
                if($hotaiParentOrderNumber){
                    $url = $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
                    $resultRedirect->setUrl($url);
                    if(!$notificationModel->getStatus()){
                        $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
                        $this->customerNotificationResource->save($notificationModel);
                    }
                    return $resultRedirect;
                }
            }
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if($notificationModel['notification_type'] == 'spin_to_win' && !empty($notificationModel['segment_report_id'])){
            $url = $this->_url->getUrl('spintowin/notification/view', ['smrpid' => $notificationModel['segment_report_id']]);
            if(!$notificationModel->getStatus()){
                $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
                $this->customerNotificationResource->save($notificationModel);
            }

            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if($notificationModel['notification_type'] == 'automated_reward' && !empty($notificationModel['ars_report_id'])){
            $url = $this->_url->getUrl('ars/notification/view', ['rid' => $notificationModel['ars_report_id']]);
            if(!$notificationModel->getStatus()){
                $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
                $this->customerNotificationResource->save($notificationModel);
            }

            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        $url = $notificationModel['redirect_url'];
        if(!$notificationModel->getStatus()){
            $notificationModel->setData('status',CustomerNotificationModel::STATUS_READ);
            $this->customerNotificationResource->save($notificationModel);
        }
        $resultRedirect->setPath($url);
        return $resultRedirect;
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
     * @return AdapterInterface|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }
        return $this->connection;
    }
}
