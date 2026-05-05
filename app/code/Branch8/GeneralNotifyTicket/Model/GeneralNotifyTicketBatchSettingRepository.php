<?php

namespace Branch8\GeneralNotifyTicket\Model;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;
use Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketBatchSettingSearchResultsInterfaceFactory;
use Branch8\GeneralNotifyTicket\Api\GeneralNotifyTicketBatchSettingRepositoryInterface;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketBatchSetting;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketBatchSetting\CollectionFactory as GeneralNotifyTicketBatchSettingCollectionFactory;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSetting as GeneralNotifyTicketBatchSettingModel;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSettingFactory as BatchSettingFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class GeneralNotifyTicketBatchSettingRepository implements GeneralNotifyTicketBatchSettingRepositoryInterface
{

    /** @var GeneralNotifyTicketBatchSetting */
    protected $resource;

    /** @var GeneralNotifyTicketBatchSettingCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var GeneralNotifyTicketBatchSettingSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var BatchSettingFactory */
    private $batchSettingFactory;

    public function __construct(
        GeneralNotifyTicketBatchSetting $resource,
        GeneralNotifyTicketBatchSettingCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        GeneralNotifyTicketBatchSettingSearchResultsInterfaceFactory $searchResultsFactory,
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
            /** @var GeneralNotifyTicketBatchSettingModel $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the GeneralNotifyTicketBatchSetting: %1',
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

    public function createNew(): BatchImportTicketBatchSettingModelInterface
    {
        /** @var BatchImportTicketBatchSettingModelInterface $model */
        $model = $this->batchSettingFactory->create();

        return $model;
    }

    public function getById(int $id): ?GeneralNotifyTicketBatchSettingModel
    {
        /** @var GeneralNotifyTicketBatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketBatchSettingModel::SETTING_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    public function getBatchSettingByBatchCode(string $batchCode): ?GeneralNotifyTicketBatchSettingModel
    {
        /** @var GeneralNotifyTicketBatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketBatchSettingModel::BATCH_CODE, $batchCode)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    public function getBatchSettingByBatchCodeAndProductId(string $batchCode, int $productId): ?BatchImportTicketBatchSettingModelInterface
    {
        /** @var BatchImportTicketBatchSettingModelInterface $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNotifyTicketBatchSettingModel::BATCH_CODE, $batchCode)
            ->addFieldToFilter(GeneralNotifyTicketBatchSettingModel::BELONG_TO_PRODUCT_ID, $productId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
