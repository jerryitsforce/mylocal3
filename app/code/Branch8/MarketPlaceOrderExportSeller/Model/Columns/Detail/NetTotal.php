<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns\Detail;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class NetTotal implements ColumnInterface
{

    public function getHeader()
    {
        return __('Net Total');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        //付款總額 = 採購成本-行銷負擔(特約商)+運費補貼
        //net_total=$row['net_sale']-$row['marketing_fee']+$row['logistic_support_fee']
        /*   $row['marketing_fee'] = 0;
           $row['support_logistic_fee'] = 0;*/
       /* $comissionAmount = 0;
        if (!empty($row['commision_rate'])) {
            $comissionAmount = ((int)$row['hotai_row_total'] * $row['commision_rate']) / 100;
        }
        $netSale = (int)$row['hotai_row_total'] - (int)$comissionAmount;*/
        return $row['purchase_cost'] - $row['vendor_share'] + $row['logistic_support_fee'];
    }
}
