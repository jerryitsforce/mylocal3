<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Controller\Adminhtml\Order\ExportProfile;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class DownLoadOrderFile extends Action implements HttpGetActionInterface
{
    private PageFactory $resultPageFactory;
    private LoggerInterface $logger;
    const ADMIN_RESOURCE = 'Magento_Sales::order_export_profile';
    private ProfileFactory $profileFactory;
    private DateTime $dateTime;
    private FileFactory $fileFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param ProfileFactory $profileFactory
     * @param DateTime $dateTime
     * @param FileFactory $fileFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context  $context,
        PageFactory     $resultPageFactory,
        ProfileFactory  $profileFactory,
        DateTime        $dateTime,
        FileFactory     $fileFactory,
        LoggerInterface $logger
    )
    {
        $this->dateTime = $dateTime;
        $this->profileFactory = $profileFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {

        $resultPage = $this->_initAction();
        try {
            $date = $this->dateTime->date('Y-m-d_H-i-s');
            $profile = $this->getProfile();
            $fileName = sprintf('orders-%s.zip', $date);
            $profile->getBatches();
            foreach ($profile->getBatches() as $batch) {
                if ($batch->getFilePath()) {
                    $paths[] = $batch->getData('file_path');
                }
            }
            if (empty($paths)) {
                throw new \Exception('No excel files found');
            }
            $zipFilePath = $this->createZipFile($fileName, $paths);
            $content = [
                'type' => 'filename',
                'value' => $zipFilePath,
                'rm' => 0
            ];
            return $this->fileFactory->create(
                $fileName,
                $content,
                DirectoryList::VAR_EXPORT,
                'application/zip'
            );

        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath(
            '*/*/index',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }

    /**
     * @param $filename
     * @param array $filePaths
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createZipFile($filename, array $filePaths)
    {
        $zipFile = BP . '/var/export/' . $filename;
        @unlink($zipFile);
        foreach ($filePaths as $filePath) {
            $excelFileName[] = pathinfo($filePath, PATHINFO_FILENAME) . '.xlsx';
        }

        $fileDirectoryPath = BP . '/var/export/';
        $command = 'cd ' . $fileDirectoryPath . ' && zip ' . $filename . ' ' . join(' ', $excelFileName);
        try {
            exec($command);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
            throw new \Magento\Framework\Exception\LocalizedException(__('Download failed'));
        }

        if (!is_file($zipFile)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Can not create zip file, please contact Administrator to support'));
        }
        return $zipFile;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getProfile()
    {
        $profile = $this->profileFactory->create()->load($this->getRequest()->getParam('id', 0));
        if (!$profile->getProfileId()) {
            throw new \Exception('Profile not found');
        }
        return $profile;
    }

    /**
     * @return mixed
     */
    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::system_convert');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Orders'), __('Orders'));
        return $resultPage;
    }

}
