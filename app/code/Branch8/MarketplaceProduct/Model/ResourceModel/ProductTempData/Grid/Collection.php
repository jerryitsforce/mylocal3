<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Webkul\Marketplace\Helper\Data;

class Collection extends SearchResult
{
    /**
     * @var AggregationInterface
     */
    protected $_aggregations;
    /**
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $attribute;

    /**
     *
     * @var Data
     */
    protected $mpHelper;

    /**
     * Construct
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \\Webkul\Marketplace\Helper\Data $mpHelper
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param mixed $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        Data $mpHelper,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
        $this->mpHelper = $mpHelper;
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $this->getSelect()->where("main_table.seller_id = ".$sellerId);
        parent::_renderFiltersBefore();
    }
}
