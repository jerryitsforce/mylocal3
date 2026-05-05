<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\AdvancedPermissions\Model\ResourceModel\Order\Grid;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Backend\Model\Locale\Resolver\Proxy;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\DB\Sql\Expression as SqlExpression;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Filter Unprocessed Async Orders in Sales/Orders Grid
 */
class Collection extends OriginalCollection
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    private $timezone;
    private Proxy $localResolver;

    protected $_filtered = [];

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param DeploymentConfig $deploymentConfig
     * @param TimezoneInterface $timezone
     * @param Proxy $localeResolver
     */
    public function __construct(
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        DeploymentConfig  $deploymentConfig,
        TimezoneInterface $timezone,
        Proxy             $localeResolver
    )
    {
        $this->deploymentConfig = $deploymentConfig;
        $this->timezone = $timezone;
        $this->localResolver = $localeResolver;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
    }

    protected function _renderFiltersBefore()
    {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            $this->addFieldToFilter('status', [
                'nin' => OrderManagement::STATUS_RECEIVED
            ]);
        }
        $select = $this->getSelect();
        $fields = $this->_filtered;

        if (in_array('seller_id', $fields) || in_array('seller', $fields) || in_array('nickname', $fields)
            || in_array('phone_number', $fields) || in_array('salesperson', $fields)
            || in_array('sale_person_id', $fields)) {
                $query = $select->__toString();
            if (strpos($query, 'mo.order_id') == false) {
                $joinTable1 = $this->getTable('marketplace_orders');
                $select->joinLeft(
                    ['mo' => $joinTable1],
                    'main_table.entity_id = mo.order_id',
                    ['seller_id']
                );
            }
            
            if (in_array('seller', $fields) || in_array('nickname', $fields)
                || in_array('phone_number', $fields)) {
                $joinTable2 = $this->getTable('customer_grid_flat');
                $select->joinLeft(
                    ['cgf' => $joinTable2],
                    'mo.seller_id = cgf.entity_id',
                    [
                        'seller' => 'GROUP_CONCAT(
                                      DISTINCT CONCAT(cgf.entity_id,";",cgf.name)
                                      ORDER BY cgf.entity_id
                                      SEPARATOR ","
                                    )',
                        'nickname',
                        'phone_number',
                    ]
                );
            }
            if (in_array('salesperson', $fields) || in_array('sale_person_id', $fields)) {
                $select->joinLeft(
                    ['mu' => 'marketplace_userdata'],
                    'mu.seller_id = mo.seller_id',
                    ['salesperson']
                );
            }
        }
        if (in_array('product_type', $fields) || in_array('product_name', $fields)
            || in_array('product_sku', $fields) || in_array('supplier_sku', $fields)
            || in_array('item_trans_sn', $fields)) {
            $select->joinLeft(
                ['soi' => 'sales_order_item'],
                'main_table.entity_id = soi.order_id',
                [
                    'product_type' => "GROUP_CONCAT(DISTINCT soi.product_type SEPARATOR '<br>') AS product_type",
                    'product_name' => "GROUP_CONCAT(DISTINCT soi.name SEPARATOR '<br>') AS product_name",
                    'supplier_sku' => "GROUP_CONCAT(DISTINCT soi.option_sku SEPARATOR '<br>') AS supplier_sku",
                    'item_trans_sn' => "GROUP_CONCAT(DISTINCT soi.hotai_point_deduction_point_trans_s_n SEPARATOR '<br>') AS item_trans_sn"
                ]
            );
        }
        if (in_array('invoice_status', $fields) || in_array('invoice_modification_date', $fields)) {
            $select->joinLeft(
                ['sig' => 'sales_invoice_grid'],
                'main_table.entity_id = sig.order_id',
                [
                    'invoice_status' => 'sig.state',
                    'invoice_modification_date' => 'sig.updated_at'
                ]
            );
        }
        if (in_array('rmaids', $fields)) {
            $select->joinLeft(
                ['rma_details' => 'marketplace_rma_details'],
                'main_table.entity_id = rma_details.order_id',
                [
                    'rmaids' =>   "GROUP_CONCAT(DISTINCT rma_details.id SEPARATOR ',') AS rmaids"
                ]
            );
        }
        
        $select->columns([
            'rma_status' => new SqlExpression(
                'If(main_table.rma_status is NULL, \'n/a\', main_table.rma_status)'
            )
        ]);

        $query = strtolower($select->__toString());
        if(strpos($query, 'group_concat') != false){
            $select->group('main_table.entity_id');
        }
    }

    /**
     * Modify for website_id code same in location type and location list
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $this->_filtered[] = $field;
        if($field=='sale_person_id'){
            $field = 'mu.salesperson';
        }elseif ($field === 'seller_id') {
            $field = 'mo.seller_id';
        }elseif ($field === 'seller') {
            $field = 'cgf.name';
        } elseif ($field == 'shipping_method') {
            if (current($condition) == 'no_shipping') {
                $condition = ['null' => true];
            }
        } elseif ($field == 'product_sku') {
            $field = 'soi.sku';
        } elseif ($field == 'product_name') {
            $field = 'soi.name';
        }elseif ($field == 'supplier_sku') {
            $field = 'soi.option_sku';
        } elseif ($field == 'invoice_modification_date') {
            $field = 'sig.updated_at';
        } elseif ($field == 'invoice_status') {
            $field = 'sig.state';
        } elseif ($field == 'item_trans_sn') {
            $field = 'soi.hotai_point_deduction_point_trans_s_n';
        } elseif ($field == 'rma_status') {
            if (current($condition) == 'n/a') {
                $condition = ['null' => true];
            }
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @param $field
     * @param $direction
     * @return Collection|OriginalCollection
     */
    public function setOrder($field, $direction = 'asc')
    {
        $this->_filtered[] = $field;
        if ($field == 'product_sku') {
            $field = 'soi.sku';
        }
        return parent::setOrder($field, $direction);
    }

    /**
     * Sets order and direction.
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::addOrder($field, $direction);
    }

    /**
     * Add select order to the beginning
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function unshiftOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::unshiftOrder($field, $direction);
    }
}
