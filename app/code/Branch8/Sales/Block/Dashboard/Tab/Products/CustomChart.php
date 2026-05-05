<?php

namespace Branch8\Sales\Block\Dashboard\Tab\Products;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\App\ResourceConnection;

class CustomChart extends \Magento\Backend\Block\Template
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ResourceConnection $resourceConnection
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ResourceConnection $resourceConnection,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Get 5 product with revenue highest
     * @return array[]
     */
    public function getProductData()
    {
        $connection = $this->resourceConnection->getConnection();
        // Get the first and last day of the current month
        $currentMonthStartDate = date('Y-m-01 00:00:00');
        $currentMonthEndDate = date('Y-m-t 23:59:59');
        $select = $connection->select()
            ->from(['order_items' => $connection->getTableName('sales_order_item')], [
                'ordered_qty' => 'order_items.qty_ordered',
                'order_items_name' => 'order_items.name',
                'revenue' => 'SUM(order_items.row_total)',
                'average_order_value' => new \Zend_Db_Expr(
                    'SUM(order_items.row_total) / NULLIF(SUM(order_items.qty_ordered), 0)'
                )
            ])
            ->join(
                ['order' => $connection->getTableName('sales_order')],
                'order.entity_id = order_items.order_id AND order.state <> "canceled" AND (order.created_at BETWEEN "'.$currentMonthStartDate.'" AND "'.$currentMonthEndDate.'")',
                []
            )
            ->where('order_items.parent_item_id IS NULL')
            ->group('order_items.sku')
            ->having('ordered_qty > 0')
            ->order('revenue DESC')
            ->limit(5);

        $result = $connection->fetchAll($select);

        // Convert data to the desired format
        $chartData = [['Product', 'Quantity', 'Revenue', 'Average Order Value']];
        foreach ($result as $item) {
            $chartData[] = [
                $item['order_items_name'],
                (int)$item['ordered_qty'],
                (int)$item['revenue'],
                (int)$item['average_order_value']
            ];
        }

        return ['chart' => $chartData];
    }

}
