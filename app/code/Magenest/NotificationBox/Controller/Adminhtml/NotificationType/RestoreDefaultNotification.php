<?php
namespace Magenest\NotificationBox\Controller\Adminhtml\NotificationType;

use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class RestoreDefaultNotification
 * @package Magenest\NotificationBox\Controller\Adminhtml\NotificationType
 */
class RestoreDefaultNotification extends \Magento\Backend\App\Action
{
    const URL_ICON = 'notificationtype/icon';

    /** @var NotificationType  */
    protected $notificationType;

    /** @var NotificationTypeFactory  */
    protected $notificationTypeFactory;

    /** @var Json  */
    protected $serialize;

    /** @var CollectionFactory  */
    protected $collectionFactory;

    /** @var StoreManagerInterface  */
    protected $storeManagerInterface;

    /** @var Helper  */
    private $helper;

    /**
     * @param Action\Context $context
     * @param NotificationTypeFactory $notificationTypeFactory
     * @param NotificationType $notificationType
     * @param Json $serialize
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param Helper $helper
     */
    public function __construct(
        Action\Context $context,
        NotificationTypeFactory $notificationTypeFactory,
        NotificationType $notificationType,
        Json $serialize,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManagerInterface,
        Helper $helper
        )
    {
        parent::__construct($context);
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->notificationType= $notificationType;
        $this->notificationTypeFactory = $notificationTypeFactory;
        $this->serialize = $serialize;
        $this->collectionFactory = $collectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
    }


    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try{
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $currentStore = $this->storeManagerInterface->getStore();
            $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . self::URL_ICON;
            $listDefaultImage = $this->helper->getDefaultImage();
            $listDefaultNotificationType = [
                NotificationModel::ABANDONED_CART_REMINDS => NotificationModel::ABANDONED_CART_REMINDS,
                NotificationModel::REVIEW_REMINDERS => NotificationModel::REVIEW_REMINDERS,
                NotificationModel::ORDER_STATUS_UPDATE => NotificationModel::ORDER_STATUS_UPDATE];
            $listExistDefaultNotificationType = $this->collectionFactory->create()
                ->addFieldToFilter('default_type',array("in" => array($listDefaultNotificationType)));

            foreach ($listExistDefaultNotificationType as $notificationType){
                unset($listDefaultNotificationType[$notificationType->getDefaultType()]);
            }
            $totalRestore = 0;
            foreach ($listDefaultNotificationType as $notificationType){
                if($notificationType == NotificationModel::REVIEW_REMINDERS){
                    $this->addReviewReminderNotificationType($mediaUrl,$listDefaultImage);
                    $totalRestore ++;
                }
                elseif ($notificationType == NotificationModel::ORDER_STATUS_UPDATE){
                    $this->addOrderStatusUpdateNotificationType($mediaUrl,$listDefaultImage);
                    $totalRestore ++;
                }
                elseif ($notificationType == NotificationModel::ABANDONED_CART_REMINDS){
                    $this->addAbandonedCartNotificationType($mediaUrl,$listDefaultImage);
                    $totalRestore ++;
                }
            }
            $this->messageManager->addSuccessMessage(__('Total of %1 record(s) have been restored.', $totalRestore));

        } catch (\Exception $exception){
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $mediaUrl
     * @param $listDefaultImage
     * @throws AlreadyExistsException
     */
    private function addAbandonedCartNotificationType($mediaUrl,$listDefaultImage){
        $data = [
                'name' => NotificationModel::ABANDONED_CART_REMINDS_LABEL,
                'description' => NotificationModel::ABANDONED_CART_REMINDS_LABEL,
                'is_category' => 1,
                'default_type' => NotificationModel::ABANDONED_CART_REMINDS,
                'icon' => '[{
                                "name": "'.NotificationModel::ABANDONED_CART_REMINDS.'",
                                "type": "image/png",
                                "url": "'.$mediaUrl.$listDefaultImage[NotificationModel::ABANDONED_CART_REMINDS].'",
                                "size":"1093"
                    }]'
        ];
        $this->saveNotificationType($data);
    }

    /**
     * @param $mediaUrl
     * @param $listDefaultImage
     * @throws AlreadyExistsException
     */
    private function addReviewReminderNotificationType($mediaUrl,$listDefaultImage){
        $data = [
                'name' => NotificationModel::REVIEW_REMINDERS_LABEL,
                'description' => NotificationModel::REVIEW_REMINDERS_LABEL,
                'is_category' => 1,
                'default_type' => NotificationModel::REVIEW_REMINDERS,
                'icon' => '[{
                                "name": "'.NotificationModel::REVIEW_REMINDERS.'",
                                "type": "image/png",
                                "url": "'.$mediaUrl.$listDefaultImage[NotificationModel::REVIEW_REMINDERS].'",
                                "size":"718"
                    }]'
        ];
        $this->saveNotificationType($data);
    }

    /**
     * @param $mediaUrl
     * @param $listDefaultImage
     * @throws AlreadyExistsException
     */
    private function addOrderStatusUpdateNotificationType($mediaUrl,$listDefaultImage){
        $data = [
                'name' => NotificationModel::ORDER_STATUS_UPDATE_LABEL,
                'description' => NotificationModel::ORDER_STATUS_UPDATE_LABEL,
                'is_category' => 1,
                'default_type' => NotificationModel::ORDER_STATUS_UPDATE,
                'icon' => '[{
                                "name": "'.NotificationModel::ORDER_STATUS_UPDATE.'",
                                "type": "image/png",
                                "url": "'.$mediaUrl.$listDefaultImage[NotificationModel::ORDER_STATUS_UPDATE].'",
                                "size":"1474"
                    }]'
        ];
        $this->saveNotificationType($data);
    }

    /**
     * @param $data
     * @throws AlreadyExistsException
     */
    private function saveNotificationType($data){
        $model = $this->notificationTypeFactory->create();
        $model->addData($data);
        $this->notificationType->save($model);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_NotificationBox::notification_type');
    }
}
