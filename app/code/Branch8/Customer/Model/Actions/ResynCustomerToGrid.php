<?php
declare(strict_types=1);

namespace Branch8\Customer\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class ResynCustomerToGrid
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $customerIds
     * @return void
     */
    public function execute($customerIds = [])
    {
        if (!$customerIds) {
            return;
        }
        $query = 'UPDATE customer_grid_flat AS `grid`
JOIN customer_orders_latest_index AS `latest_orders` ON `grid`.`entity_id` = `latest_orders`.`customer_id`
SET `grid`.`order_latest_order_ids` = `latest_orders`.`latest_order_ids`
WHERE `grid`.`entity_id` IN (' . join($customerIds) . ')';
        $this->resourceConnection->getConnection()->query($query);
    }
}
