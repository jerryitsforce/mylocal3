<?php

namespace Branch8\TicketApi\Api;

use Branch8\TicketApi\Api\Data\TicketApiBrandInterface as ModelInterface;

interface TicketApiBrandRepositoryInterface
{
    /**
     * @api
     * @param ModelInterface $record
     * @return void
     */
    public function save(ModelInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return ModelInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * @param int $id
     * @return null|ModelInterface
     */
    public function getById(int $id);
}
