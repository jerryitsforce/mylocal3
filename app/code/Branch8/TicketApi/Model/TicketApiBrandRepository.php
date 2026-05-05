<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiBrandSearchResultsInterfaceFactory as SearchResultsInterfaceFactory;
use Branch8\TicketApi\Api\TicketApiBrandRepositoryInterface as RepositoryInterface;
use Branch8\TicketApi\Model\ResourceModel\TicketApiBrand as ResourceModel;
use Branch8\TicketApi\Model\ResourceModel\TicketApiBrand\Collection;
use Branch8\TicketApi\Model\ResourceModel\TicketApiBrand\CollectionFactory;
use Branch8\TicketApi\Model\TicketApiBrand as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Branch8\TicketApi\Api\Data\TicketApiBrandInterface as ModelInterface;

class TicketApiBrandRepository implements RepositoryInterface
{
    /** @var ResourceModel */
    protected $resource;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var SearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        ResourceModel $resource,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory
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
        ModelInterface $record
    ) {
        try {
            /** @var Model $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the TicketApiBrand: %1',
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
     * 以entity_id獲取對應品牌物件
     * @param int $id
     * @return Model|null
     */
    public function getById(int $id): ?Model
    {
        /** @var Model $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(Model::ENTITY_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 以品牌代號(brand_code)獲取對應品牌物件
     * @param string $brandCode
     * @return Model|null
     */
    public function getSettingByBrandCode(string $brandCode): ?Model
    {
        /** @var Model $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(Model::BRAND_CODE, $brandCode)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 獲取啟用中的品牌
     * @return \Branch8\TicketApi\Model\ResourceModel\TicketApiBrand\Collection
     */
    public function getActiveBrand(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(Model::IS_ACTIVE, Model::IS_ACTIVE_TRUE);
        $collection->load();

        return $collection;
    }
}
