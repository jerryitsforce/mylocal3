<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\MessageSearchResultInterfaceFactory as SearchResultFactory;
use Branch8\HelpDesk\Api\Data\MessageInterface;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Message Repository Class
 */
class MessageRepository implements MessageRepositoryInterface
{
    private $collectionProcessor;
    /**
     * @var ResourceModel\Message\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;
    /**
     * @var ExtensibleDataObjectConverter
     */
    private $extensibleDataObjectConverter;
    /**
     * @var
     */
    private $resource;

    private $searchResultInterfaceFactory;

    public MessageFactory $messageFactory;

    /**
     * @param ResourceModel\Message\CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param MessageFactory $messageFactory
     * @param ResourceModel\Message $resource
     * @param SearchResultFactory $searchResultsFactory
     */
    public function __construct(
        ResourceModel\Message\CollectionFactory $collectionFactory,
        CollectionProcessorInterface            $collectionProcessor,
        JoinProcessorInterface                  $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter           $extensibleDataObjectConverter,
        \Branch8\HelpDesk\Model\MessageFactory  $messageFactory,
        ResourceModel\Message                   $resource,
        SearchResultFactory                     $searchResultsFactory
    )
    {
        $this->messageFactory=$messageFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->searchResultInterfaceFactory = $searchResultsFactory;
    }

    /**
     * GetList
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResult = $this->searchResultInterfaceFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }

    /**
     * @param MessageInterface $message
     * @return MessageInterface
     * @throws CouldNotSaveException
     */
    public function save(MessageInterface $message)
    {
        try {
            /**
             * @var MessageInterface $message
             */
            $this->resource->save($message);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Message : %1',
                $exception->getMessage()
            ));
        }
        return $message;
    }

    /**
     * @param int $id
     * @return MessageInterface
     * @throws NoSuchEntityException
     */
    public function get(int $id)
    {
        $message = $this->messageFactory->create()->load($id);
        if (!$message->getId()) {
            throw new NoSuchEntityException();
        }
        return $message;
    }
}

