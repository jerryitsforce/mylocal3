<?php

namespace Branch8\GeneralNotifyTicket\Model;

use Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordSearchResultsInterfaceFactory;
use Branch8\GeneralNotifyTicket\Api\GeneralNotifyTicketRecordRepositoryInterface;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\Collection as GeneralNotifyTicketRecordCollection;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\CollectionFactory as GeneralNotifyTicketRecordCollectionFactory;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSetting as GeneralNotifyTicketBatchSettingModel;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord as GeneralNotifyTicketRecordModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class GeneralNotifyTicketRecordRepository implements GeneralNotifyTicketRecordRepositoryInterface
{
    /** @var GeneralNotifyTicketRecord */
    protected $resource;

    /** @var GeneralNotifyTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var GeneralNotifyTicketRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        GeneralNotifyTicketRecord $resource,
        GeneralNotifyTicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        GeneralNotifyTicketRecordSearchResultsInterfaceFactory $searchResultsFactory
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
        \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordInterface $record
    ) {
        try {
            /** @var \Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the GeneralNotifyTicketRecord: %1',
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

    public function getByIdWithBatchingData(int $recordId): null|GeneralNotifyTicketRecordModel
    {
        $collection = $this->collectionFactory->create();

        $recordBatchSettingIdName = GeneralNotifyTicketRecordModel::BATCH_SETTING_ID;
        $batchTableName           = GeneralNotifyTicketBatchSettingModel::TABLE_NAME;
        $batchTableIdName         = GeneralNotifyTicketBatchSettingModel::SETTING_ID;

        $collection->getselect()->joinLeft(
            [GeneralNotifyTicketBatchSettingModel::TABLE_NAME => GeneralNotifyTicketBatchSettingModel::TABLE_NAME],
            "main_table.{$recordBatchSettingIdName} = {$batchTableName}.{$batchTableIdName}",
            [
                GeneralNotifyTicketBatchSettingModel::BATCH_CODE,
                GeneralNotifyTicketBatchSettingModel::SELLER_ID
            ]
        );

        $collection->addFieldToFilter(GeneralNotifyTicketRecordModel::RECORD_ID, $recordId);

        $result = $collection->getFirstItem();

        return is_null($result->getId()) ? null : $result;
    }

    /**
     * 以Magento商品id和貨號紀錄id取得未售出(初始匯入狀態)的票券紀錄
     * @param integer $productId
     * @param integer $batchSettingId
     * @return GeneralNotifyTicketRecordCollection
     */
    public function getUnsoldRecordsByProductIdAndBatchCode(int $productId, int $batchSettingId): GeneralNotifyTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::BATCH_SETTING_ID, $batchSettingId)
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::STATUS, TicketStatus::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 根據當下時間獲取商品可用的票券庫存
     * @param integer $productId
     * @return GeneralNotifyTicketRecordCollection
     */
    public function getAvailableForSaleRecordsByProductId(int $productId): GeneralNotifyTicketRecordCollection
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $currentDatetime = $taiwanDateObj->format("Y-m-d H:i:s");

        $batchSettingIdFieldName = GeneralNotifyTicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = GeneralNotifyTicketBatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = GeneralNotifyTicketBatchSettingModel::SETTING_ID;

        $collection = $this->collectionFactory->create()
            ->join(
                [GeneralNotifyTicketBatchSettingModel::TABLE_NAME => GeneralNotifyTicketBatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}"
            )
            ->addFieldToFilter(
                GeneralNotifyTicketBatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                GeneralNotifyTicketBatchSettingModel::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            );

        $collection->addFieldToFilter("main_table." . GeneralNotifyTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::STATUS, GeneralNotifyTicketRecordModel::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 以序號取得票券紀錄
     * @param string $serialNumber
     * @return GeneralNotifyTicketRecordModel|null
     */
    public function getRecordBySerialNumber(string $serialNumber): ?GeneralNotifyTicketRecordModel
    {
        /** @var GeneralNotifyTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
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
     * @return GeneralNotifyTicketRecordModel|null
     */
    public function getRecordBySerialNumberAndSellerId(string $serialNumber, int $sellerId): ?GeneralNotifyTicketRecordModel
    {
        /** @var GeneralNotifyTicketRecordModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SELLER_ID, $sellerId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 以序號和sellerId取得票券紀錄
     * @param string $serialNumber
     * @param string $sellerIds
     * @return GeneralNotifyTicketRecordCollection
     */
    public function getRecordBySerialNumberAndSellerIdsString(string $serialNumber, string $sellerIds): GeneralNotifyTicketRecordCollection
    {
        $sellerIdArray = explode(',', $sellerIds);

        /** @var GeneralNotifyTicketRecordModel $result */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SELLER_ID, ['in' => $sellerIdArray]);

        return $collection;
    }

    /**
     * 根據sales_order_item_id找到對應的票券紀錄
     *
     * @param integer $orderItemId
     * @return GeneralNotifyTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): GeneralNotifyTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }
}
