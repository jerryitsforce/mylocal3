<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Query\BatchIteratorInterface;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\BatchSizeManagementInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManager;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;
use Zend_Db_Expr;

/**
 * Class for retrieval of all product images
 */
class Source
{
    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Generator
     */
    protected $batchQueryGenerator;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var int
     */
    protected $batchSize;

    protected \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    protected LoggerInterface $logger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone\Validator
     */
    protected $timezoneValidator;
    protected array $headers;
    protected StoreManager $storeManager;
    protected GetTZOffsetTransitions $getTZOffsetTransitions;
    private Registry $registry;

    /**
     * @param Generator $generator
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param Registry $registry
     * @param array $headers
     * @param int $batchSize
     */
    public function __construct(
        Generator                                             $generator,
        ResourceConnection                                    $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator,
        \Magento\Framework\Stdlib\DateTime\Timezone           $timezone,
        StoreManager                                          $storeManager,
        LoggerInterface                                       $logger,
        GetTZOffsetTransitions                                $getTZOffsetTransitions,
        Registry $registry,
        array                                                 $headers = [],
        int                                                   $batchSize = 10000
    )
    {
        $this->headers = $headers;
        $this->timezoneValidator = $timezoneValidator;
        $this->batchQueryGenerator = $generator;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->batchSize = $batchSize;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
    }

    /*
     * @return string[]
     */
    protected function getOrderColumns()
    {
        return [
            'distribution_thermosphere' => 'sales_order.distribution_thermosphere',
            'site_free_shipping_threshold' => 'sales_order.site_free_shipping_threshold',
            'site_shipping_fee' => 'sales_order.site_shipping_fee',
            'order_free_shipping_threshold' => 'sales_order.order_free_shipping_threshold',
            'order_id' => 'sales_order.entity_id',
            'point_used_total' => 'sales_order.point_used_total',
            'sub_order_number' => 'sales_order.increment_id',
            'subtotal_incl_tax' => 'sales_order.subtotal_incl_tax',
            'customer_id' => 'sales_order.customer_id',
            'customer_firstname' => 'sales_order.customer_firstname',
            'customer_lastname' => 'sales_order.customer_lastname',
            'name' => new \Zend_Db_Expr(
                'CONCAT (COALESCE(`customer_firstname`,"")," ",COALESCE(`customer_lastname`,""))'
            ),
            'status' => 'sales_order.status',
            'order_status' => 'sales_order.status',
            'order_updated_at' => 'sales_order.updated_at',
            'rma_status' => 'sales_order.rma_status',
            'order_type' => 'sales_order.order_type',
            'shipping_method' => 'sales_order.shipping_method',
            'shipping_description' => 'sales_order.shipping_description',
            'hotai_checkout_number' => 'sales_order.hotai_checkout_number',
            'order_note' => 'sales_order.order_note',
            'is_flagship_store_process_order'=>new \Zend_Db_Expr('
                (CASE WHEN sales_order.is_flagship_store_process_order IS NOT NULL THEN 1
                 ELSE 0
                END
                )'
            )
        ];
    }

    /**
     * @param $ids
     * @param $storeID
     * @param $ignoreOrders
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrderItems($ids, $storeID = null)
    {
        if (!$storeID) {
            $storeID = $this->storeManager->getStore()->getId();
        }
        if (!$this->registry->registry('ignore_limit')) {
            if (count($ids) > $this->batchSize) {
                throw new LocalizedException(
                    __('Maximum %1 for once time export', $this->batchSize)
                );
            }
        }
        $select = $this->getSelect($ids, $storeID);
        $records = [];
        foreach ($this->connection->fetchAll($select) as $key => $value) {
            $records[$value['order_id']][$value['item_id']] = $value;
        }
        //echo $select;die;
        return $records;
    }

    /**
     * @return array
     */
    public static function defaultInvoiceLogColumns()
    {
        return [
            'invoice_log_id' => new Zend_Db_Expr('""'),
            'invoice_number' => new Zend_Db_Expr('""'),
            'invoice_status' => new Zend_Db_Expr('""'),
            'invoice_order_item_id' => new Zend_Db_Expr('""'),
            'item_type' => new Zend_Db_Expr('"item"'),
            'invoice_order_item_price' => new Zend_Db_Expr('""'),
            'invoice_order_item_name' => new Zend_Db_Expr('""'),
        ];
    }

