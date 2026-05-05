<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/02/2026
 */

namespace Branch8\WishlistStockAlert\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_wishlist_stock_alert_email_queue', 'queue_id');
    }
}
