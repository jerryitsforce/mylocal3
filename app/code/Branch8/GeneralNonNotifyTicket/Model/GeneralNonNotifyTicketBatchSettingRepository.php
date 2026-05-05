<?php

namespace Branch8\GeneralNonNotifyTicket\Model;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;
use Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketBatchSettingSearchResultsInterfaceFactory;
use Branch8\GeneralNonNotifyTicket\Api\GeneralNonNotifyTicketBatchSettingRepositoryInterface;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketBatchSetting;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketBatchSetting\CollectionFactory as GeneralNonNotifyTicketBatchSettingCollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting as GeneralNonNotifyTicketBatchSettingModel;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSettingFactory as BatchSettingFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class GeneralNonNotifyTicketBatchSettingRepository implements GeneralNonNotifyTicketBatchSettingRepositoryInterface
{

    /** @var GeneralNonNotifyTicketBatchSetting */
    protected $resource;

    /** @var GeneralNonNotifyTicketBatchSettingCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var GeneralNonNotifyTicketBatchSettingSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var BatchSettingFactory */
    private $batchSettingFactory;

    public function __construct(
        GeneralNonNotifyTicketBatchSetting $resource,
        GeneralNonNotifyTicketBatchSettingCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        GeneralNonNotifyTicketBatchSettingSearchResultsInterfaceFactory $searchResultsFactory,
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
            /** @var GeneralNonNotifyTicketBatchSettingModel $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the GeneralNonNotifyTicketBatchSetting: %1',
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

    public function getById(int $id): ?GeneralNonNotifyTicketBatchSettingModel
    {
        /** @var GeneralNonNotifyTicketBatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketBatchSettingModel::SETTING_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    public function getBatchSettingByBatchCode(string $batchCode): ?GeneralNonNotifyTicketBatchSettingModel
    {
        /** @var GeneralNonNotifyTicketBatchSettingModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(GeneralNonNotifyTicketBatchSettingModel::BATCH_CODE, $batchCode)
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
            ->addFieldToFilter(GeneralNonNotifyTicketBatchSettingModel::BATCH_CODE, $batchCode)
            ->addFieldToFilter(GeneralNonNotifyTicketBatchSettingModel::BELONG_TO_PRODUCT_ID, $productId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
