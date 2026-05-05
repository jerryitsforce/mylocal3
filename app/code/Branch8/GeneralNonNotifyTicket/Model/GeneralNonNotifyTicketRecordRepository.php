<?php

namespace Branch8\GeneralNonNotifyTicket\Model;

use Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordSearchResultsInterfaceFactory;
use Branch8\GeneralNonNotifyTicket\Api\GeneralNonNotifyTicketRecordRepositoryInterface;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\Collection as GeneralNonNotifyTicketRecordCollection;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\CollectionFactory as GeneralNonNotifyTicketRecordCollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting as GeneralNonNotifyTicketBatchSettingModel;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord as GeneralNonNotifyTicketRecordModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class GeneralNonNotifyTicketRecordRepository implements GeneralNonNotifyTicketRecordRepositoryInterface
{
    /** @var GeneralNonNotifyTicketRecord */
    protected $resource;

    /** @var GeneralNonNotifyTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var GeneralNonNotifyTicketRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        GeneralNonNotifyTicketRecord $resource,
        GeneralNonNotifyTicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        GeneralNonNotifyTicketRecordSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource             = $resource;
        $this->collectionFactory    = $collectionFactory;
        $this->collectionProcessor  = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(
        \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordInterface $record
    ) {
        try {
            /** @var \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the GeneralNonNotifyTicketRecord: %1',
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

    public function getByIdWithBatchingData(int $recordId): null|GeneralNonNotifyTicketRecordModel
    {
        $collection = $this->collectionFactory->create();

        $recordBatchSettingIdName = GeneralNonNotifyTicketRecordModel::BATCH_SETTING_ID;
        $batchTableName           = GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME;
        $batchTableIdName         = GeneralNonNotifyTicketBatchSettingModel::SETTING_ID;

        $collection->getselect()->joinLeft(
            [GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME => GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME],
            "main_table.{$recordBatchSettingIdName} = {$batchTableName}.{$batchTableIdName}",
            [
                GeneralNonNotifyTicketBatchSettingModel::BATCH_CODE,
                GeneralNonNotifyTicketBatchSettingModel::SELLER_ID
            ]
        );

        $collection->addFieldToFilter(GeneralNonNotifyTicketRecordModel::RECORD_ID, $recordId);

        $result = $collection->getFirstItem();

        return is_null($result->getId()) ? null : $result;
    }

    /**
     * 以Magento商品id和貨號紀錄id取得未售出(初始匯入狀態)的票券紀錄
     * @param integer $productId
     * @param integer $batchSettingId
     * @return GeneralNonNotifyTicketRecordCollection
     */
    public function getUnsoldRecordsByProductIdAndBatchCode(int $productId, int $batchSettingId): GeneralNonNotifyTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::BATCH_SETTING_ID, $batchSettingId)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::STATUS, TicketStatus::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 根據當下時間獲取商品可用的票券庫存
     * @param integer $productId
     * @return GeneralNonNotifyTicketRecordCollection
     */
    public function getAvailableForSaleRecordsByProductId(int $productId): GeneralNonNotifyTicketRecordCollection
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $currentDatetime = $taiwanDateObj->format("Y-m-d H:i:s");

        $batchSettingIdFieldName = GeneralNonNotifyTicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = GeneralNonNotifyTicketBatchSettingModel::SETTING_ID;

        $collection = $this->collectionFactory->create()
            ->join(
                [GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME => GeneralNonNotifyTicketBatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}"
            )
            ->addFieldToFilter(
                GeneralNonNotifyTicketBatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                GeneralNonNotifyTicketBatchSettingModel::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            );

        $collection->addFieldToFilter("main_table." . GeneralNonNotifyTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::STATUS, GeneralNonNotifyTicketRecordModel::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 以序號取得票券紀錄
     * @param string $serialNumber
     * @return GeneralNonNotifyTicketRecordModel|null
     */
    public function getRecordByProductIdAndSerialNumber(int $productId, string $serialNumber): ?GeneralNonNotifyTicketRecordModel
    {
        /** @var GeneralNonNotifyTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 以序號和sellerId取得票券紀錄
     * @param string $serialNumber
     * @param int $sellerId
     * @return GeneralNonNotifyTicketRecordModel|null
     */
    public function getRecordBySerialNumberAndSellerId(string $serialNumber, int $sellerId): ?GeneralNonNotifyTicketRecordModel
    {
        /** @var GeneralNonNotifyTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SELLER_ID, $sellerId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 以序號和sellerIds取得票券紀錄
     * @param string $serialNumber
     * @param string $sellerIds
     * @return \Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\Collection
     */
    public function getRecordBySerialNumberAndSellerIdsString(string $serialNumber, string $sellerIds): GeneralNonNotifyTicketRecordCollection
    {
        $sellerIdArray = explode(',', $sellerIds);

        /** @var GeneralNonNotifyTicketRecordModel $result */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SELLER_ID, ['in' => $sellerIdArray]);

        return $collection;
    }

    /**
     * 根據sales_order_item_id找到對應的票券紀錄
     *
     * @param integer $orderItemId
     * @return GeneralNonNotifyTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): GeneralNonNotifyTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }
}
