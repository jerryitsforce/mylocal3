<?php
declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\Ajax;

use Branch8\CustomNotification\Model\OneIdImportHistoryFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\ImportExport\Model\Export\Adapter\Csv as AdapterCsv;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller for the 'notibox/ajax/uploadoneid' URL route.
 */
class DownloadResultImportOneId extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    protected $fileFactory;
    /**
     * @var Json
     */
    private \Magento\Framework\Serialize\Serializer\Json $jsonSerializer;
    /**
     * @var OneIdImportHistoryFactory
     */
    private OneIdImportHistoryFactory $oneIdImportHistoryFactory;
    /**
     * @var AdapterCsv
     */
    private AdapterCsv $csv;
    /**
     * @var DirectoryList
     */
    private DirectoryList $directory;

    /**
     * @param Action\Context $context
     * @param FileFactory $fileFactory
     * @param Json $jsonSerializer
     * @param OneIdImportHistoryFactory $oneIdImportHistoryFactory
     * @param AdapterCsv $csv
     * @param DirectoryList $directory
     */
    public function __construct(
        Action\Context            $context,
        FileFactory               $fileFactory,
        Json                      $jsonSerializer,
        OneIdImportHistoryFactory $oneIdImportHistoryFactory,
        AdapterCsv                $csv,
        DirectoryList             $directory
    )
    {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->oneIdImportHistoryFactory = $oneIdImportHistoryFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->csv = $csv;
        $this->directory = $directory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $history = $this->oneIdImportHistoryFactory->create()->load($this->_request->getParam('id'));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$history->getId()) {
            $this->messageManager->addErrorMessage(__('There are no records to download.'));
            return $resultRedirect->setPath('notibox/notification/index');
        }
        $summary = $this->jsonSerializer->unserialize($history->getData('summary'));
        $extension = $summary['extension'] ?? 'csv';
        if (isset($summary['fileName'])) {
            $fileName = 'ValidateResult_' . $summary['fileName'] . '.' . $extension;
        } else {
            $fileName = 'ValidateResult_' . date('Ymd_His') . '.' . $extension;
        }
        $csvData = $this->jsonSerializer->unserialize($history->getData('import_data'));
        switch ($extension) {
            case 'csv':
                foreach ($csvData as $row) {
                    $this->csv->writeRow($row);
                }
                return $this->fileFactory->create(
                    $fileName,
                    $this->csv->getContents(),
                    DirectoryList::VAR_DIR,
                    'text/csv'
                );

            case 'xls':
            case 'xlsx':
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $headers = array_keys($csvData[0]);
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }
                $rowNumber = 2;
                foreach ($csvData as $row) {
                    $col = 'A';
                    foreach ($row as $value) {
                        $sheet->setCellValue($col . $rowNumber, $value);
                        $col++;
                    }
                    $rowNumber++;
                }
                $filePath = $this->directory->getPath(DirectoryList::VAR_DIR) . '/' . $fileName;
                $writer = new Xlsx($spreadsheet);
                $writer->save($filePath);
                return $this->fileFactory->create(
                    $fileName,
                    [
                        'type' => 'filename',
                        'value' => $fileName,
                        'rm' => true
                    ],
                    DirectoryList::VAR_DIR,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );

        }
    }
}
