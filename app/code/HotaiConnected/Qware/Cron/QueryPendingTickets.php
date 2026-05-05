<?php

namespace HotaiConnected\Qware\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use HotaiConnected\Qware\Helper\Api as ApiHelper;
use HotaiConnected\Qware\Model\QwareTicketRecord;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord\CollectionFactory as QwareCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use HotaiConnected\Qware\Service\EmailNotificationService;
use Magento\Framework\DB\Transaction;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\Event\ManagerInterface as EventManager;

class QueryPendingTickets
{
    const LOG_FOLDER_NAME = 'Qware/Cron/QueryPendingTickets';
    
    const TARGET_TICKET_TYPE = VirtualProductType::TYPE_QWARE_TICKET;
    const RETRY_COUNT_LIMIT  = 3;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    /** @var QwareCollectionFactory */
    protected $qwareCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;


    /** @var Transaction */
    protected $transaction;

    /** @var CustomerTicketFactory */
    protected $customerTicketFactory;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var EmailNotificationService */
    protected $emailNotificationService;

    /** @var EventManager */
    protected $eventManager;

    protected $orderIdsForArrivedCheckerEvent = [];

    protected $mappingSNToStartTime = [];
    protected $mappingSNToEndTime = [];

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderItemRepository $orderItemRepository,
        VirtualProductHelper $virtualProductHelper,
        QwareCollectionFactory $qwareCollectionFactory,
        ApiHelper $apiHelper,
        Transaction $transaction,
        CustomerTicketFactory $customerTicketFactory,
        CustomerTicketRepository $customerTicketRepository,
        MarketplaceHelper $marketplaceHelper,
        EmailNotificationService $emailNotificationService,
        EventManager $eventManager
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->orderItemCollectionFactory      = $orderItemCollectionFactory;
        $this->orderItemRepository             = $orderItemRepository;
        $this->virtualProductHelper            = $virtualProductHelper;
        $this->qwareCollectionFactory          = $qwareCollectionFactory;
        $this->apiHelper                       = $apiHelper;
        $this->transaction                      = $transaction;
        $this->customerTicketFactory           = $customerTicketFactory;
        $this->customerTicketRepository        = $customerTicketRepository;
        $this->marketplaceHelper               = $marketplaceHelper;
        $this->emailNotificationService       = $emailNotificationService;
        $this->eventManager                    = $eventManager;
    }

    public function execute()
    {
        $this->mappingSNToStartTime = [];
        $this->mappingSNToEndTime = [];

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message" => "QueryPendingTickets cron start.",
        ]), self::LOG_FOLDER_NAME);

        // 取得所有 PENDING 狀態的訂單項目
        $itemCollection = $this->getTargetOrderItemCollection();
        $itemObjArray   = $itemCollection->getItems();
        $itemObjArray   = $this->removeNonTargetTicketItem($itemObjArray);

        if (empty($itemObjArray)) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Message" => "No pending status items found.",
            ]), self::LOG_FOLDER_NAME);
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message"        => "Ready to query pending status items.",
            "Order item IDs" => array_keys($itemObjArray)
        ]), self::LOG_FOLDER_NAME);

        /** @var OrderItem $item */
        foreach ($itemObjArray as $item) {
            $this->processOrderItem($item);
        }

        // 觸發到貨檢查事件
        if (!empty($this->orderIdsForArrivedCheckerEvent)) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Message"   => "Ready to fire arrived checker event with order IDs.",
                "Order IDs" => array_keys($this->orderIdsForArrivedCheckerEvent)
            ]), self::LOG_FOLDER_NAME);

            $this->fireArrivedCheckerEvent();
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message" => "QueryPendingTickets cron end.",
        ]), self::LOG_FOLDER_NAME);
    }

    /**
     * 取得所有 PENDING 狀態的訂單項目
     *
     * @return OrderItemCollection
     */
    protected function getTargetOrderItemCollection(): OrderItemCollection
    {
        $collection = $this->orderItemCollectionFactory->create();

        $collection->addFieldToSelect([
            'item_id',
            'order_id',
            'product_id',
            'product_options',
            'ticket_retry_status',
            'ticket_retry_count'
        ]);

        $collection->addFieldToFilter("ticket_retry_status", TicketRetryStatus::STATUS_PENDING);

        return $collection;
    }

    /**
     * 過濾出宜睿票券類型的項目
     *
     * @param array $itemObjArray
     * @return array
     */
    protected function removeNonTargetTicketItem(array $itemObjArray): array
    {
        foreach ($itemObjArray as $key => $item) {
            $type = $this->virtualProductHelper->getProductTicketTypeByOrderItemId($item->getId());

            if ($type == self::TARGET_TICKET_TYPE) {
                continue;
            }

            unset($itemObjArray[$key]);
        }

        return $itemObjArray;
    }

    /**
     * 處理單一訂單項目
     *
     * @param OrderItem $item
     * @return void
     */
    protected function processOrderItem(OrderItem $item): void
    {
        try {
            // 取得對應的 QwareTicketRecord
            $qwareRecords = $this->getQwareRecordsByOrderItemId($item->getId());
            
            if (empty($qwareRecords)) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order Item ID" => $item->getId(),
                    "Message"       => "No qware records found for this order item."
                ]), self::LOG_FOLDER_NAME);
                
                $this->updateForNoRecord($item);
                return;
            }

            /** @var QwareTicketRecord $firstRecord */
            $firstRecord = reset($qwareRecords);
            $orderNo = $firstRecord->getQwareOrderNumber();

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order Item ID" => $item->getId(),
                "Order Number"  => $orderNo,
                "Message"       => "Starting to query order."
            ]), self::LOG_FOLDER_NAME);

            $apiResponse = $this->apiHelper->requestApiQueryOrder($orderNo);
            
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order Item ID" => $item->getId(),
                "API Response Code" => $apiResponse['Code'],
                "Response Data" => $apiResponse['Data'] ?? null
            ]), self::LOG_FOLDER_NAME);

            if ($apiResponse['Code'] == 200) {
                // 訂單完成，更新票券序號
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order Item ID" => $item->getId(),
                    "Message" => "API Code 200 - Starting updateTicketRecordsWithSN"
                ]), self::LOG_FOLDER_NAME);
                
                $this->updateTicketRecordsWithSN($qwareRecords, $apiResponse);
                $this->updateForQuerySuccess($item);
                $this->addOrderIdForArrivedCheckerEvent($item);
                
            } elseif (in_array($apiResponse['Code'], [202, 208])) {
                // 202: 新訂單(取號尚未完成), 208: 取號完成(但尚未提供序號)
                $this->updateForStillPending($item);
                
            } else {
                // 其他錯誤狀態
                $this->updateForApiError($item, $apiResponse);
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order Item ID"      => $item->getId(),
                "Exception Message"  => $e->getMessage(),
                "Message"            => "Failed to process order item."
            ]), self::LOG_FOLDER_NAME);
            
            $this->updateForException($item);
        }
    }

    /**
     * 取得對應的 QwareTicketRecord
     *
     * @param int $orderItemId
     * @return array
     */
    protected function getQwareRecordsByOrderItemId(int $orderItemId): array
    {
        $collection = $this->qwareCollectionFactory->create();
        $collection->addFieldToFilter(QwareTicketRecord::SALES_ORDER_ITEM_ID, $orderItemId);
        
        return array_values($collection->getItems());
    }

    /**
     * 找不到記錄的處理
     *
     * @param OrderItem $item
     * @return void
     */
    protected function updateForNoRecord(OrderItem $item): void
    {
        $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RECORD_EXISTS_ERROR);
        $this->orderItemRepository->save($item);
    }

    /**
     * 查詢成功的處理
     *
     * @param OrderItem $item
     * @return void
     */
    protected function updateForQuerySuccess(OrderItem $item): void
    {
        $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RETRY_SUCCESS);
        $this->orderItemRepository->save($item);
    }

    /**
     * 仍在等待中的處理
     *
     * @param OrderItem $item
     * @return void
     */
    protected function updateForStillPending(OrderItem $item): void
    {
        $oldCount = $item->getData('ticket_retry_count') ?? 0;
        $newCount = $oldCount + 1;

        if ($newCount >= self::RETRY_COUNT_LIMIT) {
            $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RETRY_LIMIT_ERROR);
            
            // 發送郵件通知
            try {
                $this->emailNotificationService->sendCronRetryFailedNotification(
                    'QueryPendingTickets',
                    $item->getId(),
                    'Query pending tickets retry failed after ' . self::RETRY_COUNT_LIMIT . ' attempts'
                );
            } catch (\Exception $e) {
                // 記錄郵件發送失敗，但不影響主流程
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    'Title' => 'Failed to send email notification for QueryPendingTickets',
                    'OrderItemId' => $item->getId(),
                    'Error' => $e->getMessage()
                ]), self::LOG_FOLDER_NAME);
            }
        }

        $item->setData('ticket_retry_count', $newCount);
        $this->orderItemRepository->save($item);
    }

    /**
     * API 錯誤的處理
     *
     * @param OrderItem $item
     * @param array $apiResponse
     * @return void
     */
    protected function updateForApiError(OrderItem $item, array $apiResponse): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Order Item ID" => $item->getId(),
            "API Response"  => $apiResponse,
            "Message"       => "API returned error code."
        ]), self::LOG_FOLDER_NAME);
        
        $this->updateForStillPending($item);
    }

    /**
     * 例外處理
     *
     * @param OrderItem $item
     * @return void
     */
    protected function updateForException(OrderItem $item): void
    {
        $this->updateForStillPending($item);
    }

    /**
     * 更新票券記錄的序號並查詢詳細資訊
     *
     * @param array $records
     * @param array $apiResponse
     * @return void
     */
    protected function updateTicketRecordsWithSN(array $records, array $apiResponse): void
    {

        if (!isset($apiResponse['Data']) || !is_array($apiResponse['Data'])) {
            throw new \Exception("Invalid API response format: " . json_encode($apiResponse));
        }

        /** @var QwareTicketRecord $firstRecord */
        $firstRecord = reset($records);
        $productGuid = $firstRecord->getQwareProductGuid();
        $vouchers = $apiResponse['Data'][$productGuid] ?? [];
        
        if (empty($vouchers)) {
            throw new \Exception("No vouchers found for GUID: " . $productGuid);
        }

        // 更新每筆記錄的SN

        foreach ($records as $index => $record) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "updateTicketRecordsWithSN - Processing record",
                "Index" => $index,
                "Record ID" => $record->getId(),
                "Voucher Exists" => isset($vouchers[$index]),
                "Voucher Value" => $vouchers[$index] ?? null
            ]), self::LOG_FOLDER_NAME);

            if (isset($vouchers[$index])) {
                $record->setQwareSn($vouchers[$index]);
                
                $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $record->getMemo(),
                    [
                        "Title"                    => "Updated SN from Query Order API.",
                        "Query Order API response" => $apiResponse,
                    ]
                );
                $record->setMemo($memo);
                
                // 將狀態從 STATUS_IMPORTED (0) 更新為 STATUS_UNUSED (2)
                $record->setStatus(QwareTicketRecord::STATUS_UNUSED);
                
                // 直接保存記錄
                try {
                    $record->save();
                } catch (\Exception $saveException) {
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        "Record ID" => $record->getId(),
                        "Exception" => $saveException->getMessage(),
                        "SN" => $vouchers[$index]
                    ]), self::LOG_FOLDER_NAME);
                    throw $saveException;
                }
            }
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "updateTicketRecordsWithSN - Record loop completed",
            "Records Processed" => count($records)
        ]), self::LOG_FOLDER_NAME);

        // 查詢票券詳細資訊 (URL 和密碼)
        $this->updateTicketDetailsFromApi($records);

        // 建立對應的 customer_ticket 記錄
        $this->createCustomerTicketRecords($records);

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "updateTicketRecordsWithSN - COMPLETED",
            "Order Number" => $firstRecord->getQwareOrderNumber(),
            "Updated records" => count($records),
            "Message" => "Successfully updated ticket records with SN and details, and created customer ticket records."
        ]), self::LOG_FOLDER_NAME);
    }

    /**
     * 建立對應的 customer_ticket 記錄
     *
     * @param array $records
     * @return void
     */
    protected function createCustomerTicketRecords(array $records): void
    {
        if (empty($records)) {
            return;
        }

        /** @var QwareTicketRecord $firstRecord */
        $firstRecord = reset($records);
        $orderItemId = $firstRecord->getSalesOrderItemId();
        
        // 透過 order item 取得相關資訊
        $orderItem = $this->orderItemRepository->get($orderItemId);
        $order = $orderItem->getOrder();
        $product = $orderItem->getProduct();
        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());

        foreach ($records as $qwareTicketRecord) {
            /** @var CustomerTicket $customerTicket */
            $customerTicket = $this->customerTicketFactory->create();
            $customerTicket->setType(VirtualProductType::TYPE_QWARE_TICKET);
            $customerTicket->setTicketTableName(QwareTicketRecord::TABLE_NAME);
            $customerTicket->setTicketTableRecordId($qwareTicketRecord->getId());
            $customerTicket->setCustomerId($order->getCustomerId());
            $customerTicket->setSalesOrderItemId($orderItemId);
            $customerTicket->setBelongToProductId($product->getId());
            $customerTicket->setSellerId($sellerId);
            $customerTicket->setTicketUniqueContent($qwareTicketRecord->getQwareSn());
            $customerTicket->setStatus(TicketStatus::STATUS_UNUSED);

            // Set validity period if available
            $useStartTime = $this->mappingSNToStartTime[$qwareTicketRecord->getQwareSn()] ?? null;
            $useEndTime   = $this->mappingSNToEndTime[$qwareTicketRecord->getQwareSn()] ?? '2099-12-31 00:00:00';
            if (!empty($useStartTime)) {
                $customerTicket->setUseStartTime($useStartTime);
            }
            if (!empty($useEndTime)) {
                $customerTicket->setUseEndTime($useEndTime);
            }

            $this->transaction->addObject($customerTicket);
        }

        $this->transaction->save();

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Order Item ID"      => $orderItemId,
            "Customer Tickets"   => count($records),
            "Message"            => "Successfully created customer ticket records."
        ]), self::LOG_FOLDER_NAME);
    }

    /**
     * 透過 API 查詢並更新票券詳細資訊 (URL 和密碼)
     *
     * @param array $ticketRecords QwareTicketRecord 陣列
     * @return void
     */
    protected function updateTicketDetailsFromApi(array $ticketRecords): void
    {
        if (empty($ticketRecords)) {
            return;
        }

        try {
            // 收集所有票券序號
            $ticketSns = [];
            $recordMapping = [];
            
            foreach ($ticketRecords as $record) {
                $sn = $record->getQwareSn();
                if (!empty($sn)) {
                    $ticketSns[] = $sn;
                    $recordMapping[$sn] = $record;
                }
            }

            if (empty($ticketSns)) {
                return;
            }

            // 分批查詢 (每次最多20筆)
            $batches = array_chunk($ticketSns, 20);
            
            foreach ($batches as $batch) {
                $this->processTicketBatch($batch, $recordMapping);
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Exception" => $e->getMessage(),
                "Ticket Count" => count($ticketRecords),
            ]), self::LOG_FOLDER_NAME);
            
            // 不拋出異常，避免影響主流程
        }
    }

    /**
     * 處理單批票券查詢
     *
     * @param array $ticketSns 票券序號陣列
     * @param array $recordMapping SN -> QwareTicketRecord 對應表
     * @return void
     */
    protected function processTicketBatch(array $ticketSns, array $recordMapping): void
    {
        try {
            $apiResponse = $this->apiHelper->requestApiQueryTickets($ticketSns);
            
            if (!isset($apiResponse['Data']) || !is_array($apiResponse['Data'])) {
                return;
            }

            // 收集 ValidityDate 資料以便後續更新 customer_ticket
            $validityData = [];

            // 更新每筆票券記錄
            foreach ($apiResponse['Data'] as $ticketData) {
                $sn = $ticketData['Sn'] ?? '';
                
                if (empty($sn) || !isset($recordMapping[$sn])) {
                    continue;
                }

                /** @var QwareTicketRecord $record */
                $record = $recordMapping[$sn];
                $ticket = $ticketData['Ticket'] ?? [];
                
                // 更新 VendorSn
                if (isset($ticket['VendorSn'])) {
                    $record->setQwareVendorSn($ticket['VendorSn']);
                }

                // 更新 URL 和密碼
                if (!empty($ticket['TicketUrl'])) {
                    $record->setQwareUrl($ticket['TicketUrl']);
                }
                
                if (!empty($ticket['TicketPwd'])) {
                    $record->setQwarePwd($ticket['TicketPwd']);
                }

                $this->mappingSNToStartTime[$sn] = $ticket['ValidityStartDate'] ?? null;
                $this->mappingSNToEndTime[$sn]   = $ticket['ValidityEndDate'] ?? null;

                // 更新備註
                $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $record->getMemo(),
                    [
                        "Title"                  => "Update ticket details from Qware Query API.",
                        "Query API response"     => $ticketData,
                    ]
                );
                $record->setMemo($memo);

                // 直接保存記錄
                $record->save();
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Ticket SNs" => $ticketSns,
                "Exception" => $e->getMessage(),
            ]), self::LOG_FOLDER_NAME);
        }
    }

    /**
     * 添加訂單ID到到貨檢查事件清單
     *
     * @param OrderItem $item
     * @return void
     */
    protected function addOrderIdForArrivedCheckerEvent(OrderItem $item): void
    {
        $orderId = $item->getOrderId();
        $this->orderIdsForArrivedCheckerEvent[$orderId] = $orderId;
    }

    /**
     * 觸發到貨檢查事件
     *
     * @return void
     */
    protected function fireArrivedCheckerEvent(): void
    {
        foreach ($this->orderIdsForArrivedCheckerEvent as $orderId) {
            $this->eventManager->dispatch(
                "ecpay_inovice_ticket_item_arrived_check",
                [
                    "orderId" => $orderId,
                ]
            );
        }
    }
}