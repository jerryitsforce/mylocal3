<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Helper;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionAmount;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\DetailNetSale;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\DetailPlatformShare;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Detail\NetTotal;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EcpaySellerRevenueExporter
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EcpaySellerRevenueExporter
{
    const HALF_WIDTH = 0.5;
    const FULL_WIDTH = 1.0;
    /**
     * @var \Magento\Framework\File\Csv
     */
    private \Magento\Framework\File\Csv $csv;
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;

    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;

    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    private GetTZOffsetTransitions $getTZOffsetTransitions;
    private StoreManager $storeManager;
    /**
     * @var \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter
     */
    private $writter;
    /**
     * @var \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider
     */
    private $recordProvider;
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\File\Csv $csv
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider $recordProvider
     * @param \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter $writer
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State                                                  $appState,
        \Magento\Framework\File\Csv                                                   $csv,
        ResourceConnection                                                            $resourceConnection,
        \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider $recordProvider,
        \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter         $writer,
        GetTZOffsetTransitions                                                        $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone                                   $timezone,
        StoreManager                                                                  $storeManager,
        LoggerInterface                                                               $logger,
        Emulation                                                                     $emulation = null,
    )
    {
        $this->timezone = $timezone;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->resourceConnection = $resourceConnection;
        $this->csv = $csv;
        $this->storeManager = $storeManager;
        $this->recordProvider = $recordProvider;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->writter = $writer;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
    }

    /**
     * Export seller revenue details and return the path to the generated zip file.
     *
     * @param string $fromDate
     * @param string $toDate
     * @param string $sellerCode
     * @return string The path to the generated zip file.
     * @throws \Exception
     */
    public function exportAndZip(string $fromDate, string $toDate, string $sellerCode): string
    {
        if (empty($sellerCode)) {
            $sellerCode = 'all';
        }

        if (is_null($fromDate) || is_null($toDate)) {
            throw new \Exception('Please set From Date and To Date');
        }
        $storeID = $this->storeManager->getStore()->getId();
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );

        $connection = $this->resourceConnection->getConnection();
        $frTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $excludeSelect = $connection->select()
            ->from($frTableName, ['seller_code', 'exclude_pending_gift_order'])
            ->where('`from` = ?', $fromDate)
            ->where('`to` = ?', $toDate);
        if ($sellerCode != 'all') {
            $excludeSelect->where('seller_code = ?', $sellerCode);
        }
        $excludeFlags = $connection->fetchPairs($excludeSelect);

        if ($sellerCode == 'all') {
            $sellers = $this->getAllSellers();
        } else {
            $sellers = $this->getAllSellers($sellerCode);
        }
        $sellerStoreMapping = $this->getSellerStoreMapping();
        $dir = BP . '/var/tmp/seller_revenue_download/';
        // Ensure the directory exists, but do not delete it here
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $generatedFilePath = '';
        foreach ($sellers as $infor) {
            if (empty($infor)) {
                continue;
            }
            $currentSellerCode = $infor['seller_code'];
            $excludeGift = (isset($excludeFlags[$currentSellerCode]) && $excludeFlags[$currentSellerCode] == 1);

            $fileNameUniqueId = isset($sellerStoreMapping[$currentSellerCode]) ? sprintf('seller-revenue-detail-%s(%s).xlsx',
                $currentSellerCode, $sellerStoreMapping[$currentSellerCode]) : sprintf('seller-revenue-detail-%s.xlsx', $currentSellerCode);
            $this->writter->setFileName($fileNameUniqueId);
            $this->writter->setRecordProvider($this->recordProvider);
            $check = $this->drawDetailSheet($fromDate, $toDate, $currentSellerCode, $excludeGift);
            if (!$check) {
                continue;
            }
            $filePath = $this->writter->save();
            $generatedFilePath = $dir . $fileNameUniqueId;
            rename($filePath, $generatedFilePath);
        }
        $this->emulation->stopEnvironmentEmulation();
        return $generatedFilePath; // Return the path to the generated Excel file
    }

    /**
     * @return array
     */
    private function getAllSellers($sellerCode = null)
    {
        $select = $this->getSellerDetailSql();
        $select->reset(
            \Zend_Db_Select::COLUMNS
        );
        $columns = [
            'seller_code' => new \Zend_Db_Expr('DISTINCT order_log.seller_code'),
            //  'seller_store_name' => new \Zend_Db_Expr('order_log.seller_shop_name')
        ];
        if ($sellerCode) {
            $select->where('order_log.seller_code = ?', $sellerCode);
        }
        $select->columns($columns)->order('order_log.seller_code');
        return $this->resourceConnection->getConnection()->fetchAll($select);
    }

    /**
     * @return array
     */
    private function getSellerStoreMapping()
    {
        $select = $this->resourceConnection->getConnection()->select()->from(
            'marketplace_userdata', ['seller_code', 'shop_title'])
            ->where('seller_code is not null');
        return $this->resourceConnection->getConnection()->fetchPairs($select);
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $sellerCode
     * @param bool $excludeGift
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function drawDetailSheet($fromdate, $todate, $sellerCode, $excludeGift = false)
    {
        $sellerStoreMapping = $this->getSellerStoreMapping();
        $sellerStoreName = $sellerCode;
        if (isset($sellerStoreMapping[$sellerCode])) {
            $sellerStoreName = $sellerStoreMapping[$sellerCode];
        }

        if ($excludeGift) {
            $this->logger->info(sprintf(
                "Financial Reconciliation Detail: Filtering pending gift orders for seller: %s",
                $sellerCode
            ));
        }

        $select = $this->getSellerDetailSql($excludeGift);
        $select->where('order_log.created_at >= ?', $fromdate)
            ->where('order_log.created_at <= ?', $todate)
            ->where('order_log.seller_code = ?', $sellerCode);
        $connection = $this->resourceConnection->getConnection();
        $records = $connection->fetchAll($select);

        // 獲取例外授權記錄
        $exceptionRecords = $this->getExceptionRecords($fromdate, $todate, $sellerCode);
        if (!empty($exceptionRecords)) {
            $records = array_merge($records, $exceptionRecords);
        }

        if (count($records) == 0) {
            return false;
        }
        $this->writter->reset();
        $this->writter->getSpreedSheet()->getActiveSheet()->disconnectCells();
        $this->writter->getSpreedSheet()->removeSheetByIndex(0);
        $this->writter->getSpreedSheet()->createSheet(0)->setTitle($sellerCode);
        $this->writter->getSpreedSheet()->setActiveSheetIndex(0);
        $this->writter->setPrint();

        // 加入 Excel 列印頁面與縮放設定
        $sheet = $this->writter->getSpreedSheet()->getActiveSheet();
        $pageSetup = $sheet->getPageSetup();

        // 列印設定為 A4 橫向，一頁列印寬度
        $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        // 邊距設定（可微調）
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setRight(0.2);
        $sheet->getPageMargins()->setLeft(0.2);
        $sheet->getPageMargins()->setBottom(0.3);

        $sheet->getStyle('A1:P1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:P1')->applyFromArray([
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(29);
        $sheet->getColumnDimension('C')->setWidth(29);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(28);

        foreach (range('F', 'M') as $col) {
            $sheet->getColumnDimension($col)->setWidth(10);
        }
        $sheet->getColumnDimension('K')->setWidth(11);
        $sheet->getColumnDimension('N')->setWidth(12);
        $sheet->getColumnDimension('O')->setWidth(12);

        $netTotalObj = ObjectManager::getInstance()->get(NetTotal::class);
        $netTotalSum = 0;
        foreach ($records as &$record) {
            $netTotalSum += $netTotalObj->processColumnData($record);
            if (isset($record['product_name'])) {
                $record['product_name'] = $this->limitMultilineText($record['product_name'], 2, 11);
            }
        }
        unset($record); // 釋放引用，避免後續迴圈覆蓋最後一筆資料
        $tax = round(($netTotalSum / 1.05) * 0.05);

        $this->recordProvider->setColumns([
            'order_checkout_serial_number_date' => 'order_checkout_serial_number_date',
            'sub_order_number' => 'sub_order_number',
            'product_sku' => 'product_sku',
            'supplier_sku' => ObjectManager::getInstance()->get(\Branch8\MarketPlaceOrderExportSeller\Model\Columns\SupplierSku::class),
            'product_name' => 'product_name',
            'qty' => 'qty',
            'special_price' => 'special_price',
            'hotai_row_total' => 'hotai_row_total',
            'commision_rate' => ObjectManager::getInstance()->get(CommissionRate::class),
            'commission_amount' => ObjectManager::getInstance()->get(CommissionAmount::class),
            'net_sale' => ObjectManager::getInstance()->get(DetailNetSale::class),
            'purchase_cost' => 'purchase_cost',
            'vendor_share' => 'vendor_share',
            'logistic_support_fee' => 'logistic_support_fee',
            'net_total' => ObjectManager::getInstance()->get(NetTotal::class),

        ]);

        $this->recordProvider->setHeader([
            __('Order Checkout Serial Number Modification Date'),
            __('Sub Order Number'),
            __('Product Sku'),
            __('Supplier Sku'),
            __('Export Product Name'),
            __('Number of Items'),
            __('Special Price'),
            __('Transaction Price'),
            __('抽成%'),
            __('Agreed service fee'),
            __('Net Sales'),
            __('Purchase Cost'),
            __('Marketing Fee'),
            __('Logistic Support Fee'),
            __('Net Total'),
        ]);


        $this->writter->setTotalIndexColumns([
            __('Number of Items')->render() => 0,
            __('Special Price')->render() => 0,
            __('Transaction Price')->render() => 0,
            __('Agreed service fee')->render() => 0,
            __('Net Sales')->render() => 0,
            __('Purchase Cost')->render() => 0,
            __('Marketing Fee')->render() => 0,
            __('Logistic Support Fee')->render() => 0,
            __('Net Total')->render() => 0,
        ]);
        $this->writter->setRecords($records);
        $this->writter->writeHeader();
        $this->writter->writeRecords();

        $totalRow = [
            __('Order Checkout Serial Number Modification Date')->render() => '合計',
            __('Sub Order Number')->render() => '',
            __('Product Sku')->render() => '',
            __('Supplier Sku')->render() => '',
            __('Export Product Name')->render() => '',
            __('Number of Items')->render() => '',
            __('Special Price')->render() => '',
            __('Transaction Price')->render() => '',
            __('抽成%')->render() => '',
            __('Agreed service fee')->render() => '',
            __('Net Sales')->render() => '',
            __('Purchase Cost')->render() => '',
            __('Marketing Fee')->render() => '',
            __('Logistic Support Fee')->render() => '',
            __('Net Total')->render() => ''
        ];

        $this->writter->setTotalRecord($totalRow)->writeTotalRecord(false);

        $sheet->insertNewRowBefore(1, 3);

        // 設定前三列資訊內容
        $sheet->setCellValue('A1', '特約商名稱');
        $sheet->setCellValue('B1', $sellerStoreName);

        $sheet->setCellValue('A2', '結帳月份');
        $sheet->setCellValue('B2', date('y/m', strtotime($todate)) . '月');

        $sheet->setCellValue('A3', '結帳區間');
        $sheet->setCellValue('B3', date('y/m/d', strtotime($fromdate)) . ' ～ ' . date('y/m/d', strtotime($todate)));

        $sheet->setCellValue('N1', '銷售額(未稅)');
        $sheet->setCellValue('O1', $netTotalSum - $tax);

        $sheet->setCellValue('N2', '營業稅額');
        $sheet->setCellValue('O2', $tax);

        $sheet->setCellValue('N3', '總計(含稅)');
        $sheet->setCellValue('O3', $netTotalSum);

        $sheet->getStyle('O1:O3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // 美化（可選）
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);

        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(40);
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A{$row}:Q{$row}")->getAlignment()->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            );
        }
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getFont()->setSize(12);

        // 處理例外授權紀錄顏色 (在所有結構調整後處理)
        foreach ($records as $index => $record) {
            if (isset($record['is_exception']) && $record['is_exception']) {
                $rowNum = $index + 5; // 標題列位移(1->4) + 數據起始列(2->5)
                // 明確指定資料範圍 A 到 O 並設定藍色字體
                $sheet->getStyle("A{$rowNum}:O{$rowNum}")->getFont()->getColor()->setRGB('0000FF');
            }
        }

        return true;
    }

    /**
     * @param bool $excludeGift
     * @return \Magento\Framework\DB\Select
     */
    private function getSellerDetailSql($excludeGift = false)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $columns = [
            'sub_order_number' => 'sales_order.increment_id',
            'seller_code' => 'order_log.seller_code',
            'order_id' => 'order_log.order_id',
            'is_reverse' => 'order_log.is_reverse',
            'qty' => new \Zend_Db_Expr(
                '(CASE WHEN is_reverse = 1
                THEN -order_item_log.qty
                ELSE order_item_log.qty END)'
            ),
            'order_checkout_serial_number_date' => new \Zend_Db_Expr('DATE_FORMAT(order_log.created_at, "%Y-%m-%d")'),
            'order_item_checkout_serial_number_date' => 'order_item_log.created_at',
            'product_name' => 'order_item_log.order_item_name',
            'product_sku' => 'sales_order_item.sku',
            'origin_sku' => 'sales_order_item.origin_sku',
            'option_sku' => 'sales_order_item.option_sku',
            'variation_sku' => 'sales_order_item.variation_sku',
            'has_options' => 'catalog_product_entity.has_options',
            'hotai_row_total' => new \Zend_Db_Expr(
                '(CASE WHEN is_reverse = 1 THEN -ROUND(order_item_log.include_tax)
                ELSE ROUND(order_item_log.include_tax)
                END
                ) * order_item_log.qty'
            ),
            'commision_rate' => 'marketplace_saleperpartner.min_commission_rate',
            'special_price' => new \Zend_Db_Expr(
                '(CASE WHEN is_reverse = 1 THEN -ROUND(sales_order_item.special_price)
                ELSE ROUND(sales_order_item.special_price)
                END
                ) * sales_order_item.qty_ordered'
            ),
            'purchase_cost' => new \Zend_Db_Expr(
                '(CASE WHEN is_reverse = 1 THEN -ROUND(COALESCE(sales_order_item.base_cost * sales_order_item.qty_ordered, 0))
                           ELSE ROUND(COALESCE(sales_order_item.base_cost * sales_order_item.qty_ordered, 0))
                           END)'
            ),
            'marketing_fee' => new \Zend_Db_Expr("0"),// pharse 2
            'logistic_support_fee' => new \Zend_Db_Expr("0"),//pharse 2,
            'vendor_share' => new \Zend_Db_Expr('
            (CASE WHEN sales_order_item.seller_borne_total_amount IS NULL THEN 0
              ELSE ROUND(sales_order_item.seller_borne_total_amount)
            END)
            '),
        ];
        $select->from(
            ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
            $columns
        )->join(['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
            []
        )->join(['sales_order_item' => $this->resourceConnection->getTableName('sales_order_item')],
            'order_item_log.order_item_id=sales_order_item.item_id',
            []
        )->join(['sales_order' => $this->resourceConnection->getTableName('sales_order')],
            'order_log.order_id=sales_order.entity_id',
            []
        )->joinLeft(
            ['marketplace_saleperpartner' => $this->resourceConnection->getTableName('marketplace_saleperpartner')],
            'order_log.seller_id=marketplace_saleperpartner.seller_id',
            []
        )->joinLeft(
            ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'sales_order_item.origin_sku=catalog_product_entity.sku',
            []
        )->where('order_item_log.export_report = ? ', 1)
            ->where('order_item_log.type = ? ', 'item');

        if ($excludeGift) {
            $select->where('NOT (sales_order.is_gift_order = 1 AND sales_order.is_gift_confirmed = 0)');
        }

        $select->order('order_log.created_at');
        return $select;
    }

    /**
     * 限制多行文本的長度（中文字/全形符號 1，英數/半形符號/空白 0.5）
     *
     * @param string $text 輸入文字
     * @param int $maxLines 最大行數限制
     * @param int $maxUnitsPerLine 每行最大單位寬度
     * @return string 處理後的文字（含換行與省略符號）
     */
    public static function limitMultilineText($text, $maxLines = 2, $maxUnitsPerLine = 15)
    {
        $text = trim(strip_tags($text));
        $lines = [];
        $currentLine = '';
        $unitCount = 0;

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            $currentLine .= $char;

            $ord = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'))[1];
            if (preg_match('/\s/u', $char)) {
                $unitCount += self::HALF_WIDTH;
            } elseif (
                ($ord >= 0x4E00 && $ord <= 0x9FFF) ||
                ($ord >= 0x3000 && $ord <= 0x303F) ||
                ($ord >= 0xFF01 && $ord <= 0xFF60)
            ) {
                $unitCount += self::FULL_WIDTH;
            } else {
                $unitCount += self::HALF_WIDTH;
            }

            if ($unitCount >= $maxUnitsPerLine) {
                $lines[] = $currentLine;
                $currentLine = '';
                $unitCount = 0;
            }

            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if (count($lines) < $maxLines && $currentLine !== '') {
            $lines[] = $currentLine;
        }

        // 判斷是否加省略符號
        $totalUnits = 0;
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            $ord = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'))[1];

            if (preg_match('/\s/u', $char)) {
                $totalUnits += self::HALF_WIDTH;
            } elseif (
                ($ord >= 0x4E00 && $ord <= 0x9FFF) ||
                ($ord >= 0x3000 && $ord <= 0x303F) ||
                ($ord >= 0xFF01 && $ord <= 0xFF60)
            ) {
                $totalUnits += self::FULL_WIDTH;
            } else {
                $totalUnits += self::HALF_WIDTH;
            }

            if ($totalUnits > $maxLines * $maxUnitsPerLine) {
                $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1]) . '…';
                break;
            }
        }

        return implode("\n", $lines);
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
     * 獲取例外授權記錄
     *
     * @param string $fromdate
     * @param string $todate
     * @param string $sellerCode
     * @return array
     */
    private function getExceptionRecords($fromdate, $todate, $sellerCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $frTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $freTableName = $this->resourceConnection->getTableName('financial_reconciliation_exception');
        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        $orderItemTableName = $this->resourceConnection->getTableName('sales_order_item');

        $select = $connection->select()
            ->from(['fre' => $freTableName], [
                'commission_rate', 'agreed_service_fee', 'net_sales', 'purchase_cost',
                'marketing_fee', 'logistic_support_fee', 'reason', 'created_at'
            ])
            ->joinInner(
                ['fr' => $frTableName],
                'fre.fr_id = fr.id',
                []
            )
            ->joinLeft(
                ['so' => $orderTableName],
                'fre.order_id = so.entity_id',
                ['sub_order_number' => 'so.increment_id']
            )
            ->joinLeft(
                ['soi' => $orderItemTableName],
                'fre.item_id = soi.item_id',
                ['product_sku' => 'soi.sku', 'product_name' => 'soi.name', 'origin_sku', 'option_sku', 'variation_sku']
            )
            ->joinLeft(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'soi.origin_sku = cpe.sku',
                ['has_options']
            )
            ->where('fr.from = ?', $fromdate)
            ->where('fr.to = ?', $todate)
            ->where('fr.seller_code = ?', $sellerCode)
            ->where('fre.exception_status = ?', 3);

        $exceptions = $connection->fetchAll($select);
        $records = [];

        foreach ($exceptions as $exception) {
            $records[] = [
                'is_exception' => true,
                'item_type' => 'item', // 確保 VendorSku 處理器能運作
                'order_checkout_serial_number_date' => date('Y-m-d', strtotime($exception['created_at'])),
                'sub_order_number' => $exception['sub_order_number'] ?? '例外授權',
                'product_sku' => $exception['product_sku'] ?? '',
                'origin_sku' => $exception['origin_sku'] ?? '',
                'option_sku' => $exception['option_sku'] ?? '',
                'variation_sku' => $exception['variation_sku'] ?? '',
                'has_options' => $exception['has_options'] ?? 0,
                'product_name' => $exception['product_name'] ?? ($exception['reason'] ?? '例外授權調整'),
                'qty' => 0,
                'special_price' => 0,
                'hotai_row_total' => 0,
                'commision_rate' => isset($exception['commission_rate']) ? (float)$exception['commission_rate'] . '%' : '',
                'commission_amount' => $exception['agreed_service_fee'] ?? 0,
                'net_sale' => $exception['net_sales'] ?? 0,
                'purchase_cost' => $exception['purchase_cost'] ?? 0,
                'vendor_share' => $exception['marketing_fee'] ?? 0,
                'logistic_support_fee' => $exception['logistic_support_fee'] ?? 0,
            ];
        }

        return $records;
    }
}
