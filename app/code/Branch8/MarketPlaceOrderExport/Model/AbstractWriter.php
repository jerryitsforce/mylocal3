<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\OrderRepository;
use Magento\Setup\Module\Di\Code\Reader\InvalidFileException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractWriter
{
    const HEADER_COLOR = '000000';

    const ROW_COLOR = 'ffffff';
    const BACKGROUND_COLOR = '595959';

    protected $spreedSheet = null;

    protected $filePath;

    protected Filesystem $fileSystem;

    protected mixed $fileName;

    protected $current = 1;
    /**
     * @var RecordsProvider
     */
    protected mixed $recordProvider = null;

    protected $records = [];

    protected mixed $ids;
    private array $headers = [];

    protected $totalIndexColums = [];

    protected $totalIndexValues = [];

    protected $totalRecord = [];

    protected $header = [];

    public $isStream = false;
    private ScopeConfigInterface $scopeConfig;

    private LoggerInterface $logger;

    /**
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Filesystem           $filesystem,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger = null
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->fileSystem = $filesystem;
        if (!$logger) {
            $this->logger = ObjectManager::getInstance()->get(LoggerInterface::class);
        }
        //$this->initCache();
    }

    /**
     * @param $records
     * @return $this
     */
    public function setRecords($records)
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @return $this
     */
    public function initCache()
    {
        $enable = (bool)$this->scopeConfig->getValue('order_export/optimize/use_redis_to_cache_excel_cells');
        $host = $this->scopeConfig->getValue('order_export/optimize/cache_redis_host');
        $port = $this->scopeConfig->getValue('order_export/optimize/cache_redis_port');
        if ($enable && $host && $port) {
            try {
                $client = new \Redis();
                $client->connect($host, $port);
                $pool = new \Cache\Adapter\Redis\RedisCachePool($client);
                $simpleCache = new \Cache\Bridge\SimpleCache\SimpleCacheBridge($pool);
                \PhpOffice\PhpSpreadsheet\Settings::setCache($simpleCache);
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }

        }
        return $this;
    }

    /**
     * @param $headers
     * @return $this
     */

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return RecordsProvider|mixed
     */
    protected function getRecordProvider()
    {
        if ($this->recordProvider === null) {
            $this->recordProvider = $this->recordProviderFactory->create();
        }
        return $this->recordProvider;
    }

    /**
     * @param $recordProvider
     * @return $this
     */
    public function setRecordProvider($recordProvider)
    {
        $this->recordProvider = $recordProvider;
        return $this;
    }

    /**
     * @param $fileName
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        $path = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_EXPORT)
            ->getAbsolutePath();
        $this->filePath = $path . $this->fileName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Writes a record (row) to the worksheet.
     * @param $record
     * @param $isHeader
     * @param bool $autoSize Whether to automatically adjust the column width.
     * @return $this
     */
    public function writeRecord($record, $isHeader = false, $autoSize = true)
    {
        $worksheet = $this->getSpreedSheet()->getActiveSheet();
        $columnSettings = $this->recordProvider->getColumnSettings();
        $columns = array_keys($this->recordProvider->getColumns());
        if ($isHeader) {
            $this->headers = array_map(function ($column) {
                return is_object($column) ? $column->render() : $column;
            }, $record);
        }
        if (!$isHeader && $this->headers) {
            $data = array_combine($this->headers, $record);
        } else {
            $data = $this->headers;
        }
        $columnIndex = 0;
        foreach ($data as $key => $value) {
            if (!$isHeader && isset($this->totalIndexColums[$key])) {

                if (is_numeric($value)) {
                    isset($this->totalIndexValues[$key]) ? $this->totalIndexValues[$key] += $value : $this->totalIndexValues[$key] = $value;
                }
            }
            $align = 'left';
            if (isset($columns[$columnIndex]) &&
                isset($columnSettings[$columns[$columnIndex]]['setHorizontal'])
            ) {
                $align = $columnSettings[$columns[$columnIndex]]['setHorizontal'];
            }
            $columnLetter = $this->getExcelColumnLetter($columnIndex + 1);
            $cellAddress = $columnLetter . $this->current;
            $worksheet->setCellValue($cellAddress, $value);
            $worksheet->getColumnDimension($columnLetter)->setAutoSize($autoSize);
            $worksheet->getStyle($cellAddress)->getAlignment()->setHorizontal($align);
            if ($isHeader) {
                $worksheet->getStyle($cellAddress)->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_COLOR)
                );
                $worksheet->getStyle($cellAddress)->getBorders()->getAllBorders()->setBorderStyle(
                    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                );
                $worksheet->getStyle($cellAddress)->getAlignment()->setHorizontal('center');
            }
            $columnIndex++;
        }
        $this->current += 1;
        return $this;
    }

    /**
     * @param $columnIndex
     * @return string
     */
    protected function getExcelColumnLetter($columnIndex)
    {
        $letter = '';
        while ($columnIndex > 0) {
            $mod = ($columnIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $columnIndex = (int)(($columnIndex - $mod) / 26);
        }
        return $letter;
    }

    public function setOrderIds($ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @return AbstractWriter
     */
    abstract function writeHeader();


    /**
     * @param $row
     * @return array
     * @throws LocalizedException
     */
    protected function buildRecord($row)
    {
        $record = [];
        foreach ($this->recordProvider->getColumns() as $column => $type) {
            if (is_string($type)) {
                $record[$column] = $row[$column] ?? '';
            } else if ($type instanceof ColumnInterface) {
                $record[$column] = $type->processColumnData($row);
            } else {
                throw new LocalizedException(__('Invalid column Interface'));
            }
        }
        return $record;
    }

    /**
     * @param $row
     * @return mixed
     */
    public function setNulLColumn(&$row)
    {
        $nullColumn = [
            //'total_item_invoiced',
            'price',
            'product_id',
            'product_sku',
            'option_title',
            'option_name',
            //'cost',
            'discount_amount',
            'order_cancellation_time',
            'order_reason_for_cancellation',
            'agree_on_return_time',
            'ReturnResultConfirmationTime',
            'return_result',
            'schedule_change_special_price',
            'scheduled_changes_of_special_price_start_date',
            'scheduled_changes_of_special_price_end_date',
            'applied_rule_names'
        ];
        foreach ($row as $key => $value) {
            if (in_array($key, $nullColumn)) {
                $row[$key] = '';
            }
        }
        return $row;
    }

    /**
     * @param array $ids
     * @return Writer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public abstract function writeRecords();

    /**
     * @param $streamHttp
     * @return void
     */
    public function save($streamHttp = false)
    {
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter(
            $this->getSpreedSheet(), "Xlsx");
        if ($streamHttp) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="orders.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save("php://output");
            exit();
        }
        $writer->save($this->filePath);
        if (!$this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_EXPORT)
            ->isFile($this->filePath)) {
            throw new InvalidFileException(__('Invalid file % 1', $this->filePath));
        }
        return $this->filePath;
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreedSheet()
    {
        if ($this->spreedSheet === null) {
            $this->spreedSheet = new Spreadsheet();
        }
        return $this->spreedSheet;
    }

    /**
     * @param Spreadsheet $spreedSheet
     * @return $this
     */
    public function setSpreedSheet(Spreadsheet $spreedSheet)
    {
        $this->spreedSheet = $spreedSheet;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetCurrent()
    {
        $this->current = 1;
        return $this;
    }

    public function reset()
    {
        $this->current = 1;
        $this->totalIndexValues = [];
        $this->totalIndexColums = [];
        $this->headers = [];
        $this->fileName = '';
        $this->spreedSheet = null;
        return $this;
    }

    /**
     * @param $totalIndexColums
     * @return $this
     */
    public function setTotalIndexColumns($totalIndexColums)
    {
        $this->totalIndexColums = $totalIndexColums;
        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getTotalIndexColumns()
    {
        return $this->totalIndexColums;
    }

    public function getTotalIndexValues()
    {
        return $this->totalIndexValues;
    }

    /**
     * @param $totalRecord
     * @return $this
     */
    public function setTotalRecord($totalRecord)
    {
        $this->totalRecord = $totalRecord;
        return $this;
    }

    /**
     * Writes a total row to the worksheet.
     * @param bool $autoSize Whether to automatically adjust the column width.
     * @return AbstractWriter
     */
    public function writeTotalRecord($autoSize = true)
    {
        $worksheet = $this->getSpreedSheet()->getActiveSheet();
        $align = 'left';
        $columnIndex = 0;
        $finalTotalRecord = array_merge($this->totalRecord, $this->totalIndexValues);
        foreach ($finalTotalRecord as $key => $value) {
            $columnLetter = $this->getExcelColumnLetter($columnIndex + 1);
            $cellAddress = $columnLetter . $this->current;
            $worksheet->setCellValue($cellAddress, $value);
            $worksheet->getStyle($cellAddress)->getAlignment()->setWrapText(true);
            $worksheet->getColumnDimension($columnLetter)->setAutoSize($autoSize);
            $worksheet->getStyle($cellAddress)->getAlignment()->setHorizontal($align);
            $worksheet->getStyle($cellAddress)->getFont()->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_COLOR)
            );

            $borders = $worksheet->getStyle($cellAddress)->getBorders();
            $borders->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $borders->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $borders->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $borders->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

            $worksheet->getStyle($cellAddress)
                ->getAlignment()
                ->setHorizontal($align);
            $columnIndex++;
        }
        $this->current++;
        return $this;
    }

    /**
     * @return $this
     */
    public function setPrint()
    {
        $this->getSpreedSheet()->getActiveSheet()->getPageSetup()->setOrientation(
            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $this->getSpreedSheet()->getActiveSheet()->getPageSetup()->setPaperSize(
            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $this->getSpreedSheet()->getActiveSheet()->getPageSetup()->setFitToPage(true);
        $this->getSpreedSheet()->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $this->getSpreedSheet()->getActiveSheet()->getPageSetup()->setFitToWidth(1);

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsstream($value)
    {
        $this->isStream = $value;
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function getIstream()
    {
        return $this->isStream;
    }
}
