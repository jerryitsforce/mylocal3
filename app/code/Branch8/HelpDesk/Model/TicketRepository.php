<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * CRUD Ticket
 */
class TicketRepository implements TicketRepositoryInterface
{
    /**
     * @var ResourceModel\Ticket
     */
    private $resource;

    private $ticketFactory;

    /**
     * @param ResourceModel\Ticket $resource
     * @param TicketFactory $ticketFactory
     */
    public function __construct(
        ResourceModel\Ticket $resource,
        TicketFactory        $ticketFactory
    )
    {
        $this->ticketFactory = $ticketFactory;
        $this->resource = $resource;
    }

    /**
     * Save
     * @param TicketInterface $ticket
     * @return TicketInterface
     * @throws CouldNotSaveException
     */
    public function save(TicketInterface $ticket)
    {
        try {
            /**
             * @var $ticket Ticket
             */
            $this->resource->save($ticket);
            if (!$ticket->getCode()) {
                $ticket->generateCode()->saveCode();
            }
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Ticket: %1',
                $exception->getMessage()
            ));
        }
        return $this->getById((int)$ticket->getId());
    }

    /**
     * GetById
     * @param int $id
     * @return TicketInterface|Ticket
     */
    public function getById(int $id)
    {
        $ticket = $this->ticketFactory->create()->load($id);
        if (!$ticket->getId()) {
            throw new NoSuchEntityException();
        }
        return $ticket;
    }

}

