<?php

namespace Branch8\CustomNotification\Block;

use Branch8\CustomNotification\Model\PersonalNotificationTypes;
use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magenest\NotificationBox\Model\NotificationType as NotificationTypeModel;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\CollectionFactory as NotificationTypeCollection;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Magento\Reports\Model\ResourceModel\Quote\Collection;
use Magento\Store\Model\StoreManagerInterface;

class Notification  extends \Magenest\NotificationBox\Block\Customer\Tab\Notification
{

    const NEWS_TYPE = 4;
    const Personal_Notifications = 2;
    protected $countAll;
    protected $countPersonal;
    private $personalNotificationTypes;
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    protected $isActive = 0;
    public function __construct(
        Collection $collection,
        Context $context,
        Json $serialize,
        CustomerNotificationFactory $customerNotificationModel,
        CustomerNotification $customerNotificationResource,
        CollectionFactory $collectionFactory,
        Helper $helper,
        Session $session,
        NotificationType $notificationTypeResource,
        NotificationTypeFactory $notificationTypeFactory,
        StoreManagerInterface $storeManagerInterface,
        NotificationTypeCollection $notificationTypeCollection,
        \Magento\Framework\App\Http\Context $httpContext,
        PersonalNotificationTypes $personalNotificationTypes
    ) {
        $this->httpContext = $httpContext;
        parent::__construct($collection, $context, $serialize, $customerNotificationModel,
            $customerNotificationResource, $collectionFactory, $helper, $session, $notificationTypeResource,
            $notificationTypeFactory, $storeManagerInterface, $notificationTypeCollection);
        $this->personalNotificationTypes = $personalNotificationTypes;
    }

    /**
     * @return false|int|null
     * Custom get customer Id from session
     */
    public function getCustomerId()
    {
        if ($this->httpContext->getValue('customer_id')) {
            return $this->httpContext->getValue('customer_id');
        }
        return false;
    }

    /**
     * check customer is login or not
     * If the customer is not logged in, redirect to the login page
     */
    public function redirectIfNotLoggedIn()
    {
        if (!$this->httpContext->getValue('customer_id')) {
            $this->session->setAfterAuthUrl($this->urlInterface->getCurrentUrl());
            $this->session->authenticate();
        }
    }


    public function getNotificationByCondition($condition)
    {
        if(isset($condition)){
            $allNotification =  $this->helper->getAllNotificationByCondition($condition)->getData();
        }
        else{
            $allNotification = $this->getAllCustomerNotification("all");
            if($allNotification){
                $allNotification = $allNotification->setOrder('entity_id','DESC')->getData();
            }
        }
        if(!$allNotification){
            return [];
        }
        foreach ($allNotification as $key => $notification)
        {
            $notificationModel = $this->notificationTypeFactory->create();
            if( $notification['notification_type'] == NotificationModel::ORDER_STATUS_UPDATE ||
                $notification['notification_type'] == NotificationModel::ABANDONED_CART_REMINDS ||
                $notification['notification_type'] == 'return_exchange' ||
                $notification['notification_type'] == 'news'
            ) {
                $this->notificationTypeResource->load($notificationModel,$notification['notification_type'],'default_type');
                $allNotification[$key]['notification_type'] = $notificationModel->getName();
                $allNotification[$key]['notification_type_id'] = $notification['notification_type'];
            }
            else{
                $this->notificationTypeResource->load($notificationModel,$notification['notification_type'],'entity_id');
                $allNotification[$key]['notification_type'] = $notificationModel->getName();
                $allNotification[$key]['notification_type_id'] = $notification['notification_type'];
            }
            $allNotification[$key]['icon'] =  $this->helper->getImageByNotificationType($notification);
            $allNotification[$key]['full_description'] = $notification['description'] ?? '';
            $allNotification[$key]['content'] = $notification['detail_description'] ?? '';
        }
        return $allNotification;
    }

    public function getIsPerSonalType()
    {
        $allNotificationType = $this->personalNotificationTypes->getList();
        $notificationType = $this->notificationTypeCollection->create()->addFieldToFilter('is_personal', 1);
        foreach ($notificationType as $item) {
            $allNotificationType[($item->getDefaultType() != 'null') ? $item->getDefaultType() : $item->getEntityId()] = ($item->getDefaultType() != 'null') ? $item->getDefaultType() : $item->getEntityId();
        }
        return $allNotificationType;
    }

    /**
     * @return mixed
     */
    public function getPageSize()
    {
        return $this->helper->getMaximumNotificationInMyNotificationOnMyAccountPage();
    }

    public function getAllCustomerNotification($type)
    {
        $pageLimit = $this->helper->getMaximumNotificationInMyNotificationOnMyAccountPage();

        if (!$this->notificationCollection && $customerId = $this->getCustomerId()) {
            // Fetch collection only once for displaying notifications
            $this->notificationCollection = $this->collectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId);
            if($this->getRequest()->getParam('mobile-type')){
                $type = $this->getRequest()->getParam('mobile-type');
            }
            $types = $this->getIsPerSonalType();
            // Apply filters to the main collection for display based on the type
            if ($type == self::Personal_Notifications) {
                $this->isActive = self::Personal_Notifications;
                $this->notificationCollection->addFieldToFilter('notification_type', ['in' => $types]);
            }else{
                $this->notificationCollection->addFieldToFilter('notification_type', ['nin' => $types]);
            }

            // Apply pagination
            $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
            if($this->getRequest()->getParam('noti-page')){
                $page = $this->getRequest()->getParam('noti-page');
            }
            $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : $pageLimit;
            $this->notificationCollection->setPageSize($pageSize);
            $this->notificationCollection->setCurPage($page);
        }

        return $this->notificationCollection;
    }

// Method to get the total count of all notifications
    public function getCountAll()
    {
        if ($this->countAll === null && $customerId = $this->getCustomerId()) {
            // Create a new collection to count all notifications
            $types = $this->getIsPerSonalType();
            $allNotificationsCollection = $this->collectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('notification_type', ['nin' => $types])
                ->addFieldToFilter('status',0);
            $this->countAll = $allNotificationsCollection->getSize();
        }
        return $this->countAll;
    }

// Method to get the count of personal notifications
    public function getCountPersonal()
    {
        if ($this->countPersonal === null && $customerId = $this->getCustomerId()) {
            // Create a new collection to count personal notifications
            $types = $this->getIsPerSonalType();
            $personalNotificationsCollection = $this->collectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('notification_type', ['in' => $types])
                ->addFieldToFilter('status',0);
            $this->countPersonal = $personalNotificationsCollection->getSize();
        }
        return $this->countPersonal;
    }

    public function getIsActive()
    {
        $type = $this->getFilteredNotificationTypes();
        return ($type === 'all' || $type === 1) ? 1 : $type;
    }


}
