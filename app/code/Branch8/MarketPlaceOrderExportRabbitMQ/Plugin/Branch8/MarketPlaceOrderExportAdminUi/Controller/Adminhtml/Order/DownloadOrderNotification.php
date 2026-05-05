<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Plugin\Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order;

use Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order\DownloadOrderNotification as MainDownloadOrderNotification;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\NotificationConfig;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileNotification;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileNotificationFactory;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Notification\Publish;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification as ProfileNotificationResource;
use Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\Manager;
use Webkul\Marketplace\Helper\Data as HelperData;

class DownloadOrderNotification extends MainDownloadOrderNotification
{
    /**
     * @var HelperData
     */
    protected $helper;
    private NotificationConfig $config;
    private ProfileNotificationFactory $profileFactory;
    private ProfileNotificationResource $profileResource;
    private Publish $publish;
    private Session $authSession;
    protected OrderDailyNotificationHelper $orderDailyNotificationHelper;
    private RedirectFactory $redirectFactory;

    /**
     * @param HelperData $helper
     * @param NotificationConfig $config
     * @param Publish $publish
     * @param ProfileNotificationResource $profileResource
     * @param ProfileNotificationFactory $profileFactory
     * @param Manager $messageManager
     * @param Session $authSession
     * @param OrderDailyNotificationHelper $orderDailyNotificationHelper
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        HelperData $helper,
        NotificationConfig $config,
        Publish $publish,
        ProfileNotificationResource $profileResource,
        ProfileNotificationFactory $profileFactory,
        Manager $messageManager,
        Session $authSession,
        OrderDailyNotificationHelper $orderDailyNotificationHelper,
        RedirectFactory $resultRedirectFactory
    ){
        $this->helper = $helper;
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->publish = $publish;
        $this->authSession = $authSession;
        $this->orderDailyNotificationHelper = $orderDailyNotificationHelper;
        $this->redirectFactory = $resultRedirectFactory;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute($subject, $proceed)
    {
        if (!$this->config->enable()) {
            return $proceed();
        }
        $sellerId = $this->helper->getCustomerId();
        $countOrderData = $this->orderDailyNotificationHelper->getOrderDataForExcel($sellerId, false);
        $threshold = $this->config->getThreshold();
        if ($countOrderData <= $threshold) {
            return $proceed();
        }
        $user = $this->authSession->getUser();
        $profile = $this->profileFactory->create();
        $profile->setSellerId((int)$sellerId)->setProfileType(
            ProfileNotification::TYPE_ADMIN
        )->setUserId((int)$user->getId())
            ->setReceiverName($user->getName())
            ->setReceiverEmail($user->getEmail());
        $this->profileResource->save($profile);
        $this->publish->execute($profile);
        $this->messageManager->addSuccessMessage(__('File is being prepared, once completed, it will be emailed to your inbox (%1), please pay attention to the email.', $user->getEmail()));
        $redirect = $this->redirectFactory->create();
        return $redirect->setPath('*/*/');
    }
}
