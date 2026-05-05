<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class DetailPlatformShare implements ColumnInterface
{
    public function getHeader()
    {
        return __('Platform Share');
    }

    /**
     * @param array $row
     * @return int
     */
    public function processColumnData(array $row = [])
    {
       return (int)$row['platform_share'];
    }

}
