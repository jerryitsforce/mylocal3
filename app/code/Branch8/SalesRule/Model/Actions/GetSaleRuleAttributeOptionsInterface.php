<?php

namespace Branch8\SalesRule\Model\Actions;

interface GetSaleRuleAttributeOptionsInterface
{
    /**
     * @param $filters
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function get($filters = [], $page = 1, $limit = 1000);
}
