<?php

namespace Branch8\HotaiCore\Api;

interface BatchImportTicketFlowInterface
{
    /**
     * Retrieve available batch data for the given product (including quantities and time windows).
     *
     * @param int|string $productId
     * @return array
     */
    public function getAvailableBatchData(int|string $productId): array;

    /**
     * Cancel tickets associated with the specified sales order item ID.
     *
     * @param int $orderItemId
     * @return void
     */
    public function cancelTickets(int $orderItemId): void;

    /**
     * Get batch setting data required for building product custom options.
     *
     * @param int|string $productId
     * @return array
     */
    public function getCurrentBatchSettingDataForCustomOption(int|string $productId): array;

    /**
     * Check if the quantity is enough by custom option and request quantity.
     *
     * @param int|string $productId
     * @param string $customOptionValue
     * @param int|string $requestQuantity
     * @return array
     */
    public function checkIfQuantityEnoughByCustomOptionAndRequestQuantity(int|string $productId, string $customOptionValue, int|string $requestQuantity): array;
}


