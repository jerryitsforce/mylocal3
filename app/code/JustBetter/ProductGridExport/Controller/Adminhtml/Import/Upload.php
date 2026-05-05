<?php
namespace JustBetter\ProductGridExport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use JustBetter\ProductGridExport\Model\Import\InventoryImport;

class Upload extends Action
{
    protected $uploaderFactory;
    protected $filesystem;
    protected $inventoryImport;

    public function __construct(
        Action\Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        InventoryImport $inventoryImport
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->inventoryImport = $inventoryImport;
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'import_file']);
            $uploader->setAllowedExtensions(['csv', 'xls', 'xlsx']);
            $uploader->setAllowRenameFiles(true);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $result = $uploader->save($mediaDirectory->getAbsolutePath('import'));

            $filePath = $result['path'] . DIRECTORY_SEPARATOR . $result['file'];
            
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Convert Excel to CSV if needed
            if (in_array($extension, ['xls', 'xlsx'])) {
                $spreadsheet = IOFactory::load($filePath);
                $csvPath = $filePath . '.csv';
                $writer = IOFactory::createWriter($spreadsheet, 'Csv');
                $writer->save($csvPath);
                $filePath = $csvPath;
            }

            // Process import file
            $importResult = $this->inventoryImport->importInventoryFromCsv($filePath);

           if (!empty($importResult['success'])) {
                $this->messageManager->addSuccessMessage(__('Imported SKUs: %1', implode(', ', $importResult['success'])));
            }
            if (!empty($importResult['error'])) {
                $this->messageManager->addErrorMessage(__('Errors: %1', implode(', ', $importResult['error'])));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}