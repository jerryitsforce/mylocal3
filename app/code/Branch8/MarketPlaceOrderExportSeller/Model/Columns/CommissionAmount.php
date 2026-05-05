<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class CommissionAmount implements ColumnInterface
{

    public function getHeader()
    {
        return __('Commission Amount');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['is_exception']) && $row['is_exception']) {
            return $row['commission_amount'] ?? '';
        }

        if (!empty($row['special_commission_rate'])) {
            return round(((int)$row['hotai_row_total'] * $row['special_commission_rate']) / 100);
        }

        if (!empty($row['commision_rate'])) {
            return round(((int)$row['hotai_row_total'] * $row['commision_rate']) / 100);
        }
        return '';
    }
}
