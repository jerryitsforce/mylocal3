<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Model;

use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterfaceFactory;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsSearchResultsInterfaceFactory;
use Ecpay\Invoice\Api\HotaiOrderInvoiceLogsRepositoryInterface;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs as ResourceHotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as HotaiOrderInvoiceLogsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class HotaiOrderInvoiceLogsRepository implements HotaiOrderInvoiceLogsRepositoryInterface
{

    /**
     * @var HotaiOrderInvoiceLogsCollectionFactory
     */
    protected $hotaiOrderInvoiceLogsCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var HotaiOrderInvoiceLogs
     */
    protected $searchResultsFactory;

    /**
     * @var ResourceHotaiOrderInvoiceLogs
     */
    protected $resource;

    /**
     * @var HotaiOrderInvoiceLogsInterfaceFactory
     */
    protected $hotaiOrderInvoiceLogsFactory;


    /**
     * @param ResourceHotaiOrderInvoiceLogs $resource
     * @param HotaiOrderInvoiceLogsInterfaceFactory $hotaiOrderInvoiceLogsFactory
     * @param HotaiOrderInvoiceLogsCollectionFactory $hotaiOrderInvoiceLogsCollectionFactory
     * @param HotaiOrderInvoiceLogsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceHotaiOrderInvoiceLogs $resource,
        HotaiOrderInvoiceLogsInterfaceFactory $hotaiOrderInvoiceLogsFactory,
        HotaiOrderInvoiceLogsCollectionFactory $hotaiOrderInvoiceLogsCollectionFactory,
        HotaiOrderInvoiceLogsSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->hotaiOrderInvoiceLogsFactory = $hotaiOrderInvoiceLogsFactory;
        $this->hotaiOrderInvoiceLogsCollectionFactory = $hotaiOrderInvoiceLogsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
    ) {
        try {
            $this->resource->save($hotaiOrderInvoiceLogs);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the hotaiOrderInvoiceLogs: %1',
                $exception->getMessage()
            ));
        }
        return $hotaiOrderInvoiceLogs;
    }

    /**
     * @inheritDoc
     */
    public function get($hotaiOrderInvoiceLogsId)
    {
        $hotaiOrderInvoiceLogs = $this->hotaiOrderInvoiceLogsFactory->create();
        $this->resource->load($hotaiOrderInvoiceLogs, $hotaiOrderInvoiceLogsId);
        if (!$hotaiOrderInvoiceLogs->getId()) {
            throw new NoSuchEntityException(__('hotai_order_invoice_logs with id "%1" does not exist.', $hotaiOrderInvoiceLogsId));
        }
        return $hotaiOrderInvoiceLogs;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->hotaiOrderInvoiceLogsCollectionFactory->create();
        
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
        HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
    ) {
        try {
            $hotaiOrderInvoiceLogsModel = $this->hotaiOrderInvoiceLogsFactory->create();
            $this->resource->load($hotaiOrderInvoiceLogsModel, $hotaiOrderInvoiceLogs->getHotaiOrderInvoiceLogsId());
            $this->resource->delete($hotaiOrderInvoiceLogsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the hotai_order_invoice_logs: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($hotaiOrderInvoiceLogsId)
    {
        return $this->delete($this->get($hotaiOrderInvoiceLogsId));
    }
}

