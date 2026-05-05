<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Actions;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionAmount;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Detail\NetTotal;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\DetailNetSale;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;

class MonthlyDetailReport
{
    const  FROM_DATE = 'from_date';
    const  TO_DATE = 'to_date';

    const  SELLER_CODE = 'seller_code';
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
     * @param $fromDate
     * @param $toDate
     * @param $sellerCode
     * @return int
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($fromDate, $toDate, $sellerCode = '')
    {
        if (empty($sellerCode)) {
            $sellerCode = 'all';
        }
        if (is_null($fromDate) || is_null($toDate)) {
            throw new \Exception('Please set From Date and To Date');
        }
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );
        if ($sellerCode == 'all') {
            $sellers = $this->getAllSellers();
        } else {
            $sellers = $this->getAllSellers($sellerCode);
        }
        $sellerStoreMapping = $this->getSellerStoreMapping();
        $dir = BP . '/var/export/seller_revenue_detail/';
        if (strpos($dir, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        $this->deleteDirectory($dir);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        foreach ($sellers as $infor) {
            if (empty($infor)) {
                continue;
            }
            //$sellerStoreName = $infor['seller_store_name'];
            $sellerCode = (string)($infor['seller_code'] ?? '');
            $shopTitle = (string)($sellerStoreMapping[$sellerCode] ?? '');

            // Sanitize values to prevent path traversal
            $safeSellerCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sellerCode);
            $safeShopTitle = preg_replace('/[^a-zA-Z0-9_\-\(\)]/u', '_', $shopTitle);

            $fileNameUniqueId = $safeShopTitle ? sprintf('seller-revenue-detail-%s(%s).xlsx',
                $safeSellerCode, $safeShopTitle) : sprintf('seller-revenue-detail-%s.xlsx', $safeSellerCode);

            $this->writter->setFileName($fileNameUniqueId);
            $this->writter->setRecordProvider($this->recordProvider);
            $check = $this->drawDetailSheet($fromDate, $toDate, $infor['seller_code']);
            if (!$check) {
                continue;
            }
            $filePath = $this->writter->save();
            if ($filePath) {
                rename($filePath, $dir . $fileNameUniqueId);
            } else {
                $this->logger->error("Failed to save report for seller: " . $sellerCode);
            }
        }
        $zipFile = $this->scanAndZipFile($fromDate, $toDate);
        $this->emulation->stopEnvironmentEmulation();
        return $zipFile;
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
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        if (strpos($dir, BP) !== 0) {
            return false;
        }
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . basename($item))) {
                return false;
            }
        }
        return rmdir($dir);
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
     * @param $directory
     * @param $zipFile
     * @return mixed
     * @throws \Exception
     */
    private function scanAndZipFile($fromdate, $todate)
    {
        $directory = BP . '/var/export/seller_revenue_detail/';
        if (strpos($directory, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        $dateCombine = (new \DateTime($fromdate, new \DateTimeZone('UTC')))->format('YmdHis') . '_' . (new \DateTime($todate, new \DateTimeZone('UTC')))->format('YmdHis');
        $zipFile = BP . '/var/export/seller_revenue_detail_' . $dateCombine . '.zip';
        if (strpos($zipFile, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        @unlink($zipFile);
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $safeFile = basename($file);
                $filePath = $directory . DIRECTORY_SEPARATOR . $safeFile;
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $safeFile); // Add file with the original file name
                }
            }
            $zip->close();
            return $zipFile;
        } else {
            throw new \Exception("Can't zip dir:" . $directory);
        }
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $sellerCode
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function drawDetailSheet($fromdate, $todate, $sellerCode)
    {
        $sellerStoreMapping = $this->getSellerStoreMapping();
        $sellerStoreName = $sellerCode;
        if (isset($sellerStoreMapping[$sellerCode])) {
            $sellerStoreName = $sellerStoreMapping[$sellerCode];
        }
        $select = $this->getSellerDetailSql();
        $select->where('order_log.created_at >= ?', $fromdate)
            ->where('order_log.created_at <= ?', $todate)
            ->where('order_log.seller_code = ?', $sellerCode);
        $connection = $this->resourceConnection->getConnection();
        $records = $connection->fetchAll($select);
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

        return true;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    private function getSellerDetailSql()
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
            'has_options' => 'catalog_product_entity.has_options',
            'hotai_row_total' => new \Zend_Db_Expr(
                'ROUND((CASE WHEN is_reverse = 1 THEN -(order_item_log.include_tax)
                ELSE (order_item_log.include_tax)
                END
                ) * order_item_log.qty)'
            ),
            'commision_rate' => 'marketplace_saleperpartner.min_commission_rate',
            'special_commission_rate' => 'marketplace_saleperpartner.special_commission_rate',
            'special_price' => new \Zend_Db_Expr(
                '(CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.special_price)
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
            'logistic_support_fee' => new \Zend_Db_Expr(
                "ROUND(
                                    CASE
                                         WHEN sales_order.status IN ('arrived', 'complete')
                                                THEN  sales_order.seller_shipping_amount
                                         ELSE 0
                                    END
                                )"
            ),
            'vendor_share' => new \Zend_Db_Expr(
                "ROUND(COALESCE(sales_order_item.seller_borne_total_amount, 0)) * (CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END)"),
        ];
        $select->from(
            'ecpay_invoice_hotai_order_invoice_logs AS order_log',
            $columns
        )->join('ecpay_invoice_hotai_order_item_invoice_logs AS order_item_log',
            'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
            []
        )->join('sales_order_item',
            'order_item_log.order_item_id=sales_order_item.item_id',
            []
        )->join('sales_order',
            'order_log.order_id=sales_order.entity_id',
            []
        )->joinLeft(
            'marketplace_saleperpartner',
            'order_log.seller_id=marketplace_saleperpartner.seller_id',
            []
        )->joinLeft(
            ['catalog_product_entity'],
            'sales_order_item.origin_sku=catalog_product_entity.sku',
            []
        )->where('order_item_log.export_report = ? ', 1)
            ->where('order_item_log.type = ? ', 'item')
            ->order('order_log.created_at');
        //  $select->where('order_log.seller_code = ? ', 'A0081');
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
}
