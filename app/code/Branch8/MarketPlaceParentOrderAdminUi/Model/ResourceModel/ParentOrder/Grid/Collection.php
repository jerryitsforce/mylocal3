<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends SearchResult
{
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    protected $_filtered = [];

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     */
    public function __construct(
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = 'sales_parent_order_grid',
        string            $resourceModel = ParentOrderDetail::class,
        TimezoneInterface $timeZone = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }

    protected function _renderFiltersBefore()
    {
        $select = $this->getSelect();
        $fields = $this->_filtered;
        if (in_array('seller', $fields) || in_array('nickname', $fields)
            || in_array('phone_number', $fields) || in_array('salesperson', $fields)
            || in_array('sale_person_id', $fields) || in_array('product_sku', $fields)
            || in_array('product_name', $fields) || in_array('product_type', $fields)
            || in_array('invoice_status', $fields) || in_array('invoice_modification_date', $fields)
            || in_array('shipping_method', $fields) || in_array('buyer_phone_number', $fields)) {
            $select->joinLeft(
                ['spoc' => 'sales_parent_order_children'],
                'main_table.entity_id = spoc.parent_id',
                []
            )->joinLeft(
                ['so' => 'sales_order'],
                'so.entity_id = spoc.children_id',
                ['shipping_method' => "GROUP_CONCAT(DISTINCT IFNULL(so.shipping_method, '-') SEPARATOR '|')"]
            );
        }

        if (in_array('seller', $fields) || in_array('nickname', $fields)
            || in_array('phone_number', $fields) || in_array('salesperson', $fields)
            || in_array('sale_person_id', $fields)) {
            $joinTable1 = $this->getTable('marketplace_orders');
            $select->joinLeft(
                ['mo' => $joinTable1],
                'so.entity_id = mo.order_id',
                ['seller_id']
            );
            if (in_array('seller', $fields) || in_array('nickname', $fields)
                || in_array('phone_number', $fields)) {
                $joinTable2 = $this->getTable('customer_grid_flat');
                $select->joinLeft(
                    ['cgf' => $joinTable2],
                    'mo.seller_id = cgf.entity_id',
                    [
                        'seller' => 'GROUP_CONCAT(DISTINCT CONCAT(cgf.entity_id,";",cgf.name) ORDER BY cgf.entity_id SEPARATOR ",")',
                        'nickname' => "GROUP_CONCAT(DISTINCT cgf.nickname SEPARATOR '<br>')",
                        'phone_number' => "GROUP_CONCAT(DISTINCT cgf.phone_number SEPARATOR '<br>')"
                    ]
                );
            }
            if (in_array('salesperson', $fields) || in_array('sale_person_id', $fields)) {
                $select->joinLeft(
                    ['mu' => 'marketplace_userdata'],
                    'mu.seller_id = mo.seller_id',
                    ['salesperson' => "GROUP_CONCAT(DISTINCT mu.salesperson SEPARATOR '|')"]
                );
            }
        }
        if (in_array('product_sku', $fields) || in_array('product_name', $fields)
            || in_array('product_type', $fields)) {
            $select->joinLeft(
                ['soi' => 'sales_order_item'],
                'so.entity_id = soi.order_id',
                [
                    'product_sku' => "GROUP_CONCAT(DISTINCT soi.sku SEPARATOR '<br>')",
                    'product_name' => "GROUP_CONCAT(DISTINCT soi.name SEPARATOR '<br>')",
                    'product_type' => "GROUP_CONCAT(DISTINCT soi.product_type SEPARATOR '|')"
                ]
            );
        }
        if (in_array('invoice_status', $fields) || in_array('invoice_modification_date', $fields)) {
            $select->joinLeft(
                ['sig' => 'sales_invoice_grid'],
                'main_table.entity_id = sig.order_id',
                [
                    'invoice_status' => 'GROUP_CONCAT(DISTINCT CONCAT(sig.entity_id,";",sig.state) ORDER BY sig.entity_id SEPARATOR "|")',
                    'invoice_modification_date' => 'GROUP_CONCAT(DISTINCT sig.updated_at SEPARATOR "|")'
                ]
            );
        }
        $select->group('main_table.entity_id');
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $this->_filtered[] = $field;
        if ($field === 'created_at') {
            if (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    $condition[$key] = $this->timeZone->convertConfigTimeToUtc($value);
                }
            }
        } elseif ($field === 'seller') {
            $field = 'cgf.name';
        } elseif ($field == 'phone_number' || $field == 'nickname') {
            $field = "cgf.$field";
        } elseif ($field == 'shipping_method') {
            if (current($condition) == 'no_shipping') {
                $condition = ['null' => true];
            }
        } elseif ($field == 'product_sku') {
            $field = 'soi.sku';
        } elseif ($field == 'product_name') {
            $field = 'soi.name';
        } elseif ($field == 'product_type') {
            $field = 'soi.product_type';
        } elseif ($field == 'invoice_modification_date') {
            $field = 'sig.updated_at';
        } elseif ($field == 'invoice_status') {
            $field = 'sig.state';
        } elseif ($field == 'salesperson') {
            $field = 'mu.salesperson';
        } elseif ($field == 'increment_id') {
            $field = 'main_table.increment_id';
        } elseif ($field == 'main_table.entity_id') {
            $field = 'main_table.entity_id';
        }else if($field == 'buyer_phone_number'){
            $field = 'cgf_g.phone_number';
        }else{
            $field = "main_table.{$field}";
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @param $field
     * @param $direction
     * @return Collection
     */
    public function setOrder($field, $direction = 'asc')
    {
        $this->_filtered[] = $field;
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
