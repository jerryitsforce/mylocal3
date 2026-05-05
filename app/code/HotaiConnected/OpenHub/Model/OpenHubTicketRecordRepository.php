<?php

namespace HotaiConnected\OpenHub\Model;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use HotaiConnected\OpenHub\Helper\Common as CommonHelper;
use HotaiConnected\OpenHub\Helper\Api as ApiHelper;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord as OpenHubTicketRecordModel;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecordFactory;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\Collection as OpenHubTicketRecordCollection;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\CollectionFactory as OpenHubTicketRecordCollectionFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class OpenHubTicketRecordRepository
{
    const LOG_FOLDER_NAME = 'OpenHub/OpenHubTicketRecordRepository';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OpenHubTicketRecord */
    protected $resource;

    /** @var OpenHubTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var OpenHubTicketRecordFactory */
    protected $openHubTicketRecordFactory;

    /** @var CustomerTicketFactory */
    protected $customerTicketFactory;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var CustomerCollectionFactory */
    protected $customerCollectionFactory;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        ApiHelper $apiHelper,
        OpenHubTicketRecord $resource,
        OpenHubTicketRecordCollectionFactory $collectionFactory,
        OpenHubTicketRecordFactory $openHubTicketRecordFactory,
        CustomerTicketFactory $customerTicketFactory,
        CustomerTicketRepository $customerTicketRepository,
        Transaction $transaction,
        OrderItemRepositoryInterface $orderItemRepository,
        MarketplaceHelper $marketplaceHelper,
        QuoteRepository $quoteRepository,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->commonHelper               = $commonHelper;
        $this->apiHelper                  = $apiHelper;
        $this->resource                   = $resource;
        $this->collectionFactory          = $collectionFactory;
        $this->openHubTicketRecordFactory = $openHubTicketRecordFactory;
        $this->customerTicketFactory      = $customerTicketFactory;
        $this->customerTicketRepository   = $customerTicketRepository;
        $this->transaction                = $transaction;
        $this->orderItemRepository        = $orderItemRepository;
        $this->marketplaceHelper          = $marketplaceHelper;
        $this->quoteRepository            = $quoteRepository;
        $this->customerCollectionFactory  = $customerCollectionFactory;
    }

    /**
     * 根據訂單項目ID取得票券記錄
     *
     * @param int $orderItemId
     * @return OpenHubTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): OpenHubTicketRecordCollection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(OpenHubTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);
        $collection->addFieldToFilter(OpenHubTicketRecordModel::STATUS, TicketStatus::STATUS_UNUSED);

        return $collection;
    }

    /**
     * 將票券資料儲存到資料庫
     *
     * @param array $apiResponse
     * @param Item $orderItem
     * @return void
     * @throws CouldNotSaveException
     */
    public function storeTicketDataInDatabase(array $apiResponse, Item $orderItem, ?array $customOwner = null): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title"           => "Start storing OpenHub ticket data in database",
            "Order Item ID"   => $orderItem->getId(),
            "API Response"    => $apiResponse
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        try {
            // 取得商品的 openhub_product_id 進行驗證
            $product = $orderItem->getProduct();
            $openHubProductId = $product->getData(CommonHelper::ATTRIBUTE_CODE_OPENHUB_PRODUCT_ID);
            
            if (empty($openHubProductId)) {
                throw new \Exception("Product openhub_product_id is required for order item ID: " . $orderItem->getId());
            }

            // 檢查是否有錯誤
            if (isset($apiResponse['errorCode']) && $apiResponse['errorCode'] !== null) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Title"         => "API response contains error, setting retry status",
                    "Order Item ID" => $orderItem->getId(),
                    "Error Code"    => $apiResponse['errorCode'],
                    "Error Message" => $apiResponse['message'] ?? 'Unknown error'
                ]), self::LOG_FOLDER_NAME);

                $orderItem->setData("ticket_retry_status", TicketRetryStatus::STATUS_NEED_RETRY);
                $this->orderItemRepository->save($orderItem);
                return;
            }

            // 提取票券資料
            $ticketInfo = $apiResponse['data']['ticketInfo'] ?? [];
            if (empty($ticketInfo)) {
                throw new \Exception('No ticket info found in API response');
            }

            $transaction = $this->transaction;
            $createdTickets = []; // 追蹤建立的票券記錄

            // 處理每個票券項目
            foreach ($ticketInfo as $ticketItem) {
                // 每個 ticketItem 包含產品資訊和對應的票券
                if (!isset($ticketItem['productId'])) {
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        "Title" => "Invalid ticket item - missing productId",
                        "Ticket Item" => $ticketItem,
                    ]), self::LOG_FOLDER_NAME);
                    continue;
                }

                $openHubProductId = $ticketItem['productId']; // OpenHub API 回傳的商品ID
                
                // 從 ticketItem 中提取票券序號陣列
                $serialNumbers = $this->extractSerialNumbers($ticketItem);
                
                if (empty($serialNumbers)) {
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        "Title" => "No serial numbers found in ticket item",
                        "Ticket Item" => $ticketItem,
                    ]), self::LOG_FOLDER_NAME);
                    continue;
                }

                // 為每個序號建立票券記錄
                foreach ($serialNumbers as $serialNo) {
                    // 建立 OpenHub 專用記錄
                    $openHubRecord = $this->openHubTicketRecordFactory->create();
                    $openHubRecord->setSalesOrderItemId($orderItem->getId())
                                  ->setBelongToProductId($orderItem->getProductId()) // 使用 Magento 產品ID
                                  ->setSerialNumber($serialNo)
                                  ->setOpenHubOrderNo($apiResponse['data']['orderNo'] ?? null)
                                  ->setTransactionNo($apiResponse['data']['transactionNo'] ?? null)
                                  ->setAppId($apiResponse['data']['appId'] ?? null)
                                  ->setAmount($apiResponse['data']['totalAmount'] ?? null)
                                  ->setRemark($apiResponse['data']['remark'] ?? null)
                                  ->setStatus(TicketStatus::STATUS_UNUSED);
                    
                    // 如果有外部建立時間，轉換並設定
                    if (isset($apiResponse['data']['createdAt'])) {
                        $openHubCreatedAt = new \DateTime($apiResponse['data']['createdAt']);
                        $openHubRecord->setOpenHubCreatedAt($openHubCreatedAt->format('Y-m-d H:i:s'));
                    }

                    $transaction->addObject($openHubRecord);
                    $createdTickets[] = $serialNo;
                }
            }

            // 執行事務保存 OpenHub 記錄
            $transaction->save();

            // 建立對應的 CustomerTicket 記錄
            $this->createCustomerTicketRecords($createdTickets, $orderItem, $customOwner);

            // 更新訂單項目狀態為成功
            $orderItem->setData("ticket_retry_status", TicketRetryStatus::STATUS_RETRY_SUCCESS);
            $this->orderItemRepository->save($orderItem);

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"           => "Successfully stored OpenHub ticket data",
                "Order Item ID"   => $orderItem->getId(),
                "Ticket Count"    => count($createdTickets),
                "OpenHub Order No" => $apiResponse['data']['orderNo'] ?? null,
                "Transaction No" => $apiResponse['data']['transactionNo'] ?? null,
                "Total Amount"    => $apiResponse['data']['totalAmount'] ?? null,
                "Created Tickets" => $createdTickets
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"           => "Error storing OpenHub ticket data",
                "Order Item ID"   => $orderItem->getId(),
                "Error Message"   => $e->getMessage(),
                "Stack Trace"     => $e->getTraceAsString()
            ]), self::LOG_FOLDER_NAME);

            throw new CouldNotSaveException(__('Could not save OpenHub ticket data: %1', $e->getMessage()));
        }
    }

    /**
     * 建立對應的 CustomerTicket 記錄
     *
     * @param array $createdTickets 已建立的票券序號陣列
     * @param Item $orderItem
     * @param array|null $customOwner
     * @return void
     * @throws CouldNotSaveException
     */
    private function createCustomerTicketRecords(array $createdTickets, Item $orderItem, ?array $customOwner = null): void
    {
        try {
            $transaction = $this->transaction;
            $order = $orderItem->getOrder();

            foreach ($createdTickets as $serialNo) {
                // 找到對應的 OpenHub 記錄
                $openHubRecord = $this->getOpenHubRecordBySerialAndOrderItem($serialNo, $orderItem->getId());
                
                if (!$openHubRecord) {
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        "Title" => "OpenHub record not found for CustomerTicket creation",
                        "Serial Number" => $serialNo,
                        "Order Item ID" => $orderItem->getId()
                    ]), self::LOG_FOLDER_NAME);
                    continue;
                }

                // 建立 CustomerTicket 記錄
                $customerTicket = $this->customerTicketFactory->create();
                $customerTicket->setType(VirtualProductType::TYPE_OPENHUB_TICKET);
                $customerTicket->setTicketTableName(OpenHubTicketRecordModel::TABLE_NAME);
                $customerTicket->setTicketTableRecordId($openHubRecord->getId());
                
                // Handle customer ID resolution for gift orders
                $customerId = $order->getCustomerId();
                if ($customOwner) {
                    if (isset($customOwner['telephone']) && $customOwner['telephone'] != '') {
                        $customerId = 0;
                        $customerTicket->setTelephone($customOwner['telephone']);
                        $customerTicket->setMemberSeq($customOwner['member_seq']);
                        // Load customer and set customer if the phone is an account
                        if($customOwner['member_seq']){
                            $customerCollection = $this->customerCollectionFactory->create()
                                ->addAttributeToFilter('member_seq', $customOwner['member_seq']);
                            $customerCollection->getSelect()->order('entity_id desc')->limit(1);
                            $customer = $customerCollection->getFirstItem();
                            if ($customer->getId()) {
                                $customerId = $customer->getId();
                            }
                        }
                        
                    } else {
                        $customerId = (int)$customOwner['customer_id'];
                        $customerTicket->setTelephone(null);
                    }
                }
                
                $customerTicket->setCustomerId($customerId);
                $customerTicket->setSalesOrderItemId($orderItem->getId());
                $customerTicket->setBelongToProductId($orderItem->getProductId());
                $customerTicket->setSellerId($this->getSellerIdByProductId($orderItem->getProductId()));
                $customerTicket->setTicketUniqueContent($serialNo);
                $customerTicket->setUseStartTime(date('Y-m-d H:i:s')); // 立即生效
                $customerTicket->setUseEndTime(date('Y-m-d H:i:s', strtotime('+6 months'))); // 6個月後
                $customerTicket->setStatus(TicketStatus::STATUS_UNUSED);

                $transaction->addObject($customerTicket);
            }

            $transaction->save();

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Successfully created CustomerTicket records for OpenHub",
                "Order Item ID" => $orderItem->getId(),
                "Ticket Count" => count($createdTickets)
            ]), self::LOG_FOLDER_NAME);

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Error creating CustomerTicket records for OpenHub",
                "Order Item ID" => $orderItem->getId(),
                "Error Message" => $e->getMessage(),
                "Stack Trace" => $e->getTraceAsString()
            ]), self::LOG_FOLDER_NAME);

            throw new CouldNotSaveException(__('Could not create OpenHub CustomerTicket records: %1', $e->getMessage()));
        }
    }

    /**
     * 根據序號和訂單項目ID找到對應的 OpenHub 記錄
     *
     * @param string $serialNo
     * @param int $orderItemId
     * @return OpenHubTicketRecordModel|null
     */
    private function getOpenHubRecordBySerialAndOrderItem(string $serialNo, int $orderItemId): ?OpenHubTicketRecordModel
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(OpenHubTicketRecordModel::SERIAL_NUMBER, $serialNo);
        $collection->addFieldToFilter(OpenHubTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $record = $collection->getFirstItem();
        return $record->getId() ? $record : null;
    }

    /**
     * 根據商品ID取得賣家ID
     *
     * @param int $productId
     * @return int
     */
    private function getSellerIdByProductId(int $productId): int
    {
        return $this->marketplaceHelper->getSellerIdByProductId($productId);
    }

    /**
     * 從票券項目中提取序號
     * 根據實際 API 回應格式調整
     *
     * @param array $ticketItem
     * @return array
     */
    private function extractSerialNumbers(array $ticketItem): array
    {
        // 根據 OpenHub API 回應格式解析序號
        // API 格式：ticketInfo[].serialNo (陣列)
        
        if (isset($ticketItem['serialNo']) && is_array($ticketItem['serialNo'])) {
            return $ticketItem['serialNo'];
        }
        
        // 備用檢查：如果是單一序號
        if (isset($ticketItem['serialNo']) && is_string($ticketItem['serialNo'])) {
            return [$ticketItem['serialNo']];
        }
        
        // 如果找不到序號，記錄警告並返回空陣列
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "Could not extract serial numbers from ticket item",
            "Ticket Item Keys" => array_keys($ticketItem),
            "Ticket Item" => $ticketItem,
            "Expected Format" => "ticketItem should contain 'serialNo' array",
            "Message" => "OpenHub API response format: ticketInfo[].serialNo should be array"
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
        
        return [];
    }

    /**
     * 根據訂單項目ID執行票券退貨
     *
     * @param int $orderItemId
     * @return void
     * @throws \Exception
     */
    public function refundTicketsByOrderItemId(int $orderItemId): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "Starting OpenHub ticket refund process",
            "Order Item ID" => $orderItemId,
            "Timestamp" => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        // 取得該訂單項目的所有未使用票券
        $collection = $this->getRecordsByOrderItemId($orderItemId);
        
        if ($collection->getSize() == 0) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "No tickets found for refund",
                "Order Item ID" => $orderItemId
            ]), self::LOG_FOLDER_NAME);
            return;
        }

        // 取得訂單項目資訊以獲得商品的 openhub_product_id
        /** @var Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);
        $product = $orderItem->getProduct();
        $openHubProductId = $product->getData(CommonHelper::ATTRIBUTE_CODE_OPENHUB_PRODUCT_ID);
        
        if (empty($openHubProductId)) {
            throw new \Exception("OpenHub product ID is required for refund but not found in product attributes");
        }

        // 取得退貨數量和交易編號
        $quantity = $collection->getSize(); // 退貨票券數量
        $externalTransactionNo = null;
        
        /** @var OpenHubTicketRecordModel $record */
        $firstRecord = $collection->getFirstItem();
        $externalTransactionNo = $firstRecord->getTransactionNo();
        
        if (empty($externalTransactionNo)) {
            throw new \Exception("External transaction number is required for refund but not found in ticket records");
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "Calling OpenHub Return API",
            "Order Item ID" => $orderItemId,
            "Transaction No" => $externalTransactionNo,
            "OpenHub Product ID" => $openHubProductId,
            "Quantity" => $quantity
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        // 調用退貨 API
        $apiResponse = $this->apiHelper->requestApiReturn($externalTransactionNo, (int)$openHubProductId, $quantity);

        // 更新票券狀態
        $this->updateTicketStatusAfterReturn($collection, $apiResponse);
    }

    /**
     * 根據 API 回應更新票券狀態
     *
     * @param OpenHubTicketRecordCollection $collection
     * @param array $apiResponse
     * @return void
     */
    protected function updateTicketStatusAfterReturn(OpenHubTicketRecordCollection $collection, array $apiResponse): void
    {
        try {
            // OpenHub API 成功回應格式：errorCode 為 null
            if (isset($apiResponse['errorCode']) && $apiResponse['errorCode'] !== null) {
                throw new \Exception('OpenHub Return API returned error: ' . ($apiResponse['message'] ?? 'Unknown error'));
            }

            $transaction = $this->transaction;
            $returnData = $apiResponse['data'] ?? [];
            $timestamp = date('Y-m-d H:i:s');

            // OpenHub API 退貨成功，更新所有票券狀態
            // 根據 API 文件，成功退貨會返回 errorCode: null
            $successMessage = $apiResponse['message'] ?? 'Return successful';
            
            /** @var OpenHubTicketRecordModel $record */
            foreach ($collection->getItems() as $record) {
                $this->updateSingleTicketAfterReturn($record, true, $successMessage, $timestamp, $transaction);
            }

            $transaction->save();

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "OpenHub ticket return process completed",
                "Processed Tickets" => count($collection->getItems()),
                "API Response" => $apiResponse,
                "Timestamp" => $timestamp
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Error updating ticket status after return",
                "Error Message" => $e->getMessage(),
                "Stack Trace" => $e->getTraceAsString(),
                "API Response" => $apiResponse
            ]), self::LOG_FOLDER_NAME);

            throw new CouldNotSaveException(__('Could not update OpenHub ticket status after return: %1', $e->getMessage()));
        }
    }

    /**
     * 更新單一票券的退貨狀態
     *
     * @param OpenHubTicketRecordModel $record
     * @param bool $success
     * @param string $message
     * @param string $timestamp
     * @param Transaction $transaction
     * @return void
     */
    private function updateSingleTicketAfterReturn(
        OpenHubTicketRecordModel $record,
        bool $success,
        string $message,
        string $timestamp,
        Transaction $transaction
    ): void {
        if ($success) {
            // 退貨成功，更新狀態為已退貨
            $record->setStatus(TicketStatus::STATUS_RETURNED);
            
            // 添加備註
            $memo = $record->getData('memo') ?: '';
            $memo .= "\n[{$timestamp}] Return successful - {$message}";
            $record->setData('memo', $memo);
            
            $transaction->addObject($record);
            
            // 更新對應的 customer_ticket 狀態
            $customerTicketRecord = $this->getCustomerTicketByOpenHubRecord($record);
            if ($customerTicketRecord) {
                $customerTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
                $transaction->addObject($customerTicketRecord);
            }
            
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "OpenHub ticket returned successfully",
                "Serial Number" => $record->getSerialNumber(),
                "Record ID" => $record->getId(),
                "Message" => $message
            ]), self::LOG_FOLDER_NAME);
        } else {
            // 退貨失敗，記錄錯誤資訊但不更改狀態
            $memo = $record->getData('memo') ?: '';
            $memo .= "\n[{$timestamp}] Return failed - {$message}";
            $record->setData('memo', $memo);
            
            $transaction->addObject($record);
            
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "OpenHub ticket return failed",
                "Serial Number" => $record->getSerialNumber(),
                "Record ID" => $record->getId(),
                "Error Message" => $message
            ]), self::LOG_FOLDER_NAME);
        }
    }

    /**
     * 根據序號取得 OpenHub 票券記錄
     *
     * @param string $serialNumber
     * @return OpenHubTicketRecordModel|null
     */
    public function getBySerialNumber(string $serialNumber): ?OpenHubTicketRecordModel
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(OpenHubTicketRecordModel::SERIAL_NUMBER, $serialNumber);

        /** @var OpenHubTicketRecordModel $record */
        $record = $collection->getFirstItem();
        
        return $record->getId() ? $record : null;
    }

    /**
     * 根據 OpenHub 記錄取得對應的 CustomerTicket
     *
     * @param OpenHubTicketRecordModel $openHubRecord
     * @return CustomerTicket|null
     */
    private function getCustomerTicketByOpenHubRecord(OpenHubTicketRecordModel $openHubRecord): ?CustomerTicket
    {
        return $this->customerTicketRepository->getByTypeAndTicketRecordId(
            VirtualProductType::TYPE_OPENHUB_TICKET,
            $openHubRecord->getId()
        );
    }
}