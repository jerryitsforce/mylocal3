<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManager;
use  Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class GetEcpayInvoiceOrderItemLogRecord
{
    private $cache = [];
    private StoreManager $storeManager;
    private ResourceConnection $resourceConnection;
    private GetTZOffsetTransitions $getTZOffsetTransitions;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManager $storeManager
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     */
    public function __construct(
        ResourceConnection                          $resourceConnection,
        StoreManager                                $storeManager,
        GetTZOffsetTransitions                      $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    )
    {
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->timezone = $timezone;
    }

    /**
     * @param $orderId
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($orderId, $storeId = null)
    {
        $key = $orderId;
        if (isset($this->cached[$orderId])) {
            return $this->cached[$key];
        }
        $this->cache[$key] = [];
        if (!$storeId) {
            $storeID = $this->storeManager->getStore()->getId();
        }
        $dateAdd = $this->getDateAdd($storeID);
        $columns = [
            'invoice_log_id' => 'main_table.hotai_order_invoice_logs_id',
            'invoice_number' => new \Zend_Db_Expr('COALESCE(main_table.invoice_number, \'no_value\')'),
            'invoice_status' => 'status',
            'invoice_order_item_id' => 'item.order_item_id',
            'invoice_order_item_name' => 'item.order_item_name',
            'item_include_tax' => 'item.include_tax',
            'price_incl_tax' => 'item.include_tax',
            'qty' => 'item.qty',
            'shipping_price_incl_tax' => 'item.include_tax',
            'item_type' => 'item.type',
            'invoice_order_item_price' => 'item.include_tax',
            'is_reverse' => 'main_table.is_reverse',
            'db_invoice_created_date' => 'item.created_at',
            'ecpay_log_hotai_checkout_number' => 'main_table.hotai_checkout_number',
            'shop_title' => 'main_table.seller_shop_name',
            'order_id' => 'main_table.order_id',
            'seller_code' => 'main_table.seller_code',
            'seller_id' => 'main_table.seller_id',
        ];
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'main_table.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $offsetItemDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'item.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $columns['order_checkout_serial_number_date'] = $offsetDate;
            $columns['order_item_checkout_serial_number_date'] = $offsetItemDate;

        } else {
            $columns['order_checkout_serial_number_date'] = 'main_table.created_at';
            $columns['order_item_checkout_serial_number_date'] = 'item.created_at';
        }
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

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            'ecpay_invoice_hotai_order_invoice_logs as main_table',
            $columns
        )->join('ecpay_invoice_hotai_order_item_invoice_logs as item',
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            [
                'product_name' => 'item.order_item_name',
                'qty' => 'item.qty',
            ]
        );
        $select->joinLeft(
            $orderItemTable,
            'item.order_item_id = sales_order_item.item_id',
            $this->getItemColumns()
        )->joinLeft(
            $orderTable,
            'main_table.order_id = sales_order.entity_id',
            $this->getOrderColumns($dateAdd),
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
                'street' => 'soas.street', 'region' => 'soas.region', 'city' => 'soas.city', 'receiver_phone' => 'soas.telephone')
        )->joinLeft(
            ["soab" => $orderAddressTable],
            'sales_order.entity_id = soab.parent_id AND soab.address_type="billing"',
            array('telephone')
        )->joinLeft(
            ['sales_shipment_item' => $shipmentItemTable],
            'item.order_item_id=sales_shipment_item.order_item_id',
            []
        )->joinLeft(
            ['sales_shipment_track' => $shipmentItemTrackTable],
            'sales_shipment_item.parent_id=sales_shipment_track.parent_id',
            [
                   'logistic_company_name' => new \Zend_Db_Expr('(`title`)'),
                  'tracking_number' => new \Zend_Db_Expr('(`track_number`)')
            ]
        )->joinLeft(
            ['customer_entity' => $customerTable],
            'main_table.seller_id=customer_entity.entity_id',
            [
                'seller_name' => new \Zend_Db_Expr('CONCAT(customer_entity.firstname, " ", customer_entity.lastname)')
            ]
        )->joinLeft(
            ['catalog_product_entity' => $productEntityTable],
            'sales_order_item.origin_sku=catalog_product_entity.sku',
            ['has_options']
        );;
        $this->joinParentOrder($select);
        $select->where('main_table.order_id = ?', $orderId)
            ->where('item.export_report = ? ', 1)
            ->order('hotai_order_invoice_logs_id');
        /// ->group('item_invoice');
        ///
        //echo $select;die;
        return $connection->fetchAll($select);
        //return $this->cache[$key];
    }

    /**
     * @param $storeID
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
     * @return array
     */
    protected function getItemColumns()
    {
        $colusms = [
            //'column'=>new \Zend_Db_Expr('item'),
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
            //'product_name' => 'sales_order_item.name',
            'cart_rules' => 'sales_order_item.applied_rule_ids',
            'cost' => 'sales_order_item.base_cost',
            'discount_amount' => 'sales_order_item.discount_amount',
            'discount_percent' => 'sales_order_item.discount_percent',
            'tax_amount' => 'sales_order_item.tax_amount',
            'base_tax_amount' => 'sales_order_item.base_tax_amount',
            'tax_invoiced' => 'sales_order_item.tax_invoiced',
            'base_tax_invoiced' => 'sales_order_item.base_tax_invoiced',
            'is_virtual' => 'sales_order_item.is_virtual',
            'applied_rule_names' => 'sales_order_item.applied_rule_names',
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
            'rma_options' => 'sales_order_item.rma_options',
            'marketing_fee' => new \Zend_Db_Expr("0"),// pharse 2
            'logistic_support_fee' => new \Zend_Db_Expr("0"),//pharse 2
        ];
        return $colusms;
    }

    /**
     * @return array
     */
    private function getOrderColumns($dateAdd)
    {

        $orderColumns= [
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
                'CONCAT (COALESCE(`sales_order`.`customer_firstname`,"")," ",COALESCE(`sales_order`.`customer_lastname`,""))'
            ),
            'status' => 'sales_order.status',
            'order_status' => 'sales_order.status',
            'order_updated_at' => 'sales_order.updated_at',
            'rma_status' => 'sales_order.rma_status',
            'order_type' => 'sales_order.order_type',
            'shipping_method' => 'sales_order.shipping_method',
            'shipping_description' => 'sales_order.shipping_description',
            'hotai_checkout_number' => 'sales_order.hotai_checkout_number',
            'order_note' => 'sales_order.order_note'
        ];
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'sales_order.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $orderColumns['order_date'] = $offsetDate;
        } else {
            $orderColumns['order_date'] = 'sales_order.created_at';
        }
        return $orderColumns;
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
            'main_table.order_id=sales_parent_order_children.children_id',
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
