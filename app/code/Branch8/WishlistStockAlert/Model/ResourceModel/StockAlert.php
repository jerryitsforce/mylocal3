<?php
namespace Branch8\WishlistStockAlert\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StockAlert extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_wishlist_stock_alert', 'alert_id');
    }
}
