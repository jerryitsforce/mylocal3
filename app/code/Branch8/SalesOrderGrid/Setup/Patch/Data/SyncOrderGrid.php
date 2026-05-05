<?php

declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;

class SyncOrderGrid implements DataPatchInterface
{
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function apply()
    {
        $conn = $this->resource->getConnection();

        $orderTable = $this->resource->getTableName('sales_order');
        $gridTable  = $this->resource->getTableName('sales_order_grid');

        // Sync all existing rows
        $sql = "
            UPDATE {$gridTable} g
            JOIN {$orderTable} o ON g.entity_id = o.entity_id
            SET g.shipping_method = o.shipping_method
        ";

        $conn->query($sql);

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}

