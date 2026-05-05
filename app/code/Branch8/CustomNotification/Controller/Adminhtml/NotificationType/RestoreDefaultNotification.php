<?php

namespace Branch8\CustomNotification\Controller\Adminhtml\NotificationType;

use Magenest\NotificationBox\Controller\Adminhtml\NotificationType\RestoreDefaultNotification as BaseRestoreDefaultNotification;
use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magenest\NotificationBox\Model\NotificationTypeFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType;
use Magenest\NotificationBox\Model\ResourceModel\NotificationType\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;

class RestoreDefaultNotification extends BaseRestoreDefaultNotification
{

    /** @var Helper  */
    private $helper;

    public function __construct(Action\Context $context, NotificationTypeFactory $notificationTypeFactory, NotificationType $notificationType, Json $serialize, CollectionFactory $collectionFactory, StoreManagerInterface $storeManagerInterface, Helper $helper)
    {
        $this->helper = $helper;
        parent::__construct($context, $notificationTypeFactory, $notificationType, $serialize, $collectionFactory, $storeManagerInterface, $helper);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $currentStore = $this->storeManagerInterface->getStore();
            $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . self::URL_ICON;
            $listDefaultImage = $this->helper->getDefaultImage();

            // Add new notification types to the list
            $listDefaultNotificationType = [
                NotificationModel::ABANDONED_CART_REMINDS => NotificationModel::ABANDONED_CART_REMINDS,
                NotificationModel::REVIEW_REMINDERS => NotificationModel::REVIEW_REMINDERS,
                NotificationModel::ORDER_STATUS_UPDATE => NotificationModel::ORDER_STATUS_UPDATE,
                'news' => 'news',
                'return_exchange' => 'return_exchange'
            ];

            $listExistDefaultNotificationType = $this->collectionFactory->create()
                ->addFieldToFilter('default_type', ['in' => array_keys($listDefaultNotificationType)]);

            foreach ($listExistDefaultNotificationType as $notificationType) {
                unset($listDefaultNotificationType[$notificationType->getDefaultType()]);
            }

            $totalRestore = 0;
            foreach ($listDefaultNotificationType as $notificationType) {
                if ($notificationType == NotificationModel::REVIEW_REMINDERS) {
                    $this->addReviewReminderNotificationType($mediaUrl, $listDefaultImage);
                    $totalRestore++;
                } elseif ($notificationType == NotificationModel::ORDER_STATUS_UPDATE) {
                    $this->addOrderStatusUpdateNotificationType($mediaUrl, $listDefaultImage);
                    $totalRestore++;
                } elseif ($notificationType == NotificationModel::ABANDONED_CART_REMINDS) {
                    $this->addAbandonedCartNotificationType($mediaUrl, $listDefaultImage);
                    $totalRestore++;
                } elseif ($notificationType == 'news') {
                    $this->addNewsNotificationType($mediaUrl);
                    $totalRestore++;
                } elseif ($notificationType == 'return_exchange') {
                    $this->addReturnExchangeNotificationType($mediaUrl);
                    $totalRestore++;
                }
            }

            $this->messageManager->addSuccessMessage(__('Total of %1 record(s) have been restored.', $totalRestore));

        } catch (\Exception $exception) {
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
            'is_personal' => 1,
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
            'is_personal' => 1,
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
            'is_personal' => 1,
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
     * Add the News Notification Type
     *
     * @param string $mediaUrl
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addNewsNotificationType($mediaUrl)
    {
        $data = [
            'name' => 'News',
            'description' => 'News Notifications',
            'is_category' => 1,
            'default_type' => 'news',
            'icon' => '[{
                            "name": "news",
                            "type": "image/svg+xml",
                            "url": "' . $mediaUrl . '/news.svg",
                            "size":"1024"
                }]'
        ];
        $this->saveNotificationType($data);
    }

    /**
     * Add the Return/Exchange Notification Type
     *
     * @param string $mediaUrl
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function addReturnExchangeNotificationType($mediaUrl)
    {
        $data = [
            'name' => 'Return/Exchange',
            'description' => 'Return or Exchange',
            'is_category' => 1,
            'is_personal' => 1,
            'default_type' => 'return_exchange',
            'icon' => '[{
                            "name": "return_exchange",
                            "type": "image/png",
                            "url": "' . $mediaUrl . '/return-exchange.png",
                            "size":"1500"
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
}
