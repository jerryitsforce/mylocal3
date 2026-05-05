<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use  Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class GetEcpayInvoiceOrderLog
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
            //'cost' => new \Zend_Db_Expr('""'),
            //'discount_amount' => new \Zend_Db_Expr('""'),
            'item_type' => 'item.type',
            'invoice_order_item_price' => 'item.include_tax',
            'product_name' => 'item.order_item_name',
            'is_reverse' => 'main_table.is_reverse',
            'db_invoice_created_date' => 'item.created_at',
            'ecpay_log_hotai_checkout_number'=>'main_table.hotai_checkout_number'
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
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            'ecpay_invoice_hotai_order_invoice_logs as main_table',
            $columns
        )->join('ecpay_invoice_hotai_order_item_invoice_logs as item',
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->where('main_table.order_id = ?', $orderId)
            ->where('item.export_report = ? ', 1)
            ->where('main_table.created_at NOT LIKE ? ','%1970%')
            ->where('item.created_at NOT LIKE ?','%1970%')
            ->order('hotai_order_invoice_logs_id');
        /// ->group('item_invoice');
        ///

        $rows = $connection->fetchAll($select);
        if ($rows) {
            foreach ($rows as $row) {
                $this->cache[$key][$row['invoice_log_id']][] = $row;
            }
        }
        return $this->cache[$key];
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
}
