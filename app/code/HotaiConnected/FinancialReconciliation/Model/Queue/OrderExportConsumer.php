<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Model\Queue;

use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Sql\Expression;

/**
 * Class OrderExportConsumer
 * 負責非同步產生訂單結帳匯出 Excel 報表
 */
class OrderExportConsumer
{
    private $logger;
    private $jsonSerializer;
    private $resourceConnection;
    private $fileSystem;
    private $storeManager;

    /**
     * @param LoggerInterface $logger
     * @param JsonSerializer $jsonSerializer
     * @param ResourceConnection $resourceConnection
     * @param Filesystem $fileSystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        JsonSerializer $jsonSerializer,
        ResourceConnection $resourceConnection,
        Filesystem $fileSystem,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->resourceConnection = $resourceConnection;
        $this->fileSystem = $fileSystem;
        $this->storeManager = $storeManager;
    }

    /**
     * 處理 Queue 訊息
     *
     * @param string $message
     * @return void
     */
    public function process(string $message): void
    {
        $startTime = microtime(true);
        ini_set("memory_limit", "-1");

        try {
            $this->logger->info('EcpayOrderExportConsumer processing message: ' . $message);
            $messageArr = json_decode($message, true);

            if (empty($messageArr)) {
                $this->logger->warning('EcpayOrderExportConsumer: No valid data in message.');
                return;
            }

            $sellerCode = $messageArr['seller_code'] ?? null;
            $fromDate = $messageArr['invoice_created_from'] ?? null;
            $toDate = $messageArr['invoice_created_to'] ?? null;
            $adminName = $messageArr['admin_name'] ?? '';

            if (!$fromDate || !$toDate) {
                $this->logger->error('EcpayOrderExportConsumer: Missing required date range.');
                return;
            }

            $objectManager = ObjectManager::getInstance();
            /** @var \Branch8\MarketPlaceOrderExport\Model\EcpayLogWriter $ecpayLogWriter */
            $ecpayLogWriter = $objectManager->get(\Branch8\MarketPlaceOrderExport\Model\EcpayLogWriter::class);
            /** @var \Branch8\MarketPlaceOrderExport\Model\EcpayLogRecordProvider $ecpayLogRecordProvider */
            $ecpayLogRecordProvider = $objectManager->get(\Branch8\MarketPlaceOrderExport\Model\EcpayLogRecordProvider::class);
            /** @var \Magento\Framework\Registry $registry */
            $registry = $objectManager->get(\Magento\Framework\Registry::class);

            $storeID = $this->storeManager->getStore()->getId();
            
            // 強制忽略限制以確保大數據量能匯出
            if (!$registry->registry('ignore_limit')) {
                $registry->register('ignore_limit', 1);
            }

            // 處理 seller_code 過濾
            $sellerCodesToFilter = [];
            if ($sellerCode === 'all') {
                $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');
                $allSellerCodesSelect = $this->resourceConnection->getConnection()->select()
                    ->from($marketplaceUserdataTableName, ['seller_code']);
                $sellerCodesToFilter = $this->resourceConnection->getConnection()->fetchCol($allSellerCodesSelect);
            } elseif (!empty($sellerCode)) {
                $sellerCodesToFilter = explode(',', $sellerCode);
                $sellerCodesToFilter = array_map('trim', $sellerCodesToFilter);
                $sellerCodesToFilter = array_filter($sellerCodesToFilter);
            }

            $ecpayLogWriter->reset();
            $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Taipei')))->format('YmdHi');
            $fileName = $adminName . ' - ' . '批次訂單結帳匯出 - ' . $timestamp . '.xlsx';
            
            // 手動擴充欄位標題與定義
            $currentColumns = $ecpayLogRecordProvider->getColumns();
            $currentHeaders = $ecpayLogRecordProvider->getHeader();
            
            if (!isset($currentColumns['gift_confirmed_at'])) {
                $currentColumns['gift_confirmed_at'] = '禮物訂單確認時間';
                $currentHeaders[] = '禮物訂單確認時間';
            }
            if (!isset($currentColumns['gift_order_status'])) {
                $currentColumns['gift_order_status'] = '禮物訂單狀態';
                $currentHeaders[] = '禮物訂單狀態';
            }
            
            $ecpayLogRecordProvider->setColumns($currentColumns);
            $ecpayLogRecordProvider->setHeader($currentHeaders);
            
            // 將已更新欄位的 Provider 設定到 Writer 中，確保寫入時包含新增欄位
            $ecpayLogWriter->setRecordProvider($ecpayLogRecordProvider);

            $ecpayLogWriter->setFileName($fileName)->writeHeader();

            // 取得符合條件的訂單 IDs (移除 limit 與 offset)
            $orderIds = $this->getOrdersWithSellerFilter($fromDate, $toDate, $sellerCodesToFilter);
            if (empty($orderIds)) {
                $this->logger->warning('EcpayOrderExportConsumer: No items found for the given criteria.');
                return;
            }

            // 取得 Log 項目與詳細訂單資料
            $logItems = $this->getLogItemsForExport($storeID, $fromDate, $toDate, $orderIds);
            $allRecords = $ecpayLogRecordProvider->getAllRecordOrderItems(array_unique($orderIds));
            
            // 建立 Excel 資料列
            $needToExcelRecord = $this->buildExcelRecordsForExport($logItems, $allRecords);
            
            $ecpayLogWriter->setRecords($needToExcelRecord)->writeRecords();
            $filePath = $ecpayLogWriter->save();

            $this->logger->info('EcpayOrderExportConsumer: File generated at ' . $filePath);

            // 紀錄效能
            $duration = microtime(true) - $startTime;
            $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
            $this->logger->info(sprintf(
                'EcpayOrderExportConsumer Finished. Time: %.2fs, Memory: %.2f MB',
                $duration, $memoryUsage
            ));

        } catch (\Exception $e) {
            $this->logger->error('EcpayOrderExportConsumer Error: ' . $e->getMessage());
        }
    }

