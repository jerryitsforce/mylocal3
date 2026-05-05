<?php

namespace Branch8\FamilyBonusPin\Model;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;
use Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingSearchResultsInterfaceFactory as BatchSettingSearchResultsFactory;
use Branch8\FamilyBonusPin\Api\FamilyBonusPinBatchSettingRepositoryInterface as BatchSettingRepositoryInterface;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting as BatchSettingResource;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting\CollectionFactory as BatchSettingCollectionFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSettingFactory as BatchSettingFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSettingModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class FamilyBonusPinBatchSettingRepository implements BatchSettingRepositoryInterface
{
    /** @var BatchSettingResource */
    protected $resource;

    /** @var BatchSettingCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var BatchSettingSearchResultsFactory */
    protected $searchResultsFactory;

    /** @var BatchSettingFactory */
    private $batchSettingFactory;

    public function __construct(
        BatchSettingResource $resource,
        BatchSettingCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        BatchSettingSearchResultsFactory $searchResultsFactory,
        BatchSettingFactory $batchSettingFactory
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
            /** @var BatchSettingModel $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the FamilyBonusPinBatchSetting: %1',
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
     * @inheritdoc
     */
    public function createNew(): BatchImportTicketBatchSettingModelInterface
    {
        /** @var BatchImportTicketBatchSettingModelInterface $model */
        $model = $this->batchSettingFactory->create();

        return $model;
    }

    public function getById(int $id): ?BatchSettingModel
    {
        /** @var BatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(BatchSettingModel::SETTING_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    public function getBatchSettingByBatchCode(string $batchCode): ?BatchSettingModel
    {
        /** @var BatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(BatchSettingModel::BATCH_CODE, $batchCode)
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
            ->addFieldToFilter(BatchSettingModel::BATCH_CODE, $batchCode)
            ->addFieldToFilter(BatchSettingModel::BELONG_TO_PRODUCT_ID, $productId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
