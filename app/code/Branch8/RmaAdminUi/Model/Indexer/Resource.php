<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       13/02/2026
 */

namespace Branch8\RmaAdminUi\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Resource
{
    /**
     * @var OrderResource
     */
    private $resourceConnection;

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
     * @param array $ids
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveIndexData(array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        if (empty($ids)) {
            $condition = ['rma.id IS NOT NULL'];
        } else {
            $condition = ['rma.id IN (?)', $ids];
        }
        $columns = [
            'id' => 'rma.id',
            'created_date' => 'rma.created_date',
            'order_created_at' => 'sale_order.created_at',
            'salesperson' => "mu.salesperson",
            'seller_id' => "rma.seller_id",
            'resolution_type' => 'rma.resolution_type',
            'order_ref' => 'rma.order_ref',
            'customer_id' => 'rma.customer_id',
            'customer_name' => 'rma.customer_name',
            'customer_email' => 'rma.customer_email',
            'product_seller'=> 'rma.product_seller',
            'status' => 'rma.status',
            'order_status' => 'rma.status',
            'rma_order_status' => 'sale_order.status',
            'product_name' => "GROUP_CONCAT(DISTINCT soi.name SEPARATOR '<br>') AS product_name",
            'product_sku' => "GROUP_CONCAT(DISTINCT soi.sku SEPARATOR '<br>') AS product_sku",
            'supplier_sku' => "GROUP_CONCAT(DISTINCT soi.option_sku SEPARATOR '<br>') AS supplier_sku",
            'rma_phone' => "rma.rma_phone",
            'shipping_method' => "sale_order.shipping_method",
        ];
        $select = $connection->select()
            ->from(
                ['rma' => $this->resourceConnection->getTableName('marketplace_rma_details')],
                $columns
            )->join(
                ['items' => $this->resourceConnection->getTableName('marketplace_rma_items')],
                'rma.id = items.rma_id',
                []
            )->joinLeft(
                ['soi' => $this->resourceConnection->getTableName('sales_order_item')],
                'items.item_id = soi.item_id',
                []
            )->joinLeft(
                ['sale_order' => $this->resourceConnection->getTableName('sales_order')],
                'rma.order_id = sale_order.entity_id',
                []
            )->joinLeft(
                ['mu' => $this->resourceConnection->getTableName('marketplace_userdata')],
                'mu.seller_id = rma.seller_id',
                []
            )->where(
                ...$condition
            )->group(
                'rma.id'
            );
        return (array)$connection->fetchAll($select);
    }
}
