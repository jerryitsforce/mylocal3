<?php
namespace Magenest\NotificationBox\Controller\Adminhtml\NotificationType;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType as NotificationTypeResource;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\CollectionFactory;
use \Magento\Backend\App\Action\Context;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\ResourceModel\Notification as NotificationResource;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollection;

/**
 * Class AbstractMassAction
 * @package Magenest\NotificationBox\Controller\Adminhtml\NotificationType;
 */
abstract class AbstractMassAction extends \Magento\Backend\App\Action
{

    /** @var string */
    protected $redirectUrl = '*/*/';

    /** @var Filter */
    protected $filter;

    /** @var CollectionFactory  */
    protected $collectionFactory;

    /** @var NotificationTypeFactory */
    protected $notificationTypeFactory;

    /** @var NotificationTypeResource  */
    protected $notificationTypeResource;

    /** @var Notification  */
    protected $notificationModel;

    /** @var NotificationResource  */
    protected $notificationResource;

    /** @var Collection  */
    protected $notificationCollection;

    /**
     * AbstractMassAction constructor.
     *
     * @param Notification $notification
     * @param NotificationResource $notificationResource
     * @param CollectNotificationCollectionction
     * @param CollectionFactory $collectionFactory
     * @param NotificationTypeResource $photoResource
     * @param NotificationTypeFactory $photoFactory
     * @param Filter $filter
     * @param Context $context
     */
    public function __construct(
        Notification $notification,
        NotificationResource $notificationResource,
        NotificationCollection $notificationCollection,
        CollectionFactory $collectionFactory,
        NotificationTypeResource $photoResource,
        NotificationTypeFactory $photoFactory,
        Filter $filter,
        Context $context
    ){
        $this->notificationModel = $notification;
        $this->notificationResource = $notificationResource;
        $this->notificationCollection = $notificationCollection;
        $this->collectionFactory = $collectionFactory;
        $this->notificationTypeResource = $photoResource;
        $this->notificationTypeFactory = $photoFactory;
        $this->filter = $filter;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Set status to collection items
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     */
    abstract protected function massAction(AbstractCollection $collection);

    /**
     * Check if there is notices of this type
     * @param $notificationTypeModel
     * @return int|void
     */
    public function checkAssign($notificationTypeModel){
        $notificationType = $notificationTypeModel->getDefaultType();
        if( $notificationType == Notification::ORDER_STATUS_UPDATE ||
            $notificationType == Notification::ABANDONED_CART_REMINDS ||
            $notificationType == Notification::REVIEW_REMINDERS)
        {
            $notification = $this->notificationCollection->create()->addFieldToFilter('notification_type',$notificationTypeModel->getDefaultType());
        }else{
            $notification = $this->notificationCollection->create()->addFieldToFilter('notification_type',$notificationTypeModel->getEntityId());
        }
        return count($notification);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_NotificationBox::notification_type');
    }
}
