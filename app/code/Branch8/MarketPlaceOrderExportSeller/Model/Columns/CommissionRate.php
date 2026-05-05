<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class CommissionRate implements ColumnInterface
{

    public function getHeader()
    {
        return __('Commission Rate');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['is_exception']) && $row['is_exception']) {
            return $row['commision_rate'] ?? '';
        }

        if (!empty($row['special_commission_rate'])) {
            return $row['special_commission_rate'] . '%';
        }

        if (!empty($row['commision_rate'])) {
            return $row['commision_rate'] . '%';
        }
        return '';
    }
}
