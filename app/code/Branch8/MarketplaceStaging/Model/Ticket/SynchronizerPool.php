<?php

namespace Branch8\MarketplaceStaging\Model\Ticket;

use Branch8\MarketplaceStaging\Api\TicketSynchronizerInterface;

/**
 * Manager pool for ticket stock synchronizers.
 */
class SynchronizerPool
{
    /**
     * @var TicketSynchronizerInterface[]
     */
    protected array $synchronizers = [];

    /**
     * @param array $synchronizers
     */
    public function __construct(array $synchronizers = [])
    {
        $this->synchronizers = $synchronizers;
    }

    /**
     * Get synchronizer by virtual product type ID.
     *
     * @param int|string $typeId
     * @return TicketSynchronizerInterface|null
     */
    public function get($typeId): ?TicketSynchronizerInterface
    {
        return $this->synchronizers[$typeId] ?? null;
    }
    
    /**
     * Check if a synchronizer exists for the given type.
     * 
     * @param int|string $typeId
     * @return bool
     */
    public function has($typeId): bool
    {
        return isset($this->synchronizers[$typeId]);
    }
}
