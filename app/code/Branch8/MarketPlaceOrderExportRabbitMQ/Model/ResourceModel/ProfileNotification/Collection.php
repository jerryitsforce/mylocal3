<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Order grid collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileNotification::class,
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification::class
        );
    }
}
