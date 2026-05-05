<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExportSeller\Model\Services\GetPurchaseCost;
use Magento\Framework\App\ResourceConnection;

class PurchaseCost implements ColumnInterface
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
        return __('Purchase Cost');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        $itemIds = [];
        $purchaseCost = '';
        if (!empty($row['order_item_ids'])) {
            $purchaseCost = $this->getPurchaseCost->execute((string)$row['order_item_ids']);
        }
        return $purchaseCost;
    }
}
