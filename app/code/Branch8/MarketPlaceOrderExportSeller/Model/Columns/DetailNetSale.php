<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class DetailNetSale implements ColumnInterface
{
    public function getHeader()
    {
        return __('Net Sales');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['is_exception']) && $row['is_exception']) {
            return $row['net_sale'] ?? 0;
        }

        $comissionAmount = 0;
        if (!empty($row['special_commission_rate'])) {
            $comissionAmount = ((float)$row['hotai_row_total'] * $row['special_commission_rate']) / 100;
        } else if (!empty($row['commision_rate'])) {
            $comissionAmount = ((float)$row['hotai_row_total'] * $row['commision_rate']) / 100;
        }
        return round($row['hotai_row_total'] - round($comissionAmount));
    }

}
