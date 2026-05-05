<?php
declare(strict_types=1);

namespace Branch8\Sales\Model\ResourceModel\Order;

use Magento\Sales\Model\ResourceModel\EntityAbstract as SalesResource;
use Magento\Sales\Model\Spi\ShipmentResourceInterface;

/**
 *
 */
class History extends SalesResource implements ShipmentResourceInterface
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_order_history_resource';

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_order_status_history', 'entity_id');
    }

}
