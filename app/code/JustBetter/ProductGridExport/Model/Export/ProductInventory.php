<?php
namespace JustBetter\ProductGridExport\Model\Export;

use Branch8\MarketplaceProductImport\Plugin\Webkul\MpMassUpload\Helper\Data as HelperData;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as FilesystemDirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductInventory
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var SearchResultIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param DateTime $dateTime
     * @param ResourceConnection $resourceConnection
     * @param SearchResultIteratorFactory $iteratorFactory
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $eavConfig
     * @param RequestInterface $request
     * @param Json|null $json
     */
    public function __construct(
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        DateTime $dateTime,
        ResourceConnection $resourceConnection,
        SearchResultIteratorFactory $iteratorFactory,
        Filesystem $filesystem,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        LoggerInterface $logger,
        RequestInterface $request,
        Json $json = null
    ) {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
        $this->iteratorFactory = $iteratorFactory;
        $this->directory = $filesystem->getDirectoryWrite(FilesystemDirectoryList::VAR_DIR);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * Get CSV file for export
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCsvFile()
    {
        $this->logger->info('[export_inventory] ' . json_encode($this->request->getParams(), JSON_PRETTY_PRINT));
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $this->logger->info('[export_inventory] getSelect:'.$collection->getSelect());
        $this->logger->info('[export_inventory] getSize:'.$collection->getSize());
        $productIds = $collection->getAllIds();

        $this->logger->info('[export_inventory] Filter get ' . count($productIds) . ' products');

        return $this->exportInventoryReport($productIds);
    }

    public function getXlsFile()
    {
        $this->logger->info('[export_inventory] ' . json_encode($this->request->getParams(), JSON_PRETTY_PRINT));
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $this->logger->info('[export_inventory] getSelect:'.$collection->getSelect());
        $this->logger->info('[export_inventory] getSize:'.$collection->getSize());
        $productIds = $collection->getAllIds();

        $this->logger->info('[export_inventory] Filter get ' . count($productIds) . ' products');

        return $this->exportInventoryReportXls($productIds);
    }

    public function exportInventoryReportXls($productIds)
    {
        // Generate unique filename (non-security usage)
        $name = bin2hex(random_bytes(16));
        $fileName = 'inventory_report_' . $this->dateTime->date('Y-m-d_H-i-s') . '_' . $name . '.xls';
        $file = 'export/' . $fileName;

        // Headers for the XLS file
        $headers = [
            '賣場名稱',
            '主sku',
            '品名',
            '單規/多規',
            'spec_name',
            'variation_sku',
            'option_sku',
            '庫存狀態',
            '上下架狀態(若為單規且下架，可銷售庫存會為0)',
            '套用Simple SKU成本設定',
            '套用Simple SKU售價設定',
            '抽成設置',
            '抽成比率',
            '成本',
            '售價',
            '主SKU Price',
            '主SKU Special Price',
            '前台可銷售庫存'
        ];

        // 準備所有產品資料
        $multiSpecData = $this->getMultiSpecProductData($productIds);
        $singleSpecData = $this->getSingleSpecProductData($productIds);

        $allData = array_merge($multiSpecData, $singleSpecData);
        $this->logger->info('[export_inventory] Product split in table: ' . count($multiSpecData) . ' multi-spec, ' . count($singleSpecData) . ' single-spec');

        // 確保目錄存在
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        foreach ($headers as $columnIndex => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($column . '1', $header);

            // Add yellow background to "前台可銷售庫存" column
            if ($header === '前台可銷售庫存' || $header === '庫存狀態') {
                $sheet->getStyle($column . '1:' . $column . (count($allData) + 1))
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('FFFF00');
            }
        }

        // Add data
        foreach ($allData as $rowIndex => $rowData) {
            foreach ($rowData as $columnIndex => $value) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValue($column . ($rowIndex + 2), $value);
            }
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        $writer->save($this->directory->getAbsolutePath($file));

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }

    /**
     * Export inventory report to CSV based on selected products
     *
     * @param array $productIds
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportInventoryReport($productIds)
    {
        // Generate unique filename (non-security usage)
        $name = bin2hex(random_bytes(16));
        $fileName = 'inventory_report_' . $this->dateTime->date('Y-m-d_H-i-s') . '_' . $name . '.csv';
        $file = 'export/' . $fileName;

        // Headers for the CSV file
        $headers = [
            '賣場名稱',
            '主sku',
            '品名',
            '單規/多規',
            'spec_name',
            'variation_sku',
            'option_sku',
            '庫存狀態',
            '上下架狀態(若為單規且下架，可銷售庫存會為0)',
            '套用Simple SKU成本設定',
            '套用Simple SKU售價設定',
            '抽成設置',
            '抽成比率',
            '成本',
            '售價',
            '主SKU Price',
            '主SKU Special Price',
            '前台可銷售庫存'
        ];

        // 準備所有產品資料
        $multiSpecData = $this->getMultiSpecProductData($productIds);
        $singleSpecData = $this->getSingleSpecProductData($productIds);

        $allData = array_merge($multiSpecData, $singleSpecData);
        $this->logger->info('[export_inventory] Product split in table: ' . count($multiSpecData) . ' multi-spec, ' . count($singleSpecData) . ' single-spec');

        // 確保目錄存在
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        // 寫入 UTF-8 BOM
        $stream->write("\xEF\xBB\xBF");

        // 寫入CSV表頭
        $stream->writeCsv($headers);

        // 寫入所有資料
        foreach ($allData as $row) {
            $stream->writeCsv($row);
        }

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }

    /**
     * Get data for products with multiple specifications
     *
     * @param array $productIds
     * @return array
     */
    protected function getMultiSpecProductData($productIds)
    {
        $connection = $this->resourceConnection->getConnection();

        // 多規庫存查詢
        $select = $connection->select()
            ->distinct()
            ->from(
                ['osi' => $this->resourceConnection->getTableName('wk_osi_variations')],
                [
                    '商品ID' => 'cpe.entity_id',
                    '賣場名稱' => 'seller.shop_title',
                    '品名' => 'cpev73.value',
                    '單規/多規' => new \Zend_Db_Expr("'多規'"),
                    '主sku' => 'cpe.sku',
                    'spec_name' => 'osi.comb',
                    'variation_sku' => 'osi.sku',
                    'option_sku' => 'opt.sku',
                    '庫存狀態' => new \Zend_Db_Expr("CASE WHEN SUM(osi.stock) > 0 THEN '1' ELSE '0' END"),
                    '上下架狀態(若為單規且下架，可銷售庫存會為0)' => new \Zend_Db_Expr('CASE WHEN opt.is_visible = 0 THEN "下架" ELSE "上架" END'),
                    '套用Simple SKU成本設定' => 'osi.follow_simple_sku_cost_setting',
                    '套用Simple SKU售價設定' => 'osi.follow_simple_sku_price_setting',
                    '抽成設置' => 'osi.cost_setting',
                    '抽成比率' => 'osi.commission_percent',
                    '成本' => 'osi.cost',
                    '售價' => 'osi.price',
                    '主SKU Price' => 'price.value',
                    '主SKU Special Price' => 'special_price.value',
                    '前台可銷售庫存' => 'osi.stock'
                ]
            )
            ->joinLeft(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpe.row_id = osi.product_id',
                []
            )
            ->joinLeft(
                ['opt' => $this->resourceConnection->getTableName('catalog_product_option_type_value')],
                'opt.sku = osi.sku',
                []
            )
            ->joinLeft(
                ['mp' => $this->resourceConnection->getTableName('marketplace_product')],
                'mp.mage_pro_row_id = cpe.row_id',
                []
            )
            ->joinLeft(
                ['cpev73' => $this->resourceConnection->getTableName('catalog_product_entity_varchar')],
                'cpev73.row_id = cpe.row_id AND cpev73.attribute_id = 73',
                []
            )
            ->joinLeft(
                ['seller' => $this->resourceConnection->getTableName('marketplace_userdata')],
                'seller.seller_id = mp.seller_id',
                []
            )
            ->joinLeft(
                ['cpei97' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                'cpei97.row_id = cpe.row_id AND cpei97.attribute_id = 97',
                []
            )
            ->joinLeft(
                ['price' => $this->resourceConnection->getTableName('catalog_product_entity_decimal')],
                'price.row_id = cpe.row_id AND price.attribute_id = 77',
                []
            )
            ->joinLeft(
                ['special_price' => $this->resourceConnection->getTableName('catalog_product_entity_decimal')],
                'special_price.row_id = cpe.row_id AND special_price.attribute_id = 78',
                []
            )
            ->where('cpe.has_options = ?', 1);

        if (!empty($productIds)) {
            $select->where('cpe.entity_id IN (?)', $productIds);
        }
        $select->group(['osi.comb', 'cpe.entity_id']);
        $select->order(['cpe.sku', 'osi.sku DESC']);
        $data = $connection->fetchAll($select);

        $result = [];
        foreach ($data as $row) {
            $result[] = [
                $row['賣場名稱'],
                $row['主sku'],
                $row['品名'],
                $row['單規/多規'],
                $row['spec_name'],
                $row['variation_sku'],
                $row['option_sku'],
                $row['庫存狀態'],
                $row['上下架狀態(若為單規且下架，可銷售庫存會為0)'],
                $row['套用Simple SKU成本設定'],
                $row['套用Simple SKU售價設定'],
                $row['抽成設置'],
                $row['抽成比率'],
                $row['成本'],
                $row['售價'],
                $row['主SKU Price'],
                $row['主SKU Special Price'],
                $row['前台可銷售庫存']
            ];
        }

        return $result;
    }

    /**
     * Get data for products with single specification
     *
     * @param array $productIds
     * @return array
     */
    protected function getSingleSpecProductData($productIds)
    {
        $connection = $this->resourceConnection->getConnection();

        // 單規庫存查詢
        $select = $connection->select()
            ->distinct()
            ->from(
                ['csi' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                [
                    '商品ID' => 'cpe.entity_id',
                    '賣場名稱' => 'seller.shop_title',
                    '品名' => 'cpev73.value',
                    '單規/多規' => new \Zend_Db_Expr("'單規'"),
                    '主sku' => 'cpe.sku',
                    'spec_name' => new \Zend_Db_Expr("''"),
                    'variation_sku' => new \Zend_Db_Expr("''"),
                    'option_sku' => new \Zend_Db_Expr("''"),
                    '庫存狀態' => 'csi.is_in_stock',
                    '上下架狀態(若為單規且下架，可銷售庫存會為0)' => new \Zend_Db_Expr('CASE WHEN cpei97.value = 2 THEN "下架" ELSE "上架" END'),
                    '套用Simple SKU成本設定' => new \Zend_Db_Expr("''"),
                    '套用Simple SKU售價設定' => new \Zend_Db_Expr("''"),
                    '抽成設置' => new \Zend_Db_Expr("''"),
                    '抽成比率' => new \Zend_Db_Expr("''"),
                    '成本' => new \Zend_Db_Expr("''"),
                    '售價' => new \Zend_Db_Expr("''"),
                    '主SKU Price' => 'price.value',
                    '主SKU Special Price' => 'special_price.value',
                    '前台可銷售庫存' => new \Zend_Db_Expr('CASE WHEN cpei97.value = 2 THEN "-" ELSE csi.qty + COALESCE(ir.reserved_qty, 0) END')
                ]
            )
            ->joinLeft(
                // 確保只取 row_id 最舊的一筆
                ['cpe' => new \Zend_Db_Expr(
                    '(SELECT * FROM ' . $this->resourceConnection->getTableName('catalog_product_entity') . ' AS cpe1
                     WHERE row_id = (
                        SELECT MIN(row_id)
                        FROM ' . $this->resourceConnection->getTableName('catalog_product_entity') . '
                        WHERE entity_id = cpe1.entity_id
                     ))'
                )],
                'cpe.entity_id = csi.product_id',
                []
            )
            ->joinLeft(
                // 先計算 reserved_qty，避免 JOIN 時數據重複累加
                ['ir' => new \Zend_Db_Expr(
                    '(SELECT sku, SUM(quantity) AS reserved_qty
                      FROM ' . $this->resourceConnection->getTableName('inventory_reservation') . '
                      GROUP BY sku)'
                )],
                'cpe.sku = ir.sku',
                []
            )
            ->joinLeft(
                ['cpei97' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                'cpei97.row_id = cpe.row_id AND cpei97.attribute_id = 97',
                []
            )
            ->joinLeft(
                ['mp' => $this->resourceConnection->getTableName('marketplace_product')],
                'mp.mage_pro_row_id = cpe.row_id',
                []
            )
            ->joinLeft(
                ['cpev73' => $this->resourceConnection->getTableName('catalog_product_entity_varchar')],
                'cpev73.row_id = cpe.row_id AND cpev73.attribute_id = 73',
                []
            )
            ->joinLeft(
                ['seller' => $this->resourceConnection->getTableName('marketplace_userdata')],
                'seller.seller_id = mp.seller_id',
                []
            )
            ->joinLeft(
                ['price' => $this->resourceConnection->getTableName('catalog_product_entity_decimal')],
                'price.row_id = cpe.row_id AND price.attribute_id = 77',
                []
            )
            ->joinLeft(
                ['special_price' => $this->resourceConnection->getTableName('catalog_product_entity_decimal')],
                'special_price.row_id = cpe.row_id AND special_price.attribute_id = 78',
                []
            )
            ->where('cpe.has_options = ?', 0);

        if (!empty($productIds)) {
            $select->where('cpe.entity_id IN (?)', $productIds);
        }

        $data = $connection->fetchAll($select);
        $result = [];
        foreach ($data as $row) {
            $result[] = [
                $row['賣場名稱'],
                $row['主sku'],
                $row['品名'],
                $row['單規/多規'],
                $row['spec_name'],
                $row['variation_sku'],
                $row['option_sku'],
                $row['庫存狀態'],
                $row['上下架狀態(若為單規且下架，可銷售庫存會為0)'],
                $row['套用Simple SKU成本設定'],
                $row['套用Simple SKU售價設定'],
                $row['抽成設置'],
                $row['抽成比率'],
                $row['成本'],
                $row['售價'],
                $row['主SKU Price'],
                $row['主SKU Special Price'],
                $row['前台可銷售庫存']
            ];
        }

        return $result;
    }
}
