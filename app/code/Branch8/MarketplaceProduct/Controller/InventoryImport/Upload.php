<?php
namespace Branch8\MarketplaceProduct\Controller\InventoryImport;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Branch8\MarketplaceProduct\Model\Import\InventoryImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Customer\Model\Session;

class Upload extends \Magento\Framework\App\Action\Action
{
    protected $uploaderFactory;
    protected $filesystem;
    protected $inventoryImport;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var HelperData
     */
    protected $helper;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        InventoryImport $inventoryImport,
        Session $customerSession,
        HelperData $helper,
        CustomerUrl $customerUrl = null
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->inventoryImport = $inventoryImport;
        $this->_customerSession = $customerSession;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerUrl::class);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            // proceed
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
        } else {
            $this->messageManager->addErrorMessage(__('You are not authorized to access this page.'));
            $resultRedirect->setUrl('marketplace/account/becomeseller');
            return $resultRedirect;
        }
        
        return $resultRedirect;
    }
}
