<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Api;

use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as Model;

interface TicketResourceInterface
{
    /**
     * Check if ticket is already used
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketUsed(int $ticketId): bool;

    /**
     * Check if ticket is not yet available for use
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketNotYetAvailable(int $ticketId): bool;

    /**
     * Check if ticket is overdue
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketOverdue(int $ticketId): bool;

    /**
     * Update ticket status and related tables in transaction
     *
     * @param  Model $model
     * @return Model
     * @throws \Exception
     */
    public function updateTicketUsageStatus(Model $model): Model;
}