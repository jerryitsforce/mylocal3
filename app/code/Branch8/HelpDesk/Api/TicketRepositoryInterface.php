<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api;

use Branch8\HelpDesk\Api\Data\TicketInterface;

/**
 * Ticket CRUD
 */
interface TicketRepositoryInterface
{
    /**
     * @param TicketInterface $ticket
     * @return TicketInterface
     */
    public function save(TicketInterface $ticket);

    /**
     * @param int $id
     * @return TicketInterface
     */
    public function getById(int $id);
}
