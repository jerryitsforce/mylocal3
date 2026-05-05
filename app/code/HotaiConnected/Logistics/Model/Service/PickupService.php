<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Service;

use HotaiConnected\Logistics\Api\PickupServiceInterface;
use HotaiConnected\Logistics\Model\LogisticsWaybillFactory;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill as LogisticsWaybillResource;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill\CollectionFactory;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\CollectionFactory as SettingsCollectionFactory;
use HotaiConnected\Logistics\Model\Data\PickupResponseFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Main Pickup Service Implementation
 */
class PickupService implements PickupServiceInterface
{
    /**
     * @var LogisticsWaybillFactory
     */
    protected $waybillFactory;

    /**
     * @var LogisticsWaybillResource
     */
    protected $waybillResource;

    /**
     * @var CollectionFactory
     */
    protected $waybillCollectionFactory;

    /**
     * @var SettingsCollectionFactory
     */
    protected $settingsCollectionFactory;

    /**
     * @var PickupResponseFactory
     */
    protected $pickupResponseFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var PickupServiceFactory
     */
    protected $pickupServiceFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LogisticsWaybillFactory $waybillFactory
     * @param LogisticsWaybillResource $waybillResource
     * @param CollectionFactory $waybillCollectionFactory
     * @param SettingsCollectionFactory $settingsCollectionFactory
     * @param PickupResponseFactory $pickupResponseFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param PickupServiceFactory $pickupServiceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        LogisticsWaybillFactory $waybillFactory,
        LogisticsWaybillResource $waybillResource,
        CollectionFactory $waybillCollectionFactory,
        SettingsCollectionFactory $settingsCollectionFactory,
        PickupResponseFactory $pickupResponseFactory,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        PickupServiceFactory $pickupServiceFactory,
        LoggerInterface $logger
    ) {
        $this->waybillFactory = $waybillFactory;
        $this->waybillResource = $waybillResource;
        $this->waybillCollectionFactory = $waybillCollectionFactory;
        $this->settingsCollectionFactory = $settingsCollectionFactory;
        $this->pickupResponseFactory = $pickupResponseFactory;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->pickupServiceFactory = $pickupServiceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createPickup($orderId, $sellerId, $carrier, array $itemIds)
    {
        try {
            // 1. Validate order ownership
            $order = $this->orderRepository->get($orderId);

            // 2. Get logistics settings
            $settings = $this->getLogisticsSettings($sellerId, $carrier);
            if (!$settings || !$settings->getId()) {
                throw new \Exception(__('找不到物流設定，請先設定物流商資訊'));
            }

            // 3. Get order items
            $orderItems = [];
            foreach ($itemIds as $itemId) {
                $orderItem = $this->orderItemRepository->get($itemId);
                if ($orderItem->getOrderId() != $orderId) {
                    throw new \Exception(__('訂單商品不匹配'));
                }
                $orderItems[] = $orderItem;
            }

            // 3.5. Check if items already have waybills
            $existingWaybills = $this->checkExistingWaybills($itemIds);
            if (!empty($existingWaybills)) {
                $alreadyPickedItems = array_column($existingWaybills, 'order_item_id');
                throw new \Exception(__('以下訂單項目已經取號過：%1', implode(', ', $alreadyPickedItems)));
            }

            // 4. Call specific logistics service
            $logisticsService = $this->pickupServiceFactory->create($carrier);
            $apiResponse = $logisticsService->requestPickup($order, $orderItems, $settings);

            // 5. Save waybill records (only on success) and check for failures
            $waybills = [];
            $successCount = 0;
            $failedCount = 0;
            $errorMessages = [];

            foreach ($apiResponse as $responseItem) {
                // Decode status JSON to check success
                $statusData = isset($responseItem['status']) ? json_decode($responseItem['status'], true) : null;
                $statusCode = $statusData['code'] ?? 'unknown';

                // Check status before saving - only save successful waybills
                if ($statusCode === 'success') {
                    $waybill = $this->saveWaybill($responseItem, $sellerId, $carrier, $settings->getId());
                    $waybills[] = $waybill;
                    $successCount++;
                } else {
                    // For failed items, just count and collect error messages without saving
                    $failedCount++;
                    // Extract error message from status or response
                    if (!empty($statusData['message'])) {
                        $errorMessages[] = $statusData['message'];
                    } elseif (isset($responseItem['memo']['ErrMsg']) && $responseItem['memo']['ErrMsg']) {
                        $errorMessages[] = $responseItem['memo']['ErrMsg'];
                    } elseif (isset($responseItem['ErrMsg']) && $responseItem['ErrMsg']) {
                        $errorMessages[] = $responseItem['ErrMsg'];
                    }
                }
            }

            // 6. Create response
            $response = $this->pickupResponseFactory->create();

            if ($failedCount === 0) {
                // All success
                $response->setSuccess(true);
                $response->setWaybills($waybills);
                $response->setMessage(__('取號成功，共 %1 筆', $successCount));
            } elseif ($successCount === 0) {
                // All failed
                $response->setSuccess(false);
                $response->setWaybills($waybills);
                $errorMsg = !empty($errorMessages) ? implode(', ', array_unique($errorMessages)) : __('取號失敗');
                $response->setErrorMessage($errorMsg);
                $response->setMessage(__('取號失敗：%1', $errorMsg));
            } else {
                // Partial success
                $response->setSuccess(true);
                $response->setWaybills($waybills);
                $errorMsg = !empty($errorMessages) ? implode(', ', array_unique($errorMessages)) : '';
                $response->setMessage(__('部分成功：成功 %1 筆，失敗 %2 筆。錯誤：%3', $successCount, $failedCount, $errorMsg));
            }

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Pickup service error: ' . $e->getMessage());

            $response = $this->pickupResponseFactory->create();
            $response->setSuccess(false);
            $response->setErrorMessage($e->getMessage());
            $response->setMessage(__('取號失敗'));

            return $response;
        }
    }

    /**
     * @inheritDoc
     */
    public function getPickupsByOrderId($orderId)
    {
        $collection = $this->waybillCollectionFactory->create();

        // Get all order item IDs for this order
        $order = $this->orderRepository->get($orderId);
        $itemIds = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $itemIds[] = $item->getId();
        }

        $collection->addFieldToFilter('sales_order_item_id', ['in' => $itemIds]);

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getPickupByWaybillNumber($waybillNumber)
    {
        $collection = $this->waybillCollectionFactory->create();
        $collection->addFieldToFilter('waybill_number', $waybillNumber);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }

    /**
     * Get logistics settings for seller and carrier
     *
     * @param int $sellerId
     * @param string $carrier
     * @return \HotaiConnected\Logistics\Model\LogisticsSettings|null
     */
    protected function getLogisticsSettings($sellerId, $carrier)
    {
        $collection = $this->settingsCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId)
                   ->addFieldToFilter('logistics_company_name', $carrier)
                   ->addFieldToFilter('is_active', 1);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }

    /**
     * Save waybill record
     *
     * @param array $responseItem
     * @param int $sellerId
     * @param string $carrier
     * @param int $settingsId
     * @return \HotaiConnected\Logistics\Model\LogisticsWaybill
     */
    protected function saveWaybill($responseItem, $sellerId, $carrier, $settingsId)
    {
        $waybill = $this->waybillFactory->create();
        $waybill->setData([
            'sales_order_item_id' => $responseItem['order_item_id'],
            'seller_id' => $sellerId,
            'logistics_company_id' => $responseItem['logistics_company_id'] ?? $carrier,
            'logistics_settings_id' => $settingsId,
            'waybill_number' => $responseItem['waybill_number'],
            'tracking_number' => $responseItem['tracking_number'],
            'image' => $responseItem['image'] ?? null,
            'status' => $responseItem['status'],
            'memo' => json_encode($responseItem['memo'] ?? $responseItem)
        ]);

        $this->waybillResource->save($waybill);

        return $waybill;
    }

    /**
     * Check if order items already have waybills
     *
     * @param array $itemIds
     * @return array
     */
    protected function checkExistingWaybills(array $itemIds)
    {
        $collection = $this->waybillCollectionFactory->create();
        $collection->addFieldToFilter('sales_order_item_id', ['in' => $itemIds]);

        $existingWaybills = [];
        foreach ($collection as $waybill) {
            $existingWaybills[] = [
                'order_item_id' => $waybill->getSalesOrderItemId(),
                'waybill_number' => $waybill->getWaybillNumber()
            ];
        }

        return $existingWaybills;
    }
}
