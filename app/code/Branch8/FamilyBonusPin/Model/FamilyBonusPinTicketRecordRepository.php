<?php

namespace Branch8\FamilyBonusPin\Model;

use Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordSearchResultsInterfaceFactory as TicketRecordSearchResultsFactory;
use Branch8\FamilyBonusPin\Api\FamilyBonusPinTicketRecordRepositoryInterface as TicketRecordRepositoryInterface;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord as TicketRecordResource;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\Collection as TicketRecordCollection;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\CollectionFactory as TicketRecordCollectionFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSettingModel;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as TicketRecordModel;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class FamilyBonusPinTicketRecordRepository implements TicketRecordRepositoryInterface
{

    /** @var TicketRecordResource */
    protected $resource;

    /** @var TicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var TicketRecordSearchResultsFactory */
    protected $searchResultsFactory;

    public function __construct(
        TicketRecordResource $resource,
        TicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        TicketRecordSearchResultsFactory $searchResultsFactory
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
        \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordInterface $record
    ) {
        try {
            /** @var TicketRecordModel $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the FamilyBonusPinTicketRecord: %1',
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

    /**
     * 以Magento商品id和貨號紀錄id取得未售出(初始匯入狀態)的票券紀錄
     * @param integer $productId
     * @param integer $batchSettingId
     * @return TicketRecordCollection
     */
    public function getUnsoldRecordsByProductIdAndBatchCode(int $productId, int $batchSettingId): TicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(TicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(TicketRecordModel::BATCH_SETTING_ID, $batchSettingId)
            ->addFieldToFilter(TicketRecordModel::STATUS, TicketStatus::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 根據當下時間獲取商品可用的票券庫存
     * @param integer $productId
     * @return TicketRecordCollection
     */
    public function getAvailableForSaleRecordsByProductId(int $productId): TicketRecordCollection
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $currentDatetime = $taiwanDateObj->format("Y-m-d H:i:s");

        $batchSettingIdFieldName = TicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = BatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = BatchSettingModel::SETTING_ID;

        $collection = $this->collectionFactory->create()
            ->join(
                [BatchSettingModel::TABLE_NAME => BatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}"
            )
            ->addFieldToFilter(
                BatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                BatchSettingModel::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            );

        $collection
            ->addFieldToFilter("main_table." . TicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(TicketRecordModel::STATUS, TicketStatus::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 以序號取得票券紀錄
     * @param string $serialNumber
     * @return null|TicketRecordModel
     */
    public function getRecordBySerialNumber(string $serialNumber): null|TicketRecordModel
    {
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(TicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }


    /**
     * 根據sales_order_item_id找到對應的票券紀錄
     * @param integer $orderItemId
     * @return TicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): TicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(TicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }
}
