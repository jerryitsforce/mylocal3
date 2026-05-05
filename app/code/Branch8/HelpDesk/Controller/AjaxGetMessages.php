<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller;

use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

class AjaxGetMessages
{
    private $messageRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterFactory
     */
    private $filterFactory;
    /**
     * @var TicketRepositoryInterface
     */
    private $serviceOutputProcessor;

    public function __construct(
        SearchCriteriaBuilder                            $searchCriteriaBuilder,
        FilterFactory                                    $filterFactory,
        MessageRepositoryInterface                       $messageRepository,
        \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor
    )
    {
        $this->messageRepository = $messageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterFactory = $filterFactory;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
    }

    /**
     * Get List message
     * @param TicketInterface $ticket
     * @param $p
     * @return array|bool|float|int|object|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMessageByPage(TicketInterface $ticket, $p)
    {
        $p = $p > 0 ? $p : 1;
        $ticketId = $ticket->getId();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            $this->filterFactory->create()
                ->setField('ticket_id')
                ->setValue($ticketId)
        )->addSortOrder('created_at', 'DESC')
            ->setCurrentPage($p)
            ->setPageSize(5)
            ->create();
        $searchResults = $this->serviceOutputProcessor->process(
            $this->messageRepository->getList(
                $searchCriteria
            ), MessageRepositoryInterface::class,
            'getList');
        unset($searchResults['search_criteria']);
        return $searchResults;
    }
}
