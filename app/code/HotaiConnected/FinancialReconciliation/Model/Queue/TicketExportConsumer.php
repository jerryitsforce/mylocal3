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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Sql\Expression;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class TicketExportConsumer
 * 負責非同步產生 2.0 票券報表與球池票券報表
 */
class TicketExportConsumer
{
    private $logger;
    private $jsonSerializer;
    private $resourceConnection;
    private $fileSystem;

    /**
     * @param LoggerInterface $logger
     * @param JsonSerializer $jsonSerializer
     * @param ResourceConnection $resourceConnection
     * @param Filesystem $fileSystem
     */
    public function __construct(
        LoggerInterface $logger,
        JsonSerializer $jsonSerializer,
        ResourceConnection $resourceConnection,
        Filesystem $fileSystem
    ) {
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->resourceConnection = $resourceConnection;
        $this->fileSystem = $fileSystem;
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
            $this->logger->info('EcpayTicketExportConsumer processing message: ' . $message);
            $data = $this->jsonSerializer->unserialize($message);

            if (empty($data)) {
                $this->logger->warning('EcpayTicketExportConsumer: No valid data in message.');
                return;
            }

            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $type = $data['type'] ?? '';
            $adminName = $data['admin_name'] ?? '';

            $connection = $this->resourceConnection->getConnection();
            $select = null;

            if ($type === 'event') {
                $select = $this->getEventExportSelect($connection);
            } else {
                $select = $this->getTicketExportSelect($connection);
            }

            if ($startDate !== null) {
                $select->where('ct.created_at >= DATE_SUB(?, INTERVAL 8 HOUR)', $startDate);
            }
            if ($endDate !== null) {
                $select->where('ct.created_at <= DATE_SUB(?, INTERVAL 8 HOUR)', $endDate);
            }

            $exportData = $connection->fetchAll($select);

            if (empty($exportData)) {
                $this->logger->warning('EcpayTicketExportConsumer: No data found for the provided criteria.');
                return;
            }

            // 產生 Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $headers = array_keys($exportData[0]);
            $sheet->fromArray($headers, NULL, 'A1');

            $rowNum = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray(array_values($item), NULL, 'A' . $rowNum++);
            }

            $typeName = ($type === 'event') ? '批次票券報表匯出(球池)' : '批次票券報表匯出(2.0)';
            $typeName = $adminName . ' - ' . $typeName;
            $writer = new Xlsx($spreadsheet);
            $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Taipei')))->format('YmdHi');
            $fileName = $typeName . ' - ' . $timestamp . '.xlsx';
            
            $directory = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
            $relativePath = 'export/' . $fileName;
            $absolutePath = $directory->getAbsolutePath($relativePath);

            $writer->save($absolutePath);

            $this->logger->info('EcpayTicketExportConsumer: File generated at ' . $absolutePath);