    /**
     * 根據賣家與日期取得訂單 IDs
     */
    private function getOrdersWithSellerFilter($fromdate, $todate, $sellerCodes = [])
    {
        $connection = $this->resourceConnection->getConnection();
        $columns = [
            'order_id' => new Expression('DISTINCT (main_table.order_id)'),
        ];
        
        $select = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
            $columns
        )->join(
            ['item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->joinLeft(
            ['soi' => $this->resourceConnection->getTableName('sales_order_item')],
            'item.order_item_id = soi.item_id',
            []
        )->where('main_table.created_at >= ?', $fromdate)
        ->where('main_table.created_at <= ?', $todate)
        ->where('item.export_report = ? ', 1);

        if (!empty($sellerCodes)) {
            $select->where('soi.seller_code IN (?)', $sellerCodes);
        }

        $select->order('main_table.order_id');
        
        return $connection->fetchCol($select);
    }

    /**
     * 取得匯出用的 Log Items
     */
    private function getLogItemsForExport($storeID, $fromdate, $todate, $orderIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $columns = $this->getColumnsForExport($storeID);
        
        $select = $connection->select()->from(
            ['main_table' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
            $columns
        )->join(
            ['item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->joinLeft(
            ['so' => $this->resourceConnection->getTableName('sales_order')],
            'main_table.order_id = so.entity_id',
            []
        )->where('main_table.created_at >= ?', $fromdate)
        ->where('main_table.created_at <= ?', $todate)
        ->where('item.export_report = ? ', 1)
        ->where('main_table.order_id IN (?)', $orderIds);

        return $connection->fetchAll($select) ?: [];
    }

    /**
     * 組合匯出欄位
     */
    private function getColumnsForExport($storeID)
    {
        $objectManager = ObjectManager::getInstance();
        $timezone = $objectManager->get(\Magento\Framework\Stdlib\DateTime\Timezone::class);
        $getTZOffsetTransitions = $objectManager->get(\Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions::class);

        $timezoneConfig = $timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $getTZOffsetTransitions->get($timezoneConfig);

        $columns = [
            'invoice_log_id' => 'main_table.hotai_order_invoice_logs_id',
            'invoice_number' => new Expression("COALESCE(main_table.invoice_number, 'no_value')"),
            'order_id' => 'main_table.order_id',
            'invoice_status' => 'main_table.status',
            'invoice_order_item_id' => 'item.order_item_id',
            'invoice_order_item_name' => 'item.order_item_name',
            'item_include_tax' => 'item.include_tax',
            'price_incl_tax' => 'item.include_tax',
            'qty' => 'item.qty',
            'shipping_price_incl_tax' => 'item.include_tax',
            'item_type' => 'item.type',
            'invoice_order_item_price' => 'item.include_tax',
            'product_name' => 'item.order_item_name',
            'is_reverse' => 'main_table.is_reverse',
            'db_invoice_created_date' => new Expression('DATE_FORMAT(main_table.created_at, "%Y-%m-%d")'),
            'ecpay_log_hotai_checkout_number' => 'main_table.hotai_checkout_number',
            'gift_confirmed_at' => new Expression("CONVERT_TZ(so.gift_confirmed_at, '+00:00', '+08:00')"),
            'gift_order_status' => new Expression("CASE
                        WHEN so.is_gift_order  = 0 THEN NULL
                        WHEN so.is_gift_order  = 1 AND so.status = 'canceled' THEN 'canceled'
                        WHEN so.is_gift_order  = 1 AND so.is_gift_confirmed = 1 THEN 'gift_info_complete'
                        WHEN so.is_gift_order  = 1 AND so.is_gift_confirmed = 0 THEN 'gift_info_pending'
                        ELSE NULL
                        END")
        ];

        if ($dateAdd) {
            $offset = array_key_first($dateAdd);
            $columns['order_checkout_serial_number_date'] = new Expression("DATE_ADD(main_table.created_at, INTERVAL {$offset} SECOND)");
            $columns['order_item_checkout_serial_number_date'] = new Expression("DATE_ADD(item.created_at, INTERVAL {$offset} SECOND)");
        } else {
            $columns['order_checkout_serial_number_date'] = 'main_table.created_at';
            $columns['order_item_checkout_serial_number_date'] = 'item.created_at';
        }
        return $columns;
    }

    /**
     * 建立 Excel 紀錄
     */
    private function buildExcelRecordsForExport($logItems, $allRecords)
    {
        $needToExcelRecord = [];
        foreach ($logItems as $logItem) {
            $orderId = $logItem['order_id'];
            $lastItem = null;
            if (isset($allRecords[$orderId])) {
                $orderItems = $allRecords[$orderId];
                $lastItem = end($orderItems);
            }
            if ($logItem['item_type'] === 'item') {
                if (isset($allRecords[$orderId][$logItem['invoice_order_item_id']])) {
                    $needToExcelRecord[] = array_merge($allRecords[$orderId][$logItem['invoice_order_item_id']], $logItem);
                }
            } elseif ($lastItem) {
                $needToExcelRecord[] = array_merge($lastItem, $logItem);
            }
        }
        return $needToExcelRecord;
    }
}
