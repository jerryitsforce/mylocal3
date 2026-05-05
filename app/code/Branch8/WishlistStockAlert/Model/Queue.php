<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/02/2026
 */

namespace Branch8\WishlistStockAlert\Model;

use Magento\Framework\Model\AbstractModel;

class Queue extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Branch8\WishlistStockAlert\Model\ResourceModel\Queue::class);
    }

}
