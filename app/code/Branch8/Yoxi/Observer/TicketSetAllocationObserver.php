<?php

namespace Branch8\Yoxi\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\Yoxi\Helper\Common as CommonHelper;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\CollectionFactory as BatchSettingCollectionFactory;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\CollectionFactory;
use Branch8\Yoxi\Model\YoxiBatchSetting as BatchSettingModel;
use Branch8\Yoxi\Model\YoxiTicketRecord as TicketRecordModel;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository as TicketRecordRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Magento\Framework\App\ResourceConnection;

class TicketSetAllocationObserver implements ObserverInterface
{
    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var QuoteItemRepository */
    protected $quoteItemRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var BatchSettingCollectionFactory */
    protected $batchSettingCollectionFactory;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var TicketRecordRepository */
    protected $ticketRecordRepository;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    protected $eventName;

    public function __construct(
        QuoteRepository $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        ProductRepositoryInterface $productRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        BatchSettingCollectionFactory $batchSettingCollectionFactory,
        CollectionFactory $collectionFactory,
        TicketRecordRepository $ticketRecordRepository,
        MarketplaceHelper $marketplaceHelper,
        VirtualProductHelper $virtualProductHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->quoteRepository               = $quoteRepository;
        $this->quoteItemRepository           = $quoteItemRepository;
        $this->productRepository             = $productRepository;
        $this->hotaiCoreCommonHelper         = $hotaiCoreCommonHelper;
        $this->commonHelper                  = $commonHelper;
        $this->batchSettingCollectionFactory = $batchSettingCollectionFactory;
        $this->collectionFactory             = $collectionFactory;
        $this->ticketRecordRepository        = $ticketRecordRepository;
        $this->marketplaceHelper             = $marketplaceHelper;
        $this->virtualProductHelper          = $virtualProductHelper;
        $this->connection                    = $resourceConnection->getConnection();
    }

    public function execute(Observer $observer)
    {
        $handleArray     = [];
        $this->eventName = $observer->getEvent()->getName();
        $data            = $observer->getEvent()->getData();
        $quoteId         = $data["quoteId"];
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getAvailableToCheckout() == 0) {
                continue;
            }

            if (!$this->commonHelper->IsYoxiTicketProduct($item->getProductId())) {
                continue;
            }

            $options              = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
            $ticketBatchSettingId = $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($item)->getSku();

            if (!$ticketBatchSettingId) {
                $title = "Ticket batch setting ID is not set for product ID.";

                $this->writeAllocationFailLog([
                    "title"                => $title,
                    "class"                => __CLASS__,
                    "productId"            => $item->getProductId(),
                    "quoteItemId"          => $item->getId(),
                    "quantity"             => $item->getQty(),
                    "options"              => $options,
                    "ticketBatchSettingId" => $ticketBatchSettingId
                ]);

                throw new \Exception(
                    "TicketSetAllocationObserver exception:" . $title
                );
            }

            $batchSetting = $this->getTargetBatchSetting($ticketBatchSettingId);

            if (empty($batchSetting) || empty($batchSetting->getSettingId())) {
                $title = "Finding ticket batch setting by ID fail.";

                $this->writeAllocationFailLog([
                    "title"                => $title,
                    "class"                => __CLASS__,
                    "productId"            => $item->getProductId(),
                    "quoteItemId"          => $item->getId(),
                    "quantity"             => $item->getQty(),
                    "options"              => $options,
                    "ticketBatchSettingId" => $ticketBatchSettingId
                ]);

                throw new \Exception(
                    "TicketSetAllocationObserver exception:" . $title
                );
            }

            if ($batchSetting->getBelongToProductId() != $item->getProductId()) {
                $title = "Product ID check for ticket batch setting fail.";

                $this->writeAllocationFailLog([
                    "title"                => $title,
                    "class"                => __CLASS__,
                    "productId"            => $item->getProductId(),
                    "quoteItemId"          => $item->getId(),
                    "quantity"             => $item->getQty(),
                    "options"              => $options,
                    "ticketBatchSettingId" => $ticketBatchSettingId
                ]);

                throw new \Exception(
                    "TicketSetAllocationObserver exception:" . $title
                );
            }

            if (!$this->virtualProductHelper->checkBatchSettingSaleEndTimeForSetAllocationFlow($batchSetting->getSaleEndTime())) {
                $title = "Ticket batch setting sale end time check fail.";

                $this->writeAllocationFailLog([
                    "title"                => $title,
                    "class"                => __CLASS__,
                    "productId"            => $item->getProductId(),
                    "quoteItemId"          => $item->getId(),
                    "quantity"             => $item->getQty(),
                    "options"              => $options,
                    "ticketBatchSettingId" => $ticketBatchSettingId,
                    "saleEndTime"          => $batchSetting->getSaleEndTime()
                ]);

                throw new \Exception(
                    __($title)
                );
            }

