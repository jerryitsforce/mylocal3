<?php

namespace Branch8\SalesReports\Model\Actions;

interface GetOptionsInterface
{
    public function get($filters = [], $page = 1, $limit = 1000);
}
