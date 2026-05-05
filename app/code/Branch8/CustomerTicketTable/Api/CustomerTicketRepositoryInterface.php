<?php

namespace Branch8\CustomerTicketTable\Api;

use Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface;

interface CustomerTicketRepositoryInterface
{
    /**
     * @api
     * @param CustomerTicketInterface $record
     * @return void
     */
    public function save(CustomerTicketInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\CustomerTicketTable\Api\Data\CustomerTicketSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Retrieve batch setting by id.
     *
     * @param int $id
     * @return null|\Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface
     */
    public function getById(int $id);
}
