<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExportSeller\Model\Services\GetPurchaseCost;
use Magento\Framework\App\ResourceConnection;

class NetTotal implements ColumnInterface
{
    private ResourceConnection $resourceConnection;
    private GetPurchaseCost $getPurchaseCost;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetPurchaseCost $getPurchaseCost
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetPurchaseCost    $getPurchaseCost
    )
    {
        $this->getPurchaseCost = $getPurchaseCost;
        $this->resourceConnection = $resourceConnection;
    }


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
        /*if ($row['purchase_cost'] <= 0) {
            return 0;
        }*/
        //付款總額 = 採購成本-行銷負擔(特約商)+運費補貼
        //net_total=$row['net_sale']-$row['marketing_fee']+$row['support_logistic_fee']
        //$row['marketing_fee'] = 0;
        // $row['support_logistic_fee'] = 0;
        /*        $row['net_sale'] = (int)$row['hotai_row_total'] - (int)$row['commission_amount'];*/
        // $purchseCost = (int)$this->getPurchaseCost->execute($row['order_item_ids']);
        return round($row['purchase_cost'] - $row['vendor_share'] + $row['logistic_support_fee']);
    }
}
