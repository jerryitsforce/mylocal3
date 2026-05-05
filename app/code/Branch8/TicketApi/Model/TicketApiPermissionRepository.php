<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiPermissionInterface as ModelInterface;
use Branch8\TicketApi\Api\Data\TicketApiPermissionSearchResultsInterfaceFactory as SearchResultsInterfaceFactory;
use Branch8\TicketApi\Api\TicketApiPermissionRepositoryInterface as RepositoryInterface;
use Branch8\TicketApi\Model\ResourceModel\TicketApiPermission as ResourceModel;
use Branch8\TicketApi\Model\ResourceModel\TicketApiPermission\Collection;
use Branch8\TicketApi\Model\ResourceModel\TicketApiPermission\CollectionFactory;
use Branch8\TicketApi\Model\TicketApiPermission as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class TicketApiPermissionRepository implements RepositoryInterface
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
                    'Could not save the TicketApiPermission: %1',
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
     * 以entity_id獲取對應的權限物件
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
     * 以brand_id和merchant_id獲取對應的權限物件
     * @param int $brandId
     * @param int $merchantId
     * @return Model|null
     */
    public function getSettingByBrandIdAndMerchantId(int $brandId, int $merchantId): ?Model
    {
        /** @var Model $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(Model::BRAND_ID, $brandId)
            ->addFieldToFilter(Model::MERCHANT_ID, $merchantId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * 以merchant_id獲取merchant所擁有的權限collection
     * @param int $merchantId
     * @return \Branch8\TicketApi\Model\ResourceModel\TicketApiPermission\Collection
     */
    public function getPermissionByMerchantId(int $merchantId): Collection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(Model::MERCHANT_ID, $merchantId)
            ->load();

        return $collection;
    }
}
