<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiMerchantInterface as ModelInterface;
use Branch8\TicketApi\Api\Data\TicketApiMerchantSearchResultsInterfaceFactory as SearchResultsInterfaceFactory;
use Branch8\TicketApi\Api\TicketApiMerchantRepositoryInterface as RepositoryInterface;
use Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant as ResourceModel;
use Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant\CollectionFactory;
use Branch8\TicketApi\Model\TicketApiMerchant as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class TicketApiMerchantRepository implements RepositoryInterface
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
                    'Could not save the TicketApiMerchant: %1',
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
     * 以entity_id獲取對應merchant物件
     * @param int $id
     * @param int $isActive
     * @return \Magento\Framework\DataObject|null
     */
    public function getById(int $id, int $isActive = null): ?Model
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(Model::ENTITY_ID, $id);

        if (!is_null($isActive)) {
            $collection->addFieldToFilter(Model::IS_ACTIVE, $isActive);
        }

        $result = $collection->getFirstItem();

        return is_null($result->getId()) ? null : $result;
    }

    /**
     * 以merchant_id獲取對應merchant物件
     * @param string $merchantId
     * @param int $isActive
     * @return \Magento\Framework\DataObject|null
     */
    public function getSettingByMerchantId(string $merchantId, int $isActive = null): ?Model
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(Model::MERCHANT_ID, $merchantId);

        if (!is_null($isActive)) {
            $collection->addFieldToFilter(Model::IS_ACTIVE, $isActive);
        }

        $result = $collection->getFirstItem();

        return is_null($result->getId()) ? null : $result;
    }
}
