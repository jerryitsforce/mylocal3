<?php

namespace HotaiConnected\Qware\Model;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use HotaiConnected\Qware\Api\Data\QwareTicketRecordSearchResultsInterfaceFactory;
use HotaiConnected\Qware\Api\QwareTicketRecordRepositoryInterface;
use HotaiConnected\Qware\Helper\Api as ApiHelper;
use HotaiConnected\Qware\Helper\Common as CommonHelper;
use HotaiConnected\Qware\Model\QwareTicketRecord as QwareTicketRecordModel;
use HotaiConnected\Qware\Model\QwareTicketRecordFactory;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord\Collection as QwareTicketRecordCollection;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord\CollectionFactory as QwareTicketRecordCollectionFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class QwareTicketRecordRepository implements QwareTicketRecordRepositoryInterface
{

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var QwareTicketRecord */
    protected $resource;

    /** @var QwareTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var QwareTicketRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var QwareTicketRecordFactory */
    protected $qwareTicketRecordFactory;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CustomerTicketFactory */
    protected $customerTicketFactory;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var CustomerCollectionFactory */
    protected $customerCollectionFactory;

    protected $mappingSNToStartTime = [];
    protected $mappingSNToEndTime = [];

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        QwareTicketRecord $resource,
        QwareTicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        QwareTicketRecordSearchResultsInterfaceFactory $searchResultsFactory,
        QwareTicketRecordFactory $qwareTicketRecordFactory,
        CustomerTicketFactory $customerTicketFactory,
        CustomerTicketRepository $customerTicketRepository,
        MarketplaceHelper $marketplaceHelper,
        ApiHelper $apiHelper,
        Transaction $transaction,
        OrderItemRepositoryInterface $orderItemRepository,
        QuoteRepository $quoteRepository,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->commonHelper               = $commonHelper;
        $this->resource                   = $resource;
        $this->collectionFactory          = $collectionFactory;
        $this->collectionProcessor        = $collectionProcessor;
        $this->searchResultsFactory       = $searchResultsFactory;
        $this->qwareTicketRecordFactory   = $qwareTicketRecordFactory;
        $this->customerTicketFactory      = $customerTicketFactory;
        $this->customerTicketRepository   = $customerTicketRepository;
        $this->marketplaceHelper          = $marketplaceHelper;
        $this->apiHelper                  = $apiHelper;
        $this->transaction                = $transaction;
        $this->orderItemRepository        = $orderItemRepository;
        $this->quoteRepository            = $quoteRepository;
        $this->customerCollectionFactory  = $customerCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(
        \HotaiConnected\Qware\Api\Data\QwareTicketRecordInterface $record
    ) {
        try {
            /** @var \HotaiConnected\Qware\Model\QwareTicketRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the QwareTicketRecord: %1',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($criteria);
        $searchResult->setItems($collection->getData());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    public function getById(int $id): ?QwareTicketRecordModel
    {
        /** @var QwareTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(QwareTicketRecordModel::RECORD_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 根據sales_order_item_id找到對應的安源票券紀錄
     *
     * @param integer $orderItemId
     * @return QwareTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): QwareTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(QwareTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }

    /**
     * 根據sales_order_item_id和票券狀態找到對應的安源票券紀錄
     *
     * @param integer $orderItemId
     * @param integer $qwareTicketRecordStatus
     * @return QwareTicketRecordCollection
     */
    public function getRecordsByOrderItemIdAndStatus(int $orderItemId, int $qwareTicketRecordStatus): QwareTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(QwareTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId)
            ->addFieldToFilter(QwareTicketRecordModel::STATUS, $qwareTicketRecordStatus);

        $collection->load();

        return $collection;
    }

    /**
     * 根據qware_sn找到單一安源票券紀錄
     *
     * @param string $qwareSn
     * @return QwareTicketRecordModel|null
     */
    public function getRecordByQwareSn(string $qwareSn): ?QwareTicketRecordModel
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(QwareTicketRecordModel::QWARE_SN, $qwareSn);

        /** @var QwareTicketRecordModel $qwareRecord */
        $qwareRecord = $collection->getFirstItem();

        return ($qwareRecord->getId()) ? $qwareRecord : null;
    }

    /**
     * 將屬於order item的票券資料以transaction的方式整批寫入資料庫中
     *
     * @param array $qwareApiResponse
     * @param Item $item
     * @return void
     */
    public function storeVoucherDataInDatabase(array $qwareApiResponse, Item $item, array $customOwner = null): void
    {
        $product          = $item->getProduct();
        $order            = $item->getOrder();
        $sellerId         = $this->marketplaceHelper->getSellerIdByProductId($product->getId());
        $recordArray      = [];
        $this->mappingSNToStartTime = [];
        $this->mappingSNToEndTime = [];


        // Parse Qware API response format: {"Data": {"guid": ["SN1", "SN2"]}, "Code": 200}
        $productGuid = $product->getData(CommonHelper::ATTRIBUTE_CODE_QWARE_GUID);
        
        // 如果是 Code 202，Data 為 null，需要先建立記錄保存 OrderNo
        if (isset($qwareApiResponse['Code']) && $qwareApiResponse['Code'] == 202) {
            // 202狀態：訂單處理中，先建立基本記錄保存OrderNo
            $this->storeCode202TicketRecords($qwareApiResponse, $item);
            
            // 設定 item 的 retry 狀態為 PENDING 並重置 retry 次數
            $item->setData('ticket_retry_status', \Branch8\HotaiCore\Model\Ticket\TicketRetryStatus::STATUS_PENDING);
            $item->setData('ticket_retry_count', 0);
            $this->orderItemRepository->save($item);
            
            return;
        } else {
            if (!isset($qwareApiResponse['Data']) || !is_array($qwareApiResponse['Data'])) {
                throw new \Exception("Invalid Qware API response format: " . json_encode($qwareApiResponse));
            }
            
            $vouchers = $qwareApiResponse['Data'][$productGuid] ?? [];
            
            if (empty($vouchers)) {
                throw new \Exception("No vouchers found in API response for GUID: " . $productGuid);
            }
        }

        foreach ($vouchers as $voucherSn) {
            /** @var \HotaiConnected\Qware\Model\QwareTicketRecord $qwareTicketRecord */
            $qwareTicketRecord = $this->qwareTicketRecordFactory->create();

            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $qwareTicketRecord->getMemo(),
                [
                    "Title"              => "Store new ticket record from Qware API.",
                    "Issue API response" => $qwareApiResponse,
                ]
            );

            // 設定基本欄位
            $qwareTicketRecord->setQwareOrderNumber($qwareApiResponse['OrderNo'] ?? "");
            $qwareTicketRecord->setQwareProductGuid($productGuid);
            $qwareTicketRecord->setBelongToProductId($product->getId());
            $qwareTicketRecord->setSalesOrderItemId($item->getId());
            $qwareTicketRecord->setQwareSn($voucherSn);
            $qwareTicketRecord->setQwareUrl(""); // 需要後續透過查詢API取得
            $qwareTicketRecord->setQwarePwd(""); // 需要後續透過查詢API取得
            $qwareTicketRecord->setQwareGenerateDate(date('Y-m-d H:i:s'));
            $qwareTicketRecord->setStatus(TicketStatus::STATUS_UNUSED);
            $qwareTicketRecord->setMemo($memo);

            $recordArray[] = $qwareTicketRecord;
            $this->transaction->addObject($qwareTicketRecord);
        }

        $this->transaction->save();

        // 查詢票券詳細資訊 (URL 和密碼)
        $this->updateTicketDetailsFromApi($recordArray);

        // 分成兩個transaction是因為未創建record記錄前沒辦法直接拿到ID($qwareTicketRecord->getId())
        foreach ($recordArray as $qwareTicketRecord) {
            /** @var CustomerTicket $customerTicket */
            $customerTicket = $this->customerTicketFactory->create();
            $customerTicket->setType(VirtualProductType::TYPE_QWARE_TICKET);
            $customerTicket->setTicketTableName(QwareTicketRecordModel::TABLE_NAME);
            $customerTicket->setTicketTableRecordId($qwareTicketRecord->getId());
            
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

            // Set validity period if available
            $useStartTime = $this->mappingSNToStartTime[$qwareTicketRecord->getQwareSn()] ?? null;
            $useEndTime   = $this->mappingSNToEndTime[$qwareTicketRecord->getQwareSn()] ?? '2099-12-31 00:00:00';
            if (!empty($useStartTime)) {
                $customerTicket->setUseStartTime($useStartTime);
            }
            if (!empty($useEndTime)) {
                $customerTicket->setUseEndTime($useEndTime);
            }

            $customerTicket->setCustomerId($customerId);
            $customerTicket->setSalesOrderItemId($item->getId());
            $customerTicket->setBelongToProductId($product->getId());
            $customerTicket->setSellerId($sellerId);
            $customerTicket->setTicketUniqueContent($qwareTicketRecord->getQwareSn());
            $customerTicket->setStatus(TicketStatus::STATUS_UNUSED);

            $this->transaction->addObject($customerTicket);
        }

        $this->transaction->save();
    }

    /**
     * 透過 API 查詢並更新票券詳細資訊 (URL 和密碼)
     *
     * @param array $ticketRecords QwareTicketRecord 陣列
     * @return void
     */
    public function updateTicketDetailsFromApi(array $ticketRecords): void
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
                "Title"           => "Failed to update ticket details from API.",
                "Exception"       => $e->getMessage(),
                "Ticket Count"    => count($ticketRecords),
            ], \JSON_UNESCAPED_SLASHES), 'Qware/Repository/UpdateTicketDetails');
            
            // 不拋出異常，避免影響主流程
        }
    }

    /**
     * 處理 Code 202 回應，建立基本票券記錄
     *
     * @param array $qwareApiResponse
     * @param Item $item
     * @return void
     */
    protected function storeCode202TicketRecords(array $qwareApiResponse, Item $item): void
    {
        $product = $item->getProduct();
        $order = $item->getOrder();
        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());
        $productGuid = $product->getData(CommonHelper::ATTRIBUTE_CODE_QWARE_GUID);
        $quantity = (int) $item->getQtyOrdered();
        $orderNo = $qwareApiResponse['OrderNo'] ?? "";
        
        $recordArray = [];

        // 根據訂購數量建立空的票券記錄
        for ($i = 1; $i <= $quantity; $i++) {
            /** @var \HotaiConnected\Qware\Model\QwareTicketRecord $qwareTicketRecord */
            $qwareTicketRecord = $this->qwareTicketRecordFactory->create();

            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $qwareTicketRecord->getMemo(),
                [
                    "Title" => "Store pending ticket record from Qware API (Code 202).",
                    "Issue API response" => $qwareApiResponse,
                ]
            );

            // 設定基本欄位
            $qwareTicketRecord->setQwareOrderNumber($orderNo);
            $qwareTicketRecord->setQwareProductGuid($productGuid);
            $qwareTicketRecord->setBelongToProductId($product->getId());
            $qwareTicketRecord->setSalesOrderItemId($item->getId());
            $qwareTicketRecord->setQwareSn(""); // 暫時空白，等待後續查詢
            $qwareTicketRecord->setQwareUrl(""); // 等待後續查詢
            $qwareTicketRecord->setQwarePwd(""); // 等待後續查詢
            $qwareTicketRecord->setQwareGenerateDate(date('Y-m-d H:i:s'));
            $qwareTicketRecord->setStatus(QwareTicketRecord::STATUS_IMPORTED);
            $qwareTicketRecord->setMemo($memo);

            $recordArray[] = $qwareTicketRecord;
            $this->transaction->addObject($qwareTicketRecord);
        }

        $this->transaction->save();

        // 202狀態不建立 customer_ticket 記錄，等到200成功時再建立

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "Created pending ticket records for Code 202 response.",
            "Order item ID" => $item->getId(),
            "Order Number" => $orderNo,
            "Records created" => count($recordArray),
        ], \JSON_UNESCAPED_SLASHES), 'Qware/Repository/Code202Processing');
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

            // 更新每筆票券記錄
            foreach ($apiResponse['Data'] as $ticketData) {
                $sn = $ticketData['Sn'] ?? '';
                
                if (empty($sn) || !isset($recordMapping[$sn])) {
                    continue;
                }

                /** @var QwareTicketRecordModel $record */
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
                $this->mappingSNToEndTime[$sn] = $ticket['ValidityEndDate'] ?? null;

                // 更新備註
                $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $record->getMemo(),
                    [
                        "Title"                  => "Update ticket details from Qware Query API.",
                        "Query API response"     => $ticketData,
                    ]
                );
                $record->setMemo($memo);

                // 保存記錄
                $this->transaction->addObject($record);
            }

            $this->transaction->save();

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"           => "Failed to process ticket batch.",
                "Ticket SNs"      => $ticketSns,
                "Exception"       => $e->getMessage(),
            ], \JSON_UNESCAPED_SLASHES), 'Qware/Repository/ProcessTicketBatch');
        }
    }

    /**
     * 將"已退貨(已取消)"狀態寫入本地安源票券資料庫中(qware_ticket_record)
     * @param QwareTicketRecordCollection $collection
     * @param array $apiResponse
     * @return void
     */
    public function setCanceledStatusToQwareTicketRecordsInDb(QwareTicketRecordCollection $collection, array $apiResponse = []): void
    {
        /** @var QwareTicketRecordModel $qwareTicketRecord */
        foreach ($collection->getItems() as $qwareTicketRecord) {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $qwareTicketRecord->getMemo(),
                [
                    "Title"               => "Cancel ticket record using Qware API.",
                    "Cancel API response" => $apiResponse,
                ]
            );

            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

            $qwareTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
            $qwareTicketRecord->setMemo($memo);
            $this->transaction->addObject($qwareTicketRecord);

            $customerTicketRecord = $this->customerTicketRepository->getByTypeAndTicketRecordId(
                VirtualProductType::TYPE_QWARE_TICKET,
                $qwareTicketRecord->getId()
            );

            if (!is_null($customerTicketRecord)) {
                $customerTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
                $this->transaction->addObject($customerTicketRecord);
            }
        }

        $this->transaction->save();
    }
}