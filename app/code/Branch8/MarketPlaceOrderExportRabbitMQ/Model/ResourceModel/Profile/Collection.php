<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile;

/**
 * Order grid collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
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
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile::class,
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile::class
        );
    }
}
