<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Plugin;

use Branch8\HelpDesk\Api\AttachmentRepositoryInterface;
use Branch8\HelpDesk\Api\Data\LessDataMessageInterface;
use Branch8\HelpDesk\Api\Data\MessageSearchResultInterface;
use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterFactory;

/**
 * Plugin Add attachment to message
 */
class TicketRepositoryPlugin
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
     * @param \Branch8\HelpDesk\Api\Data\TicketExtensionInterfaceFactory $factory
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterFactory $filterFactory
     */
    public function __construct(
        \Branch8\HelpDesk\Api\Data\TicketExtensionInterfaceFactory $factory,
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
     * @param TicketRepositoryInterface $ticketRepository
     * @param TicketInterface $result
     * @return TicketInterface
     */
    public function afterGetById(TicketRepositoryInterface $ticketRepository, TicketInterface $result)
    {
        /** @var \Branch8\HelpDesk\Api\Data\TicketExtension $extensionAttributes */
        $extensionAttributes = $result->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }
        $extensionAttributes->setAttachments([]);
        /**
         * @var $searchResult \Branch8\HelpDesk\Model\ResourceModel\Attachment\Collection
         */
        if ($attachments = (string)$result->getData('attachment_ids')) {
            $searchResult = $this->attachmentRepository->getList(
                $this->searchCriteriaBuilderFactory->create()->addFilter(
                    $this->filterFactory->create()
                        ->setField('attachment_id')
                        ->setConditionType('in')
                        ->setValue($attachments)
                )->addSortOrder('created_at', 'DESC')
                    ->setCurrentPage(1)
                    ->create()
            );
            $extensionAttributes->setAttachments($searchResult->getItems());
        }
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }
}
