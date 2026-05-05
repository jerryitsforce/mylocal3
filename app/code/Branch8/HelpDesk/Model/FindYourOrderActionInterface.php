<?php

namespace Branch8\HelpDesk\Model;

interface FindYourOrderActionInterface
{
    /**
     * @param $customerId
     * @param $limit
     * @param $page
     * @param $q
     * @param $sort
     * @param $sortDirection
     * @return mixed
     */
    public function execute($customerId, $limit = 50, $page = 1, $q = '', $sort = 'entity_id', $sortDirection = 'ASC');
}
