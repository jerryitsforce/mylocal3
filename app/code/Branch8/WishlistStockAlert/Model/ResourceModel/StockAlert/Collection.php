<?php
namespace Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'alert_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WishlistStockAlert\Model\StockAlert::class,
            \Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert::class
        );
    }

    /**
     * @return void
     */
    public function joinProducts()
    {
        $attributes=[
          'name',
            ''
        ];
        $this->getSelect()->join(
            ['product' => 'catalog_product_entity'],
            'main_table.entity_id = product.entity_id'
        );
    }
}
