<?php

namespace Branch8\Marketplace\Plugin;

use Webkul\Marketplace\Model\Notification as ModelNotification;
use Webkul\Marketplace\Model\ResourceModel\Notification\Collection as NotificationColl;
use Webkul\Marketplace\Model\ResourceModel\Notification\CollectionFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;

class MarketplaceCustomNotification{
    /**
     * @var NotificationColl
     */
    protected $notificationColl;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param NotificationColl $notificationColl
     * @param CollectionFactory $collectionFactory
     * @param MarketplaceLogger $marketplaceLogger
     */
    public function __construct(
        NotificationColl $notificationColl,
        CollectionFactory $collectionFactory,
        MarketplaceLogger $marketplaceLogger
    ){
        $this->notificationColl = $notificationColl;
        $this->collectionFactory = $collectionFactory;
        $this->marketplaceLogger = $marketplaceLogger;
    }
    public function afterGetAllNotificationIds($subject, $result, $sellerId){
        try {
            $marketplaceCustom = $this->notificationColl->getTable(
                'marketplace_custom_notification'
            );
            $collectionDataCustom = $this->collectionFactory->create()
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
            $collectionDataCustom->getSelect()->join(
                $marketplaceCustom . ' as mr',
                'main_table.notification_id = mr.entity_id'
            )->where(
                'mr.seller_pending_notification = 1 AND main_table.type = ' . \Webkul\Marketplace\Model\Notification::TYPE_CUSTOM
            );
            $idsCustom = $collectionDataCustom->getAllIds();
            $result = array_merge($result, $idsCustom);
        }catch (\Exception $e) {
            $ids = [];
            $this->marketplaceLogger->logException('MarketplaceCustomNotification', $e, [
                'seller_id' => $sellerId,
            ]);
        }
        return $result;
    }

}