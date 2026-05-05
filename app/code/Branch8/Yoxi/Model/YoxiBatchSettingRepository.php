<?php

namespace Branch8\Yoxi\Model;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;
use Branch8\Yoxi\Api\Data\YoxiBatchSettingSearchResultsInterfaceFactory;
use Branch8\Yoxi\Api\YoxiBatchSettingRepositoryInterface;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\CollectionFactory as YoxiBatchSettingCollectionFactory;
use Branch8\Yoxi\Model\YoxiBatchSetting as YoxiBatchSettingModel;
use Branch8\Yoxi\Model\YoxiBatchSettingFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class YoxiBatchSettingRepository implements YoxiBatchSettingRepositoryInterface
{
    /** @var YoxiBatchSetting */
    protected $resource;

    /** @var YoxiBatchSettingCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var YoxiBatchSettingSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var YoxiBatchSettingFactory */
    private $batchSettingFactory;

    public function __construct(
        YoxiBatchSetting $resource,
        YoxiBatchSettingCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        YoxiBatchSettingSearchResultsInterfaceFactory $searchResultsFactory,
        YoxiBatchSettingFactory $batchSettingFactory
    ) {
        $this->resource             = $resource;
        $this->collectionFactory    = $collectionFactory;
        $this->collectionProcessor  = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->batchSettingFactory  = $batchSettingFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(BatchImportTicketBatchSettingModelInterface $record): void
    {
        try {
            /** @var \Branch8\Yoxi\Model\YoxiBatchSetting $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the YoxiBatchSetting: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function createNew(): BatchImportTicketBatchSettingModelInterface
    {
        return $this->batchSettingFactory->create();
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

    public function getById(int $id): null | YoxiBatchSettingModel
    {
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiBatchSettingModel::SETTING_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    public function getBatchSettingByBatchCode(string $batchCode): YoxiBatchSettingModel | null
    {
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiBatchSettingModel::BATCH_CODE, $batchCode)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * @param string $batchCode
     * @param int    $productId
     * @return BatchImportTicketBatchSettingModelInterface|null
     */
    public function getBatchSettingByBatchCodeAndProductId(string $batchCode, int $productId): ?BatchImportTicketBatchSettingModelInterface
    {
        /** @var BatchImportTicketBatchSettingModelInterface $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(YoxiBatchSettingModel::BATCH_CODE, $batchCode)
            ->addFieldToFilter(YoxiBatchSettingModel::BELONG_TO_PRODUCT_ID, $productId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
