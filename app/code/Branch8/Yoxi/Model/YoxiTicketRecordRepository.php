<?php

namespace Branch8\Yoxi\Model;

use Branch8\Yoxi\Api\Data\YoxiTicketRecordSearchResultsInterfaceFactory;
use Branch8\Yoxi\Api\YoxiTicketRecordRepositoryInterface;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection as YoxiTicketRecordCollection;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\CollectionFactory as YoxiTicketRecordCollectionFactory;
use Branch8\Yoxi\Model\YoxiBatchSetting as YoxiBatchSettingModel;
use Branch8\Yoxi\Model\YoxiTicketRecord as YoxiTicketRecordModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class YoxiTicketRecordRepository implements YoxiTicketRecordRepositoryInterface
{

    /** @var YoxiTicketRecord */
    protected $resource;

    /** @var YoxiTicketRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var YoxiTicketRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        YoxiTicketRecord $resource,
        YoxiTicketRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        YoxiTicketRecordSearchResultsInterfaceFactory $searchResultsFactory
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
        \Branch8\Yoxi\Api\Data\YoxiTicketRecordInterface $record
    ) {
        try {
            /** @var \Branch8\Yoxi\Model\YoxiTicketRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the YoxiTicketRecord: %1',
                $exception->getMessage()
            ));
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
     *
     * @param integer $productId
     * @param integer $batchSettingId
     * @return YoxiTicketRecordCollection
     */
    public function getUnsoldRecordsByProductIdAndBatchCode(int $productId, int $batchSettingId): YoxiTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(YoxiTicketRecordModel::BATCH_SETTING_ID, $batchSettingId)
            ->addFieldToFilter(YoxiTicketRecordModel::STATUS, YoxiTicketRecordModel::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 根據當下時間獲取商品可用的YOXI票券庫存
     *
     * @param integer $productId
     * @return YoxiTicketRecordCollection
     */
    public function getAvailableForSaleRecordsByProductId(int $productId): YoxiTicketRecordCollection
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $currentDatetime = $taiwanDateObj->format("Y-m-d H:i:s");

        $batchSettingIdFieldName = YoxiTicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = YoxiBatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = YoxiBatchSettingModel::SETTING_ID;

        $collection = $this->collectionFactory->create()
            ->join(
                [YoxiBatchSettingModel::TABLE_NAME => YoxiBatchSettingModel::TABLE_NAME],
                // "main_table.batch_setting_id = yoxi_batch_setting.setting_id"
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}"
            )
            ->addFieldToFilter(
                YoxiBatchSettingModel::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                YoxiBatchSettingModel::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            );

        $collection->addFieldToFilter("main_table." . YoxiTicketRecordModel::BELONG_TO_PRODUCT_ID, $productId)
            ->addFieldToFilter(YoxiTicketRecordModel::STATUS, YoxiTicketRecordModel::STATUS_IMPORTED);

        $collection->load();

        return $collection;
    }

    /**
     * 以YOXI序號取得票券紀錄
     *
     * @param string $serialNumber
     * @return null|YoxiTicketRecordCollection
     */
    public function getRecordBySerialNumber(string $serialNumber): null|YoxiTicketRecordModel
    {
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiTicketRecordModel::SERIAL_NUMBER, $serialNumber)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 根據sales_order_item_id找到對應的YOXI票券紀錄
     *
     * @param integer $orderItemId
     * @return YoxiTicketRecordCollection
     */
    public function getRecordsByOrderItemId(int $orderItemId): YoxiTicketRecordCollection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiTicketRecordModel::SALES_ORDER_ITEM_ID, $orderItemId);

        $collection->load();

        return $collection;
    }
}
