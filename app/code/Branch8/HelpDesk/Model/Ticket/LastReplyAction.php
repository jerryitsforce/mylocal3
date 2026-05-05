<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\User\Model\User;

/**
 * LastReplyAction Class
 */
class LastReplyAction
{
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var
     */
    private $date;

    /**
     * @param TicketRepositoryInterface $ticketRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        TicketRepositoryInterface                   $ticketRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->ticketRepository = $ticketRepository;
        $this->date = $date;
    }

    /**
     * @param TicketInterface $ticket
     * @param \Magento\User\Model\User $user
     * @return TicketInterface
     */
    public function saveByUser(TicketInterface $ticket, \Magento\User\Model\User $user)
    {
        if ($ticket->getId()) {
            return $this->ticketRepository->save($ticket->setLastReplyName(
                $user->getName()
            )->setLastReplyDate($this->date->gmtDate()));
        }
    }

    /**
     * @param TicketInterface $ticket
     * @param Customer $customer
     * @return TicketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveByCustomer(TicketInterface $ticket, Customer $customer)
    {
        if ($ticket->getId()) {
            return $this->ticketRepository->save($ticket->setLastReplyName(
                $customer->getName()
            )->setLastReplyDate($this->date->gmtDate()));
        }
    }
    /**
     * @param TicketInterface $ticket
     * @param array $customer
     * @return TicketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveByGuest(TicketInterface $ticket, array $customer)
    {
        if ($ticket->getId()) {
            return $this->ticketRepository->save($ticket->setLastReplyName(
                $customer['name']
            )->setLastReplyDate($this->date->gmtDate()));
        }
    }
}