    /**
     * @return string[]
     */
    protected function getItemColumns()
    {
        $colusms = [
            //'column'=>new \Zend_Db_Expr('item'),
            'main_category_name' => 'main_category_name',
            'main_category' => 'main_category',
            'phone' => 'IFNULL(soas.telephone, soab.telephone)',
            'item_id' => 'sales_order_item.item_id',
            'order_item_id' => 'sales_order_item.item_id',
            'product_id' => 'sales_order_item.product_id',
            'price' => 'sales_order_item.price',
            'original_price' => 'sales_order_item.original_price',
            'product_type' => 'sales_order_item.product_type',
            'product_sku' => 'sales_order_item.sku',
            'origin_sku' => 'sales_order_item.origin_sku',
            'option_sku' => 'sales_order_item.option_sku',
            'variation_sku' => 'sales_order_item.variation_sku',
            'product_options' => 'sales_order_item.product_options',
            'product_name' => 'sales_order_item.name',
            'cart_rules' => 'sales_order_item.applied_rule_ids',
            'cost' => 'sales_order_item.base_cost',
            'discount_amount' => 'sales_order_item.discount_amount',
            'discount_percent' => 'sales_order_item.discount_percent',
            'qty' => 'sales_order_item.qty_ordered',
            'tax_amount' => 'sales_order_item.tax_amount',
            'base_tax_amount' => 'sales_order_item.base_tax_amount',
            'tax_invoiced' => 'sales_order_item.tax_invoiced',
            'base_tax_invoiced' => 'sales_order_item.base_tax_invoiced',
            'is_virtual' => 'sales_order_item.is_virtual',
            'seller_id' => 'sales_order_item.seller_id',
            'applied_rule_names' =>  new \Zend_Db_Expr('(CASE WHEN sales_order_item.applied_rule_ids IS NULL
                                        THEN "" ELSE sales_order_item.applied_rule_names END)'),
            'schedule_change_special_price' => 'sales_order_item.schedule_change_special_price',
            'schedule_change_special_price_start' => 'sales_order_item.schedule_change_special_price_start',
            'schedule_change_special_price_end' => 'sales_order_item.schedule_change_special_price_end',
            'special_price' => 'sales_order_item.special_price',
            'price_incl_tax' => 'sales_order_item.price_incl_tax',
            'sales_order_item_price_incl_tax' => 'sales_order_item.price_incl_tax',
            'row_invoiced' => 'sales_order_item.row_invoiced',
            'row_total_point_used' => 'sales_order_item.row_total_point_used',
            'row_total_incl_tax' => 'sales_order_item.row_total_incl_tax',
            'flow_status' => 'sales_order_item.flow_status',
            'hotai_point_deduction_point_trans_s_n' => 'sales_order_item.hotai_point_deduction_point_trans_s_n',
            'base_original_price' => 'sales_order_item.base_original_price',
            'base_price_incl_tax' => 'sales_order_item.base_price_incl_tax',
            'base_discount_amount' => 'sales_order_item.base_discount_amount',
            'price_log' => 'sales_order_item.price_log',
            'seller_code' => 'sales_order_item.seller_code',
            'rma_options' => 'sales_order_item.rma_options',
            'marketing_fee' => new \Zend_Db_Expr("0"),// pharse 2
            'logistic_support_fee' => new \Zend_Db_Expr("ROUND(
                             CASE WHEN sales_order.status IN ('arrived', 'complete')
                             THEN  sales_order.seller_shipping_amount
                             ELSE 0 END
                            )"
            ),
            'vendor_share' => new \Zend_Db_Expr('ROUND(
                CASE
                    WHEN (sales_order.discount_amount IS NULL OR sales_order.discount_amount =0) THEN 0
                    WHEN sales_order.applied_rule_ids IS NULL THEN 0
                    WHEN sales_order.seller_borne_total_amount > 0 THEN sales_order.seller_borne_total_amount
                    ELSE 0 END
                )'),
            'platform_share' => new \Zend_Db_Expr('ROUND(
                    CASE
                    WHEN (sales_order.discount_amount IS NULL OR sales_order.discount_amount =0) THEN 0
                    WHEN sales_order.applied_rule_ids IS NULL THEN 0
                    WHEN sales_order.platform_borne_total_amount > 0 THEN sales_order.platform_borne_total_amount
                    ELSE 0 END
                )'),
        ];
        return $colusms + self::defaultInvoiceLogColumns();
    }

    /**
     * @return array
     */
    private function getDateAdd($storeID)
    {
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $this->getTZOffsetTransitions->get($timezone);
        return $dateAdd;
    }

    /**
     * @param $ids
     * @param $storeID
     * @return Select
     * @TODO  create index table
     */
    private function getSelect($ids, $storeID): Select
    {
        $dateAdd = $this->getDateAdd($storeID);
        $itemColumns = $this->getItemColumns();
        //new \Zend_Db_Expr('DATE_FORMAT(main_table.created_at, "%Y-%m-%d")')
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'sales_order.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $itemColumns['order_date'] = $offsetDate;
        } else {
            $itemColumns['order_date'] = 'sales_order.created_at';
        }
        $select = $this->connection->select();
        $orderTable = $this->resourceConnection->getTableName(
            'sales_order'
        );
        $orderItemTable = $this->resourceConnection->getTableName(
            'sales_order_item'
        );
        $orderAddressTable = $this->resourceConnection->getTableName(
            'sales_order_address'
        );
        $shipmentItemTable = $this->resourceConnection->getTableName(
            'sales_shipment_item'
        );
        $shipmentItemTrackTable = $this->resourceConnection->getTableName(
            'sales_shipment_track'
        );
        $customerTable = $this->resourceConnection->getTableName(
            'customer_entity'
        );
        $sellerDataTable = $this->resourceConnection->getTableName(
            'marketplace_userdata'
        );
        $productEntityTable = $this->resourceConnection->getTableName(
            'catalog_product_entity'
        );
        $select->from($orderTable, $this->getOrderColumns())
            ->join(
                ['sales_order_item' => $orderItemTable],
                'sales_order.entity_id=sales_order_item.order_id',
                $itemColumns
            )->joinLeft(
                ['buyer' => $customerTable],
                'sales_order.customer_id=buyer.entity_id',
                [
                    'buyer_name' => 'buyer.firstname',
                    'buyer_phone' => 'buyer.phone_number'
                ]
            )->joinLeft(
                ["soas" => $orderAddressTable],
                'sales_order.entity_id = soas.parent_id AND soas.address_type="shipping"',
                array('receiver_name' => 'soas.firstname',
                    'street' => 'soas.street', 'region' => 'soas.region', 'city' => 'soas.city', 'receiver_phone' => 'soas.telephone', 'postcode' => 'soas.postcode')
            )->joinLeft(
                ["soab" => $orderAddressTable],
                'sales_order.entity_id = soab.parent_id AND soab.address_type="billing"',
                array(
                    'telephone'=>'soab.telephone',
                    'receiver_billingname' => 'soab.firstname',
                    'receiver_billingphone' => 'soab.telephone',
                )
            )->joinLeft(
                ['sales_shipment_item' => $shipmentItemTable],
                'sales_order_item.item_id=sales_shipment_item.order_item_id',
                []
            )->joinLeft(
                ['sales_shipment_track' => $shipmentItemTrackTable],
                'sales_shipment_item.parent_id=sales_shipment_track.parent_id',
                ['logistic_company_name' => new \Zend_Db_Expr('group_concat(`title`)'), 'tracking_number' => new \Zend_Db_Expr('group_concat(`track_number`)')]
            )->joinLeft(
                ['customer_entity' => $customerTable],
                'sales_order_item.seller_id=customer_entity.entity_id',
                [
                    'seller_name' => new \Zend_Db_Expr('CONCAT(customer_entity.firstname, " ", customer_entity.lastname)')
                ]
            )->joinLeft(
                ['marketplace_userdata' => $sellerDataTable],
                'sales_order_item.seller_id=marketplace_userdata.seller_id',
                ['shop_title' => 'shop_title']
            )->joinLeft(
                ['catalog_product_entity' => $productEntityTable],
                'sales_order_item.origin_sku=catalog_product_entity.sku',
                ['has_options']
            );
        $select->where(
            'sales_order.entity_id IN (?)', $ids
        )->where('sales_order_item.parent_item_id IS NULL');
        // $select = $this->joinShipment($select);
        $this->joinParentOrder($select);
        $select->group('sales_order_item.item_id');
        ///  echo $select;die;
        //$select->order('entity_id DESC');
        //$this->joinEcpayInvoiceLog($select);
        // echo $select;die;
        return $select;
    }

    /**
     * @param Select $select
     * @return Select
     */
    protected function joinParentOrder(Select $select)
    {
        $parentOrderDetail = $this->resourceConnection->getTableName(
            'sales_parent_order_detail'
        );
        $select->joinLeft(
            ['sales_parent_order_children' => 'sales_parent_order_children'],
            'sales_order.entity_id=sales_parent_order_children.children_id',
            []
        )->joinLeft(
            ['sales_parent_order_detail' => $parentOrderDetail],
            'sales_parent_order_children.parent_id=sales_parent_order_detail.parent_id',
            [
                'parent_order_number' => 'sales_parent_order_detail.increment_id',
                'parent_order_id' => 'sales_parent_order_children.parent_id'
            ]
        );
        return $select;
    }

}
