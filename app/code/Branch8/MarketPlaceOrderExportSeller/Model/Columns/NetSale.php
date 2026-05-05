<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class NetSale implements ColumnInterface
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
        $comissionAmount = 0;
        if (!empty($row['commission_amount'])) {
            $comissionAmount = round($row['commission_amount']);
        }
        return round($row['hotai_row_total'] - $comissionAmount);
    }

}
