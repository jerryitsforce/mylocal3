<?php

namespace Branch8\ProductExportRabbitMQ\Controller\Product;

use Branch8\ProductExportRabbitMQ\Model\GetSellerInvoiceNo;
use Branch8\ProductExportRabbitMQ\Model\ProfileFactory;
use Branch8\ProductExportRabbitMQ\Model\Profile;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Download Seller Order File controller.
 */
class DownloadExportFile extends Action implements CsrfAwareActionInterface
{
    protected CustomerUrl $customerUrl;
    protected CustomerFactory $customerFactory;
    protected DateTime $dateTime;
    protected ProfileFactory $profileFactory;
    protected TimezoneInterface $timezone;
    protected SellerFactory $sellerFactory;
    protected GetSellerInvoiceNo $getSellerInvoiceNo;
    protected FileFactory $fileFactory;
    protected LoggerInterface $logger;

    /**
     * @param Context $context
     * @param CustomerUrl $customerUrl
     * @param CustomerFactory $customerFactory
     * @param FileFactory $fileFactory
     * @param DateTime $dateTime
     * @param ProfileFactory $profileFactory
     * @param TimezoneInterface $timezone
     * @param SellerFactory $sellerFactory
     * @param GetSellerInvoiceNo $getSellerInvoiceNo
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context              $context,
        CustomerUrl          $customerUrl,
        CustomerFactory      $customerFactory,
        FileFactory          $fileFactory,
        DateTime             $dateTime,
        ProfileFactory       $profileFactory,
        TimezoneInterface    $timezone,
        SellerFactory        $sellerFactory,
        GetSellerInvoiceNo   $getSellerInvoiceNo,
        LoggerInterface      $logger
    ){
        parent::__construct(
            $context,
        );
        $this->customerUrl = $customerUrl;
        $this->customerFactory = $customerFactory;
        $this->dateTime = $dateTime;
        $this->profileFactory = $profileFactory;
        $this->timezone = $timezone;
        $this->sellerFactory = $sellerFactory;
        $this->getSellerInvoiceNo = $getSellerInvoiceNo;
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $profile = $this->getProfile();
            if ($profile->getProfileType() !== Profile::TYPE_SELLER
                && !(int)$profile->getUserId()
            ) {
                throw new \Exception('You do not have insufficient permissions to download this file');
            }
            $sellerId = $profile->getUserId();
            $date = $this->dateTime->date('Y-m-d_H-i-s');
            $fileName = sprintf('products-%s.zip', $date);
            $path = $profile->getFilePath();
            $invoiceCompanyNo = $this->getSellerInvoiceNo->execute($sellerId);
            if (!$invoiceCompanyNo) {
                throw new \Exception('Invoice Company not found');
            }
            $pass = $this->generatePassword($invoiceCompanyNo);
            $zipFilePath = $this->createZipFile($fileName, $path, $pass);
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
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath(
            'marketplace/order/history',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }

    /**
     * @return Profile
     * @throws \Exception
     */
    private function getProfile()
    {
        $profile = $this->profileFactory->create()->load($this->getRequest()->getParam('id', 0));
        if (!$profile->getId()) {
            throw new \Exception('Profile not found');
        }
        return $profile;
    }

    /**
     * @param $filename
     * @param $filePath
     * @param $password
     * @return string
     * @throws \Exception
     */
    private function createZipFile($filename, $filePath, $password)
    {

        //echo system('cd '.$fileDirectoryPath.' && zip -P '.$password.' '.$filename.' '.$excelFileName);
        $zipFile = BP . '/var/export/' . $filename;
        @unlink($zipFile);
        $excelFileName = pathinfo($filePath, PATHINFO_FILENAME) . '.xls';
        $fileDirectoryPath = BP . '/var/export/';
        $command = 'cd ' . $fileDirectoryPath . ' && zip -P ' . $password . ' ' . $filename . ' ' . $excelFileName;
        try {
            exec($command);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_ProductExportRabbitMQ', 'exceptionlog')){
                $this->logger->critical($e->getMessage());
            }
            throw new LocalizedException(__('Download failed'));
        }

        if (!is_file($zipFile)) {
            throw new LocalizedException(__('Can not create zip file, please contact Administrator to support'));
        }
        return $zipFile;
    }

    /**
     * @param $invoiceCompanyNo
     * @return string
     */
    protected function generatePassword($invoiceCompanyNo)
    {
        $prefix = 'oD';
        $monthDate = $this->timezone->date()->format('md');
        return $prefix . $monthDate . substr(trim($invoiceCompanyNo), -4);
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