            $handleArray[] = [
                "productId"            => $item->getProductId(),
                "quoteItemId"          => $item->getId(),
                "quantity"             => $item->getQty(),
                "ticketBatchSettingId" => $ticketBatchSettingId
            ];
        }

        if (count($handleArray) == 0) {
            return;
        }

        foreach ($handleArray as $handleSet) {
            try {
                $this->connection->beginTransaction();

                $collection   = $this->getAvailableCollection($handleSet);
                $recordsArray = $collection->getItems();

                if (count($recordsArray) != $handleSet["quantity"]) {
                    $requestQuantity   = $handleSet["quantity"];
                    $remainingQuantity = count($recordsArray);

                    $exceptionMessage = "Quantity not enough, ";
                    $exceptionMessage .= "request quantity: {$requestQuantity}, ";
                    $exceptionMessage .= "remaining quantity: {$remainingQuantity}.";
                    throw new \Exception($exceptionMessage);
                }

                /** @var TicketRecordModel $record */
                foreach ($recordsArray as $record) {
                    $record = $this->setTicketRecordUpdateValue($record, $handleSet["quoteItemId"]);
                    $this->ticketRecordRepository->save($record);
                }

                $this->connection->commit();
            } catch (\Throwable $th) {
                $this->writeAllocationFailLog([
                    "title"     => "Something went wrong while executing set allocation transaction.",
                    "class"     => __CLASS__,
                    "handleSet" => $handleSet,
                    "exception" => $th->getMessage()
                ]);

                $this->connection->rollBack();

                throw new \Exception("TicketSetAllocationObserver exception:" . $th->getMessage());
            }
        }
    }

    protected function getTargetBatchSetting(int|string $batchSettingId): null|BatchSettingModel
    {
        $collection = $this->batchSettingCollectionFactory->create();
        $collection->addFieldToFilter(BatchSettingModel::SETTING_ID, $batchSettingId);

        if ($collection->getSize() != 1) {
            return null;
        }

        /** @var BatchSettingModel $batchSetting */
        $batchSetting = $collection->getFirstItem();

        return $batchSetting;
    }

    /**
     * 對資料庫查詢票券可用庫存紀錄,
     * 使用forUpdate()阻擋其他請求同時查詢的結果進行查詢或更新,
     * 以避免將票券紀錄重複指派不同的quote_item_id
     *
     * 查詢條件:
     * 1. 指定商品({record_table}.product_id)
     * 2. 票券狀態({record_table}.status)
     * 3. 指定貨號設定ID({batch_setting_table}.setting_id)
     *
     * @param array $handleSet
     * @return Collection
     */
    private function getAvailableCollection(array $handleSet): Collection
    {
        $batchSettingIdFieldName = TicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = BatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = BatchSettingModel::SETTING_ID;
        $productId               = $handleSet["productId"];
        $quantity                = $handleSet["quantity"];
        $ticketBatchSettingId    = $handleSet["ticketBatchSettingId"];
        $sellerId                = $this->marketplaceHelper->getSellerIdByProductId((string) $productId);

        $collection = $this->collectionFactory->create();

        $collection
            ->join(
                [BatchSettingModel::TABLE_NAME => BatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}",
                [
                    'batch_use_start_time' => 'use_start_time',
                    'batch_use_end_time'   => 'use_end_time',
                    'batch_due_days'       => 'due_days'
                ]
            );

        $collection
            ->addFieldToFilter("main_table." . TicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter("main_table." . TicketRecordModel::SELLER_ID, $sellerId)
            ->addFieldToFilter(TicketRecordModel::STATUS, TicketRecordModel::STATUS_IMPORTED)
            ->addFieldToFilter(
                BatchSettingModel::SETTING_ID,
                $ticketBatchSettingId
            );

        $collection
            ->setPageSize($quantity)
            ->setCurPage(1);

        $collection->getSelect()->order(BatchSettingModel::SALE_END_TIME . " ASC")->forUpdate();

        $collection->load();

        return $collection;
    }

    /**
     * 將傳入的TicketRecordModel寫入欲更新的值
     * @param TicketRecordModel $record
     * @param integer $salesOrderItemId
     * @return TicketRecordModel
     */
    private function setTicketRecordUpdateValue(TicketRecordModel $record, int $quoteItemId): TicketRecordModel
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = \Branch8\HotaiCore\Model\Ticket\Status::STATUS_ALLOCATED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(TicketRecordModel::MEMO),
            [
                "Timestamp" => time(),
                "Datetime"  => date("Y-m-d H:i:s"),
                "Title"     => "Yoxi TicketSetAllocationObserver triggered by event: {$this->eventName}",
                "Message"   => "Set allocated status, quote item ID: {$quoteItemId}, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        $record->setQuoteItemId($quoteItemId);
        $record->setUseStartTime($record->getData("batch_use_start_time"));
        $record->setUseEndTime($record->getData("batch_use_end_time"));
        $record->setDueDays($record->getData("batch_due_days"));
        $record->setStatus($afterStatus);
        $record->setMemo($memoMessage);

        return $record;
    }

    protected function writeAllocationFailLog(array $message): void
    {
        $this->hotaiCoreCommonHelper->writeLog(
            json_encode($message),
            "TicketSetAllocationObserver"
        );
    }
}
