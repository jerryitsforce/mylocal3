<?php

namespace Branch8\CustomNotification\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\CustomNotification\Block\Notification;
class LoadMore extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Notification
     */
    protected $notificationBlock;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Notification $notificationBlock
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Notification $notificationBlock
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->notificationBlock = $notificationBlock;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $page = (int) $this->getRequest()->getParam('noti-page', 1);
            $pageSize = (int) $this->getRequest()->getParam('pageSize', 10); // Default page size
            $type = (int) $this->getRequest()->getParam('mobile-type'); // Default page size

            $this->notificationBlock->setData('page_size', $pageSize);
            $this->notificationBlock->setData('current_page', $page);

            $notifications = $this->notificationBlock->getNotificationByCondition(null);

            if (!$notifications) {
                return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more notifications.')]);
            }

            $html = '';
            foreach ($notifications as $notification) {
                $html .= $this->notificationBlock->getLayout()
                    ->createBlock(Notification::class)
                    ->setTemplate('Branch8_CustomNotification::customer/notification_item.phtml')
                    ->setData('notification', $notification)
                    ->toHtml();
            }

            $totalCount = $this->notificationBlock->getCountAll();
            if ($type == 2) {
                $totalCount = $this->notificationBlock->getCountPersonal();
            }
            $hasMore = ($totalCount > ($page * $pageSize));

            return $resultJson->setData([
                'success' => true,
                'html' => $html,
                'has_more' => $hasMore
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
