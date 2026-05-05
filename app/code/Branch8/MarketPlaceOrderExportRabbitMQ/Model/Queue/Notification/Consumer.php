<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Notification;

use Branch8\Marketplace\Model\Export\Writer;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\NotificationConfig;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\NotificationMail;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\ProfileNotification;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\ProfileNotificationFactory;
use Branch8\MarketplaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification as ProfileNotificationResource;
use Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;

class Consumer
{
    private LoggerInterface $logger;
    private DateTime $date;
    private ProfileNotificationFactory $profileFactory;
    private $startTime = 0;
    private $startMemory = 0;
    private ProfileNotificationResource $resource;
    private NotificationMail $mail;
    private NotificationConfig $config;
    private State $state;
    protected Emulation $appEmulation;
    protected Filesystem $filesystem;

    protected OrderDailyNotificationHelper $orderDailyNotificationHelper;

    protected Writer $writer;

    /**
     * @param NotificationConfig $config
     * @param LoggerInterface $logger
     * @param DateTime $date
     * @param ProfileNotificationFactory $profileFactory
     * @param ProfileNotificationResource $profileResource
     * @param Filesystem $filesystem
     * @param State $state
     * @param Emulation $appEmulation
     * @param NotificationMail $mail
     * @param OrderDailyNotificationHelper $orderDailyNotificationHelper
     * @param Writer $writer
     */
    public function __construct(
        NotificationConfig $config,
        LoggerInterface $logger,
        DateTime $date,
        ProfileNotificationFactory  $profileFactory,
        ProfileNotificationResource $profileResource,
        Filesystem $filesystem,
        State $state,
        Emulation $appEmulation,
        NotificationMail $mail,
        OrderDailyNotificationHelper $orderDailyNotificationHelper,
        Writer $writer
    ){
        $this->config = $config;
        $this->profileFactory = $profileFactory;
        $this->date = $date;
        $this->logger = $logger;
        $this->resource = $profileResource;
        $this->filesystem = $filesystem;
        $this->state = $state;
        $this->appEmulation = $appEmulation;
        $this->mail = $mail;
        $this->orderDailyNotificationHelper = $orderDailyNotificationHelper;
        $this->writer = $writer;
    }

    private function beginProcess()
    {
        $this->writeLog('=======================BEGIN EXECUTE EXPORT"=====================');
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_peak_usage();
    }

    /**
     * @param $jsonData
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($jsonData)
    {
        $this->beginProcess();
        $this->writeLog('JSON REQUEST:' . $jsonData);
        $this->writeLog('AREA:' . $this->state->getAreaCode());

        if (!$this->config->enable()) {
            $this->writeLog('CONFIG NOT ENABLE:');
            return;
        }
        try {
            $data = @json_decode($jsonData, TRUE);
            if (empty($data) || empty($data['profile_id'])) {
                $this->endProcess();
            }
        } catch (\Exception $e) {
            $this->endProcess();
            return;
        }
        $profileId = $data['profile_id'];
        $this->writeLog('profileId: ' . $data['profile_id']);
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile || !$profile->getId()) {
            $this->writeLog('Profile Not exist: ' . $profileId);
            $this->endProcess();
            return;
        }
        $profile->setExecuteAt($this->date->gmtDate('Y-m-d H:i:s'));
        if ($profile->getStoreId()) {
            $this->appEmulation->startEnvironmentEmulation(
                $profile->getStoreId(),
                Area::AREA_FRONTEND, true
            );
        }
        $filePath = $this->exportOrders($profile);
        $this->appEmulation->stopEnvironmentEmulation();
        if (empty($filePath)) {
            $this->writeLog('Empty FilePath: ' . $profileId);
            $this->endProcess();
            return;
        }
        $this->writeLog('File Path: ' . $filePath);
        $profile->setFilePath($filePath);
        $this->resource->save($profile);
        $this->mail->send($profile);
        $this->endProcess();
    }

    /**
     * @return $this
     */
    private function endProcess()
    {
        $end = microtime(true);
        $endMemory = memory_get_peak_usage();
        $executionTime = $end - $this->startTime;
        $memoryUsage = $endMemory - $this->startMemory;
        $this->writeLog('Execution time: ' . $executionTime . 'ms');
        $this->writeLog('Memory usage: ' . $memoryUsage);
        $this->writeLog('=======================END EXECUTE EXPORT====================');
        return $this;
    }

    /**
     * @param ProfileNotification $profile
     * @return string
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function exportOrders(ProfileNotification $profile): string
    {
        $orderData = $this->orderDailyNotificationHelper->getOrderDataForExcel($profile->getSellerId());
        if (empty($orderData)) {
            return '';
        } else {
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $directory->create('export');
            list($fileName, $zipFileName) = $this->orderDailyNotificationHelper->getFileName();
            $this->writer->setFileName($fileName)
                ->setData($orderData)
                ->writeHeader()
                ->writeRecords()
                ->save();

            $file = $this->writer->getFilePath();
        }
        return $file;
    }

    protected function writeLog(string|\Stringable $message, array $context = []){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'order_notification_download')){
            $this->logger->info($message, $context);
        }
    }
}
