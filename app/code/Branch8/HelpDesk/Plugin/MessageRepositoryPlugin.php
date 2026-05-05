<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Plugin;

use Branch8\HelpDesk\Api\AttachmentRepositoryInterface;
use Branch8\HelpDesk\Api\Data\LessDataMessageInterface;
use Branch8\HelpDesk\Api\Data\MessageSearchResultInterface;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterFactory;

/**
 * Plugin Add attachment to message
 */
class MessageRepositoryPlugin
{
    /**
     * @var \Branch8\HelpDesk\Api\Data\LessDataMessageExtensionFactory
     */
    private $extensionFactory;
    /**
     * @var AttachmentRepositoryInterface
     */
    private $attachmentRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterFactory
     */
    private $filterFactory;

    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    /**
     * @param \Branch8\HelpDesk\Api\Data\LessDataMessageExtensionFactory $factory
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterFactory $filterFactory
     */
    public function __construct(
        \Branch8\HelpDesk\Api\Data\LessDataMessageExtensionFactory $factory,
        AttachmentRepositoryInterface                              $attachmentRepository,
        SearchCriteriaBuilderFactory                               $searchCriteriaBuilderFactory,
        FilterFactory                                              $filterFactory
    )
    {
        $this->filterFactory = $filterFactory;
        $this->attachmentRepository = $attachmentRepository;
        $this->extensionFactory = $factory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * @param MessageRepositoryInterface $messageRepository
     * @param MessageSearchResultInterface $result
     * @return MessageSearchResultInterface
     */
    public function afterGetList(MessageRepositoryInterface $messageRepository, MessageSearchResultInterface $result)
    {
        foreach ($result->getItems() as $item) {
            $this->afterGet($messageRepository, $item);
        }
        return $result;
    }

    /**
     * @param MessageRepositoryInterface $messageRepository
     * @param LessDataMessageInterface $result
     * @return LessDataMessageInterface
     */
    public function afterGet(MessageRepositoryInterface $messageRepository, LessDataMessageInterface $result)
    {
        /** @var \Branch8\HelpDesk\Api\Data\LessDataMessageExtension $extensionAttributes */
        $extensionAttributes = $result->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }
        /**
         * @var $searchResult \Branch8\HelpDesk\Model\ResourceModel\Attachment\Collection
         */

        $searchResult = $this->attachmentRepository->getList(
            $this->searchCriteriaBuilderFactory->create()->addFilter(
                $this->filterFactory->create()
                    ->setField('message_id')
                    ->setValue((int)$result->getId())
            )->addSortOrder('created_at', 'DESC')
                ->setCurrentPage(1)
                ->setPageSize(5)
                ->create()
        );
        $extensionAttributes->setAttachments($searchResult->getItems());
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }
}
