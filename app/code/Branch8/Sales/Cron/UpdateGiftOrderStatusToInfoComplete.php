<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Exception;
use \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use \Branch8\Sales\Helper\Order\UpdateOrderStatus;

class UpdateGiftOrderStatusToInfoComplete
{

    const LOG_PATH = 'Sales/UpdateGiftOrderStatusToInfoComplete';

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory */
    protected $parentOrderCollectionFactory;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory $parentOrderDetailFactory */
    protected $parentOrderDetailFactory;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    public function __construct(
        CollectionFactory $parentOrderCollectionFactory,
        HotaiCoreCommon $hotaiCoreCommon,
        ParentOrderDetailFactory $parentOrderDetailFactory,
        UpdateOrderStatus $updateOrderStatus,

    ) {
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->hotaiCoreCommon              = $hotaiCoreCommon;
        $this->parentOrderDetailFactory     = $parentOrderDetailFactory;
        $this->updateOrderStatus            = $updateOrderStatus;
    }

    public function execute()
    {
        try {

            // info confirmed but still pending
            $collection = $this->getCheckOrderCollectionList();

            foreach ($collection as $parentOrder) {
                //If there is no increment id then return
                if (! $parentOrder->getIncrementId()) {
                    continue;
                }

                //log parent order id
                $this->hotaiCoreCommon->writeLog(
                    "[ParentOrderId] " . $parentOrder->getIncrementId() . " -- update to gift_info_complete",
                    self::LOG_PATH
                );

                $parentOrder = $this->parentOrderDetailFactory->create()->load(
                    $parentOrder->getParentId(),
                    'parent_id'
                );

                $parentOrder
                    ->setStatus(HotaiStatus::STATUS_GIFT_INFO_COMPLETE)
                    ->save();

                $subOrders = $parentOrder->getSubOrders();
                
                foreach ($subOrders as $order) {
                    $order
                        ->setStatus(HotaiStatus::STATUS_GIFT_INFO_COMPLETE)
                        ->addStatusHistoryComment(
                            __('Cron Update Gift Order Status to #%1.', HotaiStatus::STATUS_GIFT_INFO_COMPLETE))
                        ->save();

                    $this->updateItems($order);

                }

            }
        } catch (Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                $e->getMessage(),
                self::LOG_PATH
            );

        }

    }

    /**
     * getCheckOrderCollectionList 取得需要變更狀態的訂單
     *
     * @return
     */
    private function getCheckOrderCollectionList()
    {
        $collection = $this->parentOrderCollectionFactory->create()
            ->joinDetail(
                [
                    '*',
                ]
            )->addFieldToFilter(
            'detail.is_gift_order', true
        )->addFieldToFilter(
            'detail.is_gift_confirmed', true
        )->addFieldToFilter(
            'detail.status', HotaiStatus::STATUS_GIFT_INFO_PENDING
        );

        return $collection;
    }

    private function updateItems($order)
    {
        foreach ($order->getAllVisibleItems() as $item) {
            $item->setFlowStatus(HotaiStatus::STATUS_GIFT_INFO_COMPLETE);
            $item->save();

            $this->updateOrderStatus->addItemStatusRecord(
                $order->getId(),
                $item,
                HotaiStatus::STATUS_GIFT_INFO_COMPLETE
            );
        }
    }

}