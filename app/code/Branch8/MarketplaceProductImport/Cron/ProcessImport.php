<?php
/**
* @Branch8
*/

declare(strict_types=1);

namespace Branch8\MarketplaceProductImport\Cron;

use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Exception\FileSystemException;
use Psr\Log\LoggerInterface;

class ProcessImport
{
    /**
     * @var Sftp
     */
    private $fstp;
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    private $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $driverFile;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;
    /**
     * @var false
     */
    private $sftpConnected;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Asiapay\Pdcptb\Model\WriteOrderToDat
     */
    private $orderToDat;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encrypt;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Sftp $fstp,
        LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Encryption\EncryptorInterface $encrypt
    ) {
        $this->fstp = $fstp;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->logger = $logger;
        $this->date = $date;
        $this->sftpConnected = false;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->timezone = $timezone;
        $this->encrypt = $encrypt;
    }

    protected function isSftpEnabled()
    {
        return $this->scopeConfig->isSetFlag('marketplace/credential/enabled');
    }

    protected function getHost()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/host');
    }

    protected function getUsername()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/username');
    }

    protected function getPassword()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/password');
    }

    protected function getRemotePath()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/remote_path');
    }

    protected function getRemotePathMedia()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/remote_path');
    }

    protected function getArchivePassword()
    {
        return $this->scopeConfig->getValue('marketplace/import_ftp_credential/archive_password');
    }

    /**
     * @throws \Exception
     */
    private function getFstp()
    {
        if(!$this->sftpConnected){
            $credential = [
                'host' => $this->getHost(),
                'username' => $this->getUsername(),
                'password' => $this->getPassword(),
                'port' => 22,
                'passive' => true
            ];
            try{
                $this->fstp->open($credential);
                $this->fstp->cd($this->getRemotePath());
                $this->sftpConnected = true;
            }catch (\Exception $e){
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceProductImport', 'exceptionlog')){
                    $this->logger->critical($e);
                }
            }
        }
        return $this->fstp;
    }

    /**
     * Export order data to sftp
     * @throws FileSystemException
     */
    public function execute()
    {
        $hour = $this->getCurrentHour();
        if ($hour < 12) {
            $currentDay = $this->timezone->date()->format('Y-m-d 04:00:00');
            $currentDayFilter = $this->convertToTz($currentDay, $this->timezone->getDefaultTimezone(), $this->timezone->getConfigTimezone());
            $lastDay = date('Y-m-d 16:00:00', strtotime("-1 days", strtotime($currentDay)));
            $lastDay = $this->convertToTz($lastDay, $this->timezone->getDefaultTimezone(), $this->timezone->getConfigTimezone());
        } else {
            $currentDay = $this->timezone->date()->format('Y-m-d 16:00:00');
            $currentDayFilter = $this->convertToTz($currentDay, $this->timezone->getDefaultTimezone(), $this->timezone->getConfigTimezone());
            $lastDay = $this->timezone->date()->format('Y-m-d 04:00:00');
            $lastDay = $this->convertToTz($lastDay, $this->timezone->getDefaultTimezone(), $this->timezone->getConfigTimezone());
        }
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter('created_at',
                ['gteq' => $lastDay]
            )->addAttributeToFilter('created_at',
                ['lteq' => $currentDayFilter]
            )->addAttributeToFilter('state',
                ['in' => ['complete', 'processing']]
            );
        $i = 0;
        foreach ($orders->getItems() as $order) {
            $_payment = $order->getPayment();
            if ($_payment->getMethod() == \Branch8\AsiaPayInstallment\Helper\Data::PAYMENT_METHOD_CODE) {
                $additionalInfo = $_payment->getAdditionalInformation();
                $paymentProvider = '';
                $panLast4 = '';
                $holder = '';
                $payRef = '';
                $authId = '';
                $merchantId = '';
                $amt = '';
                if (isset($additionalInfo[\Branch8\AsiaPayInstallment\Helper\Data::INSTALLMENT_PROVIDER])
                && $additionalInfo[\Branch8\AsiaPayInstallment\Helper\Data::INSTALLMENT_PROVIDER]) {
                    $paymentProvider = $additionalInfo[\Branch8\AsiaPayInstallment\Helper\Data::INSTALLMENT_PROVIDER];
                }
                if (isset($additionalInfo['instalmentHidePanLast4']) && $additionalInfo['instalmentHidePanLast4']) {
                    $panLast4 = $this->encrypt->decrypt($additionalInfo['instalmentHidePanLast4']);
                }
                if (isset($additionalInfo['instalmentHideHolder']) && $additionalInfo['instalmentHideHolder']) {
                    $holder = $this->encrypt->decrypt($additionalInfo['instalmentHideHolder']);
                    if ($holder) {
                        $holderArray = explode(' ', $holder, 2);
                        if (isset($holderArray[1])) $holderArray[1] = preg_replace("/[\S]/", "X", $holderArray[1]);
                        $holder = implode(' ', $holderArray);
                    }
                }
                if (isset($additionalInfo['instalmentHideRefNo']) && $additionalInfo['instalmentHideRefNo']) {
                    $payRef = $additionalInfo['instalmentHideRefNo'];
                }
                if (isset($additionalInfo['instalmentHideAuthId']) && $additionalInfo['instalmentHideAuthId']) {
                    $authId = $this->encrypt->decrypt($additionalInfo['instalmentHideAuthId']);
                }
                if (isset($additionalInfo['instalmentHideMerchantId']) && $additionalInfo['instalmentHideMerchantId']) {
                    $merchantId = $additionalInfo['instalmentHideMerchantId'];
                }
                if (isset($additionalInfo['instalmentHideAmount']) && $additionalInfo['instalmentHideAmount']) {
                    $amt = $additionalInfo['instalmentHideAmount'];
                }
                $orderInfo = [
                    'InvoiceNo' => $order->getIncrementId(),
                    'TransactionTime' => $order->getCreatedAt(),
                    'InstallmentProvider' => $paymentProvider,
                    'PanLast4' => $panLast4,
                    'HolderName' => $holder,
                    'RefNo' => $payRef,
                    'AuthId' => $authId,
                    'MerchantId' => $merchantId,
                    'Amount' => $amt
                ];
                $this->orderToDat->write(implode('|', $orderInfo));
                $i++;
            }
        }
        if ($i > 0) $this->orderToDat->write(sprintf("%06d", $i).'|TRAILER');
        $paths = $this->getAllFiles();
        if($paths){
            if (!class_exists('\ZipArchive')) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceProductImport', 'exceptionlog')){
                    $this->logger->error('ZipArchive class not found');
                }
            }else{
                $exportDir = $this->directoryList->getPath('var') . '/' . WriteOrderToDat::EXPORT_FOLDER;
                foreach($paths as $path){
                    $zip = new \ZipArchive();
                    $fileName = str_replace('%Date%', $this->date->date('YmdHis'), WriteOrderToDat::ORDER_FILE);
                    $zipName = $exportDir.'/' . $fileName . '.zip';
                    $zip->open($zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    $zip->addFile($path, $fileName .'.dat');
                    $zip->setEncryptionName($fileName .'.dat', \ZipArchive::EM_AES_256, $this->getArchivePassword());
                    $zip->close();
                    if($this->isSftpEnabled()){
                        if($this->getFstp()->write($fileName . '.zip', $zipName)){
                            $this->driverFile->deleteFile($path);
                            $this->driverFile->deleteFile($zipName);
                        }
                    }

                }
            }
        }
    }

    /**
     * @param $path
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getAllFiles() {
        $paths = [];
        $path = \Asiapay\Pdcptb\Model\WriteOrderToDat::EXPORT_FOLDER;
        try {
            //get the base folder path you want to scan (replace var with pub / media or any other core folder)
            $path = $this->directoryList->getPath('var') . '/' .$path;
            //read just that single directory
            $paths =  $this->driverFile->readDirectory($path);
            //read all folders
            $paths =  $this->driverFile->readDirectoryRecursively($path);
        } catch (FileSystemException $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceProductImport', 'exceptionlog')){
                $this->logger->error($e->getMessage());
            }
        }

        return $paths;
    }

    protected function convertToTz($dateTime="", $toTz='', $fromTz='')
    {
        // timezone by php friendly values
        $date = new \DateTime($dateTime, new \DateTimeZone($fromTz));
        $date->setTimezone(new \DateTimeZone($toTz));
        $dateTime = $date->format('Y-m-d H:i:s');
        return $dateTime;
    }

    public function getCurrentHour()
    {
        return $this->timezone->date()->format('H');
    }
}
