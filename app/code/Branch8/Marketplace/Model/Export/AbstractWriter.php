<?php

namespace Branch8\Marketplace\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\OrderRepository;
use Magento\Setup\Module\Di\Code\Reader\InvalidFileException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class AbstractWriter
{
    const HEADER_COLOR = 'ffffff';
    const BACKGROUND_COLOR = '595959';
    const RIGHT_ALIGN = [];

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

    protected mixed $data;

    private mixed $headers;

    protected $totalIndexColums = [];

    protected $totalIndexValues = [];

    protected $totalRecord=[];
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    )
    {
        $this->fileSystem = $filesystem;
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
     * @param $record
     * @param bool $isHeader
     * @return $this
     */
    public function writeRecord($record, bool $isHeader = false, $style = [])
    {
        $worksheet = $this->getSpreedSheet()->getActiveSheet();
        $columnIndex = 0;
        foreach ($record as $key => $value) {
            $align = 'left';
            if (in_array($columnIndex, self::RIGHT_ALIGN)) {
                $align = 'right';
            }
            $columnLetter = $this->getExcelColumnLetter($columnIndex + 1);
            $cellAddress = $columnLetter . $this->current;
            $worksheet->setCellValue($cellAddress, $value);
            $worksheet->getStyle($cellAddress)->getAlignment()->setWrapText(true);
            $worksheet->getColumnDimension(
                $columnLetter)
                ->setAutoSize(true);
            $worksheet->getStyle($cellAddress)->getAlignment()->setHorizontal($align);
            if (!empty($style)) {
                $worksheet->getStyle($cellAddress)
                    ->getFont()
                    ->getColor()
                    ->setARGB('FF0000');
            }
            if ($isHeader) {
                $worksheet->getStyle($cellAddress)->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(self::HEADER_COLOR)
                );
                $worksheet->getStyle($cellAddress)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB(self::BACKGROUND_COLOR);
                $worksheet->getStyle($cellAddress)
                    ->getAlignment()
                    ->setHorizontal('center');
            }
            $columnIndex += 1;
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

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return AbstractWriter
     */
    abstract function writeHeader();



    /**
     * @param array $ids
     * @return Writer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public abstract function writeRecords();

    /**
     * @return mixed
     */
    public function save()
    {
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter(
            $this->getSpreedSheet(), "Xlsx");
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


}