            // 紀錄效能
            $duration = microtime(true) - $startTime;
            $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
            $this->logger->info(sprintf(
                'EcpayTicketExportConsumer Finished (%s). Time: %.2fs, Memory: %.2f MB',
                $type, $duration, $memoryUsage
            ));

        } catch (\Exception $e) {
            $this->logger->error('EcpayTicketExportConsumer Error: ' . $e->getMessage());
        }
    }

    /**
     * 取得球池票券匯出 Select
     */
    private function getEventExportSelect($connection)
    {
        return $connection->select()
            ->from(
                ['mu' => $this->resourceConnection->getTableName('marketplace_userdata')],
                [
                    '特約商名稱' => 'mu.shop_title',
                    '票券類型' => 'te.ticket_type',
                    '行銷活動名稱(目前未定義)' => new Expression("''"),
                    '球池名稱' => 'te.name',
                    '貨號code' => 'tet.batch_code',
                    '票券價值' => 'te.amount',
                    '票券序號' => new Expression("CONCAT(CHAR(8203), tet.serial_number)"),
                    '歸戶時間' => 'ct.created_at',
                    '核銷日期' => new Expression("CONVERT_TZ(tet.redeemed_at, '+00:00', '+08:00')"),
                    '核銷到期日' => 'tet.end_date',
                    '票券狀態' => new Expression("CASE
                        WHEN tet.redeemed_at IS NOT NULL THEN '已核銷'
                        WHEN CURRENT_TIME() >= tet.end_date THEN '已過期'
                        WHEN CURRENT_TIME() < tet.end_date AND tet.redeemed_at IS NULL THEN '未核銷'
                    END"),
                    '廠商訂單編號' => new Expression("CASE
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'edenred_ticket_record' THEN COALESCE(edenred.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'qware_ticket_record' THEN COALESCE(qware.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'family_bonus_pin_ticket_record_v2' THEN COALESCE(fami.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'yoxi_ticket_record_v2' THEN COALESCE(yoxi.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'general_notify_ticket_record' THEN COALESCE(g_notify.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'general_non_notify_ticket_record' THEN COALESCE(gn_notify.used_transaction_no)
                        ELSE NULL
                    END")
                ]
            )
            ->joinLeft(
                ['te' => $this->resourceConnection->getTableName('ticket_event')],
                'mu.seller_id = te.seller_id',
                []
            )
            ->joinLeft(
                ['tet' => $this->resourceConnection->getTableName('ticket_event_ticket')],
                'te.entity_id = tet.event_id',
                []
            )
            ->joinLeft(
                ['ct' => $this->resourceConnection->getTableName('customer_ticket')],
                'tet.entity_id = ct.ticket_table_record_id AND ct.ticket_table_name = \'ticket_event_ticket\'',
                []
            )
            ->joinLeft(
                ['edenred' => $this->resourceConnection->getTableName('edenred_ticket_record')],
                'edenred.record_id = ct.ticket_table_record_id AND edenred.edenred_voucher_no = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['qware' => $this->resourceConnection->getTableName('qware_ticket_record')],
                'qware.record_id = ct.ticket_table_record_id AND qware.qware_sn = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['fami' => $this->resourceConnection->getTableName('family_bonus_pin_ticket_record_v2')],
                'fami.record_id = ct.ticket_table_record_id AND fami.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['yoxi' => $this->resourceConnection->getTableName('yoxi_ticket_record_v2')],
                'yoxi.record_id = ct.ticket_table_record_id AND yoxi.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['g_notify' => $this->resourceConnection->getTableName('general_notify_ticket_record')],
                'g_notify.record_id = ct.ticket_table_record_id AND g_notify.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['gn_notify' => $this->resourceConnection->getTableName('general_non_notify_ticket_record')],
                'gn_notify.record_id = ct.ticket_table_record_id AND gn_notify.serial_number = ct.ticket_unique_content',
                []
            )
            ->group('tet.batch_code')
            ->group('tet.serial_number')
            ->order('ct.created_at');
    }

    /**
     * 取得 2.0 票券匯出 Select
     */
    private function getTicketExportSelect($connection)
    {
        return $connection->select()
            ->from(
                ['ecpay_item' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                [
                    '公司名稱' => 'soi.seller_company_name',
                    'store名稱' => 'ecpay_main.seller_company_name',
                    '票券類型' => 'ct.ticket_table_name',
                    '子訂單編號' => 'so.increment_id',
                    '訂單日期' => new Expression("CONVERT_TZ(so.created_at, '+00:00', '+08:00')"),
                    '訂單狀態' => 'so.status',
                    '訂單結帳序號異動日' => 'ecpay_main.created_at',
                    '訂單結帳序號' => 'so.hotai_checkout_number',
                    '發票號碼' => 'ecpay_main.invoice_number',
                    '付款方式' => new Expression("CASE
                        WHEN (ecpay_main.include_tax) = 0 AND ecpay_main.point_used != 0 THEN '純點'
                        WHEN (ecpay_main.include_tax) != 0 AND ecpay_main.point_used != 0 THEN '點加金'
                        WHEN (ecpay_main.include_tax) != 0 AND ecpay_main.point_used = 0 THEN '純金'
                        END"),
                    '產品名稱' => 'ecpay_item.order_item_name',
                    '商品售價' => 'soi.special_price',
                    '件數' => 'ecpay_item.qty',
                    '成本' => 'soi.base_cost',
                    '點數折抵' => 'soi.row_total_point_discount',
                    '總刷卡金額' => 'so.total_paid',
                    '發票含稅' => 'ecpay_main.include_tax',
                    'Product SKU' => 'soi.sku',
                    '票券貨號' => 'ct.batch_code',
                    '票券序號' => new Expression("CONCAT(CHAR(8203), ct.ticket_unique_content)"),
                    '歸戶日期' => 'ct.created_at',
                    '核銷日期' => 'ct.redeemed_at',
                    '核銷到期日' => 'ct.use_end_time',
                    '票券狀態' => new Expression("CASE
                        WHEN ct.redeemed_at IS NOT NULL THEN '已核銷'
                        WHEN CURRENT_TIME() >= ct.use_end_time THEN '已過期'
                        WHEN CURRENT_TIME() < ct.use_end_time AND ct.redeemed_at IS NULL THEN '未核銷'
                        END"),
                    '廠商訂單編號' => new Expression("CASE
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'edenred_ticket_record' THEN COALESCE(edenred.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'qware_ticket_record' THEN COALESCE(qware.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'family_bonus_pin_ticket_record_v2' THEN COALESCE(fami.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'yoxi_ticket_record_v2' THEN COALESCE(yoxi.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'general_notify_ticket_record' THEN COALESCE(g_notify.used_transaction_no)
                        WHEN ct.status = 3 AND ct.ticket_table_name = 'general_non_notify_ticket_record' THEN COALESCE(gn_notify.used_transaction_no)
                        ELSE NULL
                        END"),
                    '禮物訂單確認時間' => new Expression("CONVERT_TZ(so.gift_confirmed_at, '+00:00', '+08:00')"),
                    '禮物訂單狀態' => new Expression("CASE
                        WHEN so.is_gift_order  = 0 THEN NULL
                        WHEN so.is_gift_order  = 1 AND so.status = 'canceled' THEN 'canceled'
                        WHEN so.is_gift_order  = 1 AND so.is_gift_confirmed = 1 THEN 'gift_info_complete'
                        WHEN so.is_gift_order  = 1 AND so.is_gift_confirmed = 0 THEN 'gift_info_pending'
                        ELSE NULL
                        END")
                ]
            )
            ->joinLeft(
                ['ecpay_main' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                'ecpay_item.hotai_order_invoice_log_id = ecpay_main.hotai_order_invoice_logs_id',
                []
            )
            ->joinLeft(
                ['soi' => $this->resourceConnection->getTableName('sales_order_item')],
                'ecpay_item.order_item_id = soi.item_id',
                []
            )
            ->joinLeft(
                ['so' => $this->resourceConnection->getTableName('sales_order')],
                'soi.order_id = so.entity_id',
                []
            )
            ->joinLeft(
                ['ct' => $this->resourceConnection->getTableName('customer_ticket')],
                'soi.item_id = ct.sales_order_item_id',
                []
            )
            ->joinLeft(
                ['edenred' => $this->resourceConnection->getTableName('edenred_ticket_record')],
                'edenred.record_id = ct.ticket_table_record_id AND edenred.edenred_voucher_no = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['qware' => $this->resourceConnection->getTableName('qware_ticket_record')],
                'qware.record_id = ct.ticket_table_record_id AND qware.qware_sn = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['fami' => $this->resourceConnection->getTableName('family_bonus_pin_ticket_record_v2')],
                'fami.record_id = ct.ticket_table_record_id AND fami.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['yoxi' => $this->resourceConnection->getTableName('yoxi_ticket_record_v2')],
                'yoxi.record_id = ct.ticket_table_record_id AND yoxi.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['g_notify' => $this->resourceConnection->getTableName('general_notify_ticket_record')],
                'g_notify.record_id = ct.ticket_table_record_id AND g_notify.serial_number = ct.ticket_unique_content',
                []
            )
            ->joinLeft(
                ['gn_notify' => $this->resourceConnection->getTableName('general_non_notify_ticket_record')],
                'gn_notify.record_id = ct.ticket_table_record_id AND gn_notify.serial_number = ct.ticket_unique_content',
                []
            )
            ->where('ecpay_item.type = ?', 'item')
            ->where('so.increment_id NOT LIKE ?', '%hotai_order_%')
            ->group('ct.batch_code')
            ->group('ct.ticket_unique_content')
            ->order('so.created_at');
    }
}
