<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Controller\Order;

use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\GetSellerInvoiceNo;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Profiler;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\CustomerFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;
use  Webkul\Marketplace\Model\SellerFactory;

/**
 * Download Seller Order File controller.
 */
class DownloadOrderFile extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    private $customerUrl;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var DateTime
     */
    private DateTime $dateTime;
    private Writer $writter;
    private ProfileFactory $profileFactory;
    private TimezoneInterface $timezone;
    private SellerFactory $sellerFactory;
    private GetSellerInvoiceNo $getSellerInvoiceNo;
    private FileFactory $fileFactory;
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param HelperData $helper
     * @param CustomerUrl $customerUrl
     * @param CustomerFactory $customerFactory
     * @param MpOrdersCollection $mpOrdersCollection
     * @param InvoiceCollection $invoiceCollection
     * @param DateTime $date
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param Writer $writer
     * @param ProfileFactory $profileFactory
     * @param TimezoneInterface $timezone
     * @param SellerFactory $sellerFactory
     * @param GetSellerInvoiceNo $getSellerInvoiceNo
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context              $context,
        Filter               $filter,
        Session              $customerSession,
        CollectionFactory    $orderCollectionFactory,
        HelperData           $helper,
        CustomerUrl          $customerUrl,
        CustomerFactory      $customerFactory,
        MpOrdersCollection   $mpOrdersCollection,
        InvoiceCollection    $invoiceCollection,
        DateTime             $date,
        FileFactory          $fileFactory,
        Filesystem           $filesystem,
        ScopeConfigInterface $scopeConfig,
        DateTime             $dateTime,
        Writer               $writer,
        ProfileFactory       $profileFactory,
        TimezoneInterface    $timezone,
        SellerFactory        $sellerFactory,
        GetSellerInvoiceNo   $getSellerInvoiceNo,
        LoggerInterface      $logger
    )
    {

        parent::__construct(
            $context,
        );
        $this->customerUrl = $customerUrl;
        $this->customerFactory = $customerFactory;
        $this->dateTime = $dateTime;
        $this->writter = $writer;
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
            $fileName = sprintf('orders-%s.zip', $date);
            $path = $profile->getFilePath();
            $profile->getBatches();
            foreach ($profile->getBatches() as $batch) {
                if ($batch->getFilePath()) {
                    $paths[] = $batch->getData('file_path');
                }
            }
            $invoiceCompanyNo = $this->getSellerInvoiceNo->execute($sellerId);
            if (!$invoiceCompanyNo) {
                throw new \Exception('Invoice Company not found');
            }
            if(empty($paths)) {
                throw new \Exception('No excel files found');
            }
            $pass = $this->generatePassword($invoiceCompanyNo);
            $zipFilePath = $this->createZipFile($fileName, $paths, $pass);
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
        if (!$profile->getProfileId()) {
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
    private function createZipFile($filename, array $filePaths, $password)
    {

        //echo system('cd '.$fileDirectoryPath.' && zip -P '.$password.' '.$filename.' '.$excelFileName);
        $zipFile = BP . '/var/export/' . $filename;
        @unlink($zipFile);
        foreach ($filePaths as $filePath) {
            $excelFileName[] = pathinfo($filePath, PATHINFO_FILENAME) . '.xlsx';
        }

        $fileDirectoryPath = BP . '/var/export/';
        $command = 'cd ' . $fileDirectoryPath . ' && zip -P ' . $password . ' ' . $filename . ' ' . join(' ', $excelFileName);
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
