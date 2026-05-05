<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Model;

use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterfaceFactory;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsSearchResultsInterfaceFactory;
use Ecpay\Invoice\Api\HotaiOrderItemInvoiceLogsRepositoryInterface;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs as ResourceHotaiOrderItemInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\CollectionFactory as HotaiOrderItemInvoiceLogsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class HotaiOrderItemInvoiceLogsRepository implements HotaiOrderItemInvoiceLogsRepositoryInterface
{

    /**
     * @var HotaiOrderItemInvoiceLogsInterfaceFactory
     */
    protected $hotaiOrderItemInvoiceLogsFactory;

    /**
     * @var HotaiOrderItemInvoiceLogsCollectionFactory
     */
    protected $hotaiOrderItemInvoiceLogsCollectionFactory;

    /**
     * @var ResourceHotaiOrderItemInvoiceLogs
     */
    protected $resource;

    /**
     * @var HotaiOrderItemInvoiceLogs
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceHotaiOrderItemInvoiceLogs $resource
     * @param HotaiOrderItemInvoiceLogsInterfaceFactory $hotaiOrderItemInvoiceLogsFactory
     * @param HotaiOrderItemInvoiceLogsCollectionFactory $hotaiOrderItemInvoiceLogsCollectionFactory
     * @param HotaiOrderItemInvoiceLogsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceHotaiOrderItemInvoiceLogs $resource,
        HotaiOrderItemInvoiceLogsInterfaceFactory $hotaiOrderItemInvoiceLogsFactory,
        HotaiOrderItemInvoiceLogsCollectionFactory $hotaiOrderItemInvoiceLogsCollectionFactory,
        HotaiOrderItemInvoiceLogsSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->hotaiOrderItemInvoiceLogsFactory = $hotaiOrderItemInvoiceLogsFactory;
        $this->hotaiOrderItemInvoiceLogsCollectionFactory = $hotaiOrderItemInvoiceLogsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
    ) {
        try {
            $this->resource->save($hotaiOrderItemInvoiceLogs);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the hotaiOrderItemInvoiceLogs: %1',
                $exception->getMessage()
            ));
        }
        return $hotaiOrderItemInvoiceLogs;
    }

    /**
     * @inheritDoc
     */
    public function get($hotaiOrderItemInvoiceLogsId)
    {
        $hotaiOrderItemInvoiceLogs = $this->hotaiOrderItemInvoiceLogsFactory->create();
        $this->resource->load($hotaiOrderItemInvoiceLogs, $hotaiOrderItemInvoiceLogsId);
        if (!$hotaiOrderItemInvoiceLogs->getId()) {
            throw new NoSuchEntityException(__('hotai_order_item_invoice_logs with id "%1" does not exist.', $hotaiOrderItemInvoiceLogsId));
        }
        return $hotaiOrderItemInvoiceLogs;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->hotaiOrderItemInvoiceLogsCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
    ) {
        try {
            $hotaiOrderItemInvoiceLogsModel = $this->hotaiOrderItemInvoiceLogsFactory->create();
            $this->resource->load($hotaiOrderItemInvoiceLogsModel, $hotaiOrderItemInvoiceLogs->getHotaiOrderItemInvoiceLogsId());
            $this->resource->delete($hotaiOrderItemInvoiceLogsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the hotai_order_item_invoice_logs: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($hotaiOrderItemInvoiceLogsId)
    {
        return $this->delete($this->get($hotaiOrderItemInvoiceLogsId));
    }
}

