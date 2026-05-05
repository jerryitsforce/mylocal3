<?php
/**
 * Copyright © dev@branch8 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Model;

use Branch8\HotaiPay\Api\Data\MessageInterface;
use Branch8\HotaiPay\Api\Data\MessageInterfaceFactory;
use Branch8\HotaiPay\Api\Data\MessageSearchResultsInterfaceFactory;
use Branch8\HotaiPay\Api\MessageRepositoryInterface;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Model\ResourceModel\Message as ResourceMessage;
use Branch8\HotaiPay\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class MessageRepository implements MessageRepositoryInterface
{

    const TYPE_CHECKOUT = 'Checkout';
    const TYPE_CREDITCARD = 'Creditcard';
    const TYPE_PAYMENT = 'Payment';

    /**
     * @var ResourceMessage
     */
    protected $resource;

    /**
     * @var MessageInterfaceFactory
     */
    protected $messageFactory;

    /**
     * @var MessageCollectionFactory
     */
    protected $messageCollectionFactory;

    /**
     * @var Message
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;
    private HotaiPayLogHelper $hotaiPayLogHelper;


    /**
     * @param ResourceMessage $resource
     * @param MessageInterfaceFactory $messageFactory
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param MessageSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param HotaiPayLogHelper $hotaiPayLogHelper
     */
    public function __construct(
        ResourceMessage $resource,
        MessageInterfaceFactory $messageFactory,
        MessageCollectionFactory $messageCollectionFactory,
        MessageSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->resource = $resource;
        $this->messageFactory = $messageFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    /**
     * @inheritDoc
     */
    public function save(MessageInterface $message)
    {
        try {
            $this->resource->save($message);
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            throw new CouldNotSaveException(__(
                'Could not save the message: %1',
                $exception->getMessage()
            ));
        }
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function get($messageId)
    {
        $message = $this->messageFactory->create();
        $this->resource->load($message, $messageId);
        if (!$message->getId()) {
            throw new NoSuchEntityException(__('Message with id "%1" does not exist.', $messageId));
        }
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->messageCollectionFactory->create();
        
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
    public function delete(MessageInterface $message)
    {
        try {
            $messageModel = $this->messageFactory->create();
            $this->resource->load($messageModel, $message->getMessageId());
            $this->resource->delete($messageModel);
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            throw new CouldNotDeleteException(__(
                'Could not delete the Message: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($messageId)
    {
        return $this->delete($this->get($messageId));
    }
}

