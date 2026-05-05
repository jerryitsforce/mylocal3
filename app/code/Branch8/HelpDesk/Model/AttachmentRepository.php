<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\AttachmentRepositoryInterface;
use Branch8\HelpDesk\Api\Data\AttachmentInterface;
use Branch8\HelpDesk\Api\Data\AttachmentSearchResultInterfaceFactory;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * AttachmentRepository class
 */
class AttachmentRepository implements AttachmentRepositoryInterface
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
     * @var AttachmentSearchResultInterfaceFactory
     */
    private $searchResultsFactory;
    /**
     * @var ResourceModel\Attachment
     */
    private $resource;

    /**
     * @param ResourceModel\Message\CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param AttachmentSearchResultInterfaceFactory $searchResultsInterfaceFactory
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param ResourceModel\Attachment $resource
     */
    public function __construct(
        ResourceModel\Message\CollectionFactory $collectionFactory,
        CollectionProcessorInterface            $collectionProcessor,
        JoinProcessorInterface                  $extensionAttributesJoinProcessor,
        AttachmentSearchResultInterfaceFactory  $searchResultsInterfaceFactory,
        ExtensibleDataObjectConverter           $extensibleDataObjectConverter,
        ResourceModel\Attachment                $resource
    )
    {
        $this->resource = $resource;
        $this->searchResultsFactory = $searchResultsInterfaceFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * AssignAttachmentToMessage
     * @param int $messageId
     * @param AttachmentInterface $attachment
     * @return AttachmentInterface
     * @throws CouldNotSaveException
     */
    public function assignAttachmentToMessage(int $messageId, AttachmentInterface $attachment)
    {
        try {
            /**
             * @var AttachmentInterface Ticket
             */
            $attachment->setMessageId($messageId);
            $this->resource->save($attachment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Attachment: %1',
                $exception->getMessage()
            ));
        }
        return $attachment;
    }

    /**
     * GetList
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResult = $this->searchResultsFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }

}

