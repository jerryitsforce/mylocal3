<?php

namespace Branch8\Marketplace\Block\Page;

use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Webkul\Marketplace\Model\ResourceModel\Notification\CollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrderColl;

class Notifications extends \Webkul\Marketplace\Block\Page\Notifications{

    protected $customNotificationFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CollectionFactory $collectionFactory,
        HelperData $helperData,
        NotificationHelper $notificationHelper,
        OrderColl $orderColl,
        \Branch8\Marketplace\Model\CustomNotificationFactory $customNotificationFactory,
        array $data = []
    ){
        parent::__construct($context, $collectionFactory, $helperData, $notificationHelper, $orderColl, $data);
        $this->customNotificationFactory = $customNotificationFactory;
    }

    public function getAllNotificationCount()
    {
        $sellerId = $this->helperData->getCustomerId();
        $orderGridFlat = $this->orderColl->getTable('sales_order_grid');
        $ids = $this->notificationHelper->getAllNotificationIds($sellerId);
        $collectionData = $this->collectionFactory->create()
            ->addFieldToFilter(
                'entity_id',
                ["in" => $ids]
            );
//        $collectionData->getSelect()->join(
//            $orderGridFlat.' as ogf',
//            'main_table.notification_row_id = ogf.entity_id',
//        )->where(
//            'ogf.order_approval_status = 1'
//        );

        return count($collectionData);
    }

    /**
     * Get All notifications .
     *
     * @return array
     */
    public function getAllNotification()
    {
        $sellerId = $this->helperData->getCustomerId();
        $orderGridFlat = $this->orderColl->getTable('sales_order_grid');
        $ids = $this->notificationHelper->getAllNotificationIds($sellerId);
        $collectionData = $this->collectionFactory->create()
            ->addFieldToFilter(
                'entity_id',
                ["in" => $ids]
            );
        $collectionData->setOrder('main_table.created_at', 'DESC');
        $collectionData->setPageSize(5) ->setCurPage(1);
//        $collectionData->getSelect()->join(
//            $orderGridFlat.' as ogf',
//            'main_table.notification_row_id = ogf.entity_id',
//        )->where(
//            'ogf.order_approval_status = 1'
//        );
        $collectionData->setPageSize(5) ->setCurPage(1);
        return $collectionData;
    }

    public function getNotificationInfo($rowData)
    {
        $message = '';
        if (empty($rowData)) {
            return $message;
        }
        $timeArr = $this->notificationHelper->getCalculatedTimeDigits(
            $rowData['created_at']
        );
        $type = $timeArr[0];
        $timedigit = $timeArr[1];
        $time = $timeArr[2];
        if ($rowData['type'] == Notification::TYPE_PRODUCT) {
            $productNotificationDesc = $this->notificationHelper->getProductNotificationDesc(
                $rowData['notification_row_id']
            );
            $url = $this->getUrl(
                'marketplace/product/edit',
                [
                    "id" => $rowData['notification_row_id']
                ]
            );
            $message = '<li class="wk-mp-notification-row wk-mp-dropdown-notification-products">
                <a
                href="'.$url.'"
                class="wk-mp-notification-entry-description-start"
                title="'.__("View Product").'">
                    <span>'.$productNotificationDesc.'</span>
                </a>
                <small class="wk-mp-notification-time">'.$time.'</small>
            </li>';
        } elseif ($rowData['type'] == Notification::TYPE_ORDER) {
            $order = $this->notificationHelper->getOrder($rowData['notification_row_id']);
            $id = $order->getIncrementId();
            $status = $order->getFrontendStatusLabel();
            $orderClass = "wk-mp-order-notification-".$order->getState();
            $url = $this->getUrl(
                "marketplace/order/view",
                [
                    "id" => $rowData['notification_row_id']
                ]
            );
            $message = '<li class="wk-mp-notification-row wk-mp-dropdown-notification-orders '.$orderClass.'">
                <a
                href="'.$url.'"
                class="wk-mp-notification-entry-description-start"
                title="'.__("View Order").'">
                    <span>'.__("Order #%1 is %2.", $id, $status).'</span>
                </a>
                <small class="wk-mp-notification-time">'.$time.'</small>
            </li>';
        } elseif ($rowData['type'] == Notification::TYPE_TRANSACTION) {
            $transactionNotificationDesc = $this->notificationHelper->getTransactionNotifyDesc(
                $rowData['notification_id']
            );
            $url = $this->getUrl(
                'marketplace/transaction/view',
                [
                    "id" => $rowData['notification_id']
                ]
            );
            $message = '<li class="wk-mp-notification-row wk-mp-dropdown-notification-transaction">
                <a
                href="'.$url.'"
                class="wk-mp-notification-entry-description-start"
                title="'.__("View Transaction").'">
                    <span>'.$transactionNotificationDesc.'</span>
                </a>
                <small class="wk-mp-notification-time">'.$time.'</small>
            </li>';
        } elseif ($rowData['type'] == Notification::TYPE_REVIEW) {
            $reviewNotification = $this->notificationHelper->getReviewNotificationDesc(
                $rowData['notification_id']
            );
            $reviewNotificationDesc = $this->escapeHtml($reviewNotification['desc']);
            $reviewClass = $this->escapeHtml($reviewNotification['feedsClass']) ;
            $url = $this->getUrl(
                'marketplace/account/review/'
            );
            $message = '<li class="wk-mp-notification-row wk-mp-dropdown-notification-review '.$reviewClass.'">
                <a
                href="'.$url.'"
                class="wk-mp-notification-entry-description-start"
                title="'.__("View Review").'">
                    <span>'.$reviewNotificationDesc.'</span>
                </a>
                <small class="wk-mp-notification-time">'.$time.'</small>
            </li>';
        }else if ($rowData['type'] == \Webkul\Marketplace\Model\Notification::TYPE_CUSTOM) {
            $notificationDetail = $this->customNotificationFactory->create()->load($rowData['notification_id']);
            $url = $notificationDetail['url'];
            $description = $notificationDetail['description'];
            $message = '<li class="wk-mp-notification-row wk-mp-dropdown-notification-custom">
                <span>'.$description.'</span>
                <small class="wk-mp-notification-time">'.$time.'</small>
            </li>';
            return $message;
        }
        return $message;
    }

}