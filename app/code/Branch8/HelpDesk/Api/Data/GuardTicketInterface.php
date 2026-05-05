<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;
/**
 * Separate interface for Ticket Data
 */
interface GuardTicketInterface
{
    /**
     * @param $value
     * @return TicketInterface
     */
    public function setCustomerId($value);

    /**
     * @return int
     */
    public function getCustomerId();
}
