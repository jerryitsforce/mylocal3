<?php

namespace Branch8\TicketApi\Api;

use Branch8\TicketApi\Api\Data\TicketApiMerchantInterface;

interface TicketApiMerchantRepositoryInterface
{
    /**
     * @api
     * @param TicketApiMerchantInterface $record
     * @return void
     */
    public function save(TicketApiMerchantInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\TicketApi\Api\Data\TicketApiMerchantSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * @param int $id
     * @return null|TicketApiMerchantInterface
     */
    public function getById(int $id);
}
