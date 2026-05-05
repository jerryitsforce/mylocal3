<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue;

use AllowDynamicProperties;
use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExport\Model\Writer2;
use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileInterface;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Batch;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Mail;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\App\Emulation;
use function Aws\filter;

ini_set("memory_limit", -1);

#[AllowDynamicProperties] class Consumer
{
    private LoggerInterface $logger;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory;
    private $startTime = 0;
    private $startMemory = 0;
    private Writer $writer;
    private \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $resource;
    private Mail $mail;
    private Config $config;
    private $state;
    private $batchFactory;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory $profileFactory
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $profileResource
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\BatchFactory $batchFactory
     * @param Writer $writer
     * @param Writer2 $writer2
     * @param \Magento\Framework\App\State $state
     * @param Emulation $appEmulation
     * @param Mail $mail
     */
    public function __construct(
        Config                                                              $config,
        LoggerInterface                                                     $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime                         $date,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory        $profileFactory,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile $profileResource,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\BatchFactory          $batchFactory,
        Writer                                                              $writer,
        Writer2                                                             $writer2,
        \Magento\Framework\App\State                                        $state,
        Emulation                                                           $appEmulation,
        Mail                                                                $mail
    )
    {
        $this->config = $config;
        $this->mail = $mail;
        $this->profileFactory = $profileFactory;
        $this->date = $date;
        $this->logger = $logger;
        $this->writer = $writer;
        $this->resource = $profileResource;
        $this->state = $state;
        $this->writer2 = $writer2;
        $this->batchFactory = $batchFactory;
        $this->appEmulation = $appEmulation;
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
        /**
         * @var $profile ProfileInterface
         * @var $batch Batch
         */
        $this->beginProcess();
        $this->writeLog('JSON REQUEST:' . $jsonData);
        $this->writeLog('AREA:' . $this->state->getAreaCode());
        $this->writeLog('MEMORY LIMIT:' . ini_get('memory_limit'));
        $this->writeLog('MAX_EXECUTION_TIME:' . ini_get('max_execution_time'));

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
        $batchId = $data['batchId'];
        $queueId = $data['queueId'];
        $profile = $this->profileFactory->create()->load($profileId);
        $batch = $this->batchFactory->create()->load($batchId);
        $pid = getmypid();
        $batch->setData('queuid', $queueId);
        $batch->setData('phpid', $pid);
        //$profile->setPhpId((string)$pid);
        if (!$profile || !$profile->getProfileId()) {
            $this->writeLog('Profile Not exist: ' . $profileId);
            $this->endProcess();
            return;
        }
        if (!$batch->getId()) {
            $this->writeLog('Batch Not exist: ' . $batch->getId());
            $this->endProcess();
            return;
        }
        try {
            $this->appEmulation->startEnvironmentEmulation(0);
            if ($profile->getStatus() === Profile::STATUS_PENDING) {
                $profile->process();
                $this->resource->save($profile);
            }
            $batch->process();
            $batch->setDataChanges(true);
            $batch->getResource()->save($batch);
            $filePath = $this->exportOrders($batch);
            if (empty($filePath)) {
                $this->endProcess();
                return;
            }
            $batch->setFilePath($filePath)->complete();
            $batch->getResource()->save($batch);
            $batches = $profile->getBatches();
            $this->writeLog('COUNT BATCHES:' . count($batches));
            $this->writeLog('BATCH STATUS :' . $batch->getStatus());
            if (count($batches) === 1) {
                // only 1 batch and it done
                $profile->complete()->setHasDataChanges(true);
                $this->resource->save($profile);
                // $this->mail->send($profile);
            }
            $this->writeLog('STATUS":' . $profile->getStatus());
            //$this->mail->send($profile);// mail send by cron
            $this->endProcess();
        } catch (\Exception $e) {
            $this->writeLog('EXCEPTION:' . $e->getMessage());
        } finally {
            // Stop environment emulation and revert to the previous environment
            $this->appEmulation->stopEnvironmentEmulation();
        }
        gc_collect_cycles();
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
     * @param Batch $profile
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */

    private function exportOrders(Batch $batch)
    {
        $useOptimize = $this->config->getOptimize();
        $orderIds = $batch->getOrderIds();
        $this->writeLog('TotalOrderIds: ' . count($orderIds));

        if (empty($orderIds)) {
            return '';
        }
        $date = $this->date->date('Y-m-d_H-i-s');
        $fileNameUniqueId = sprintf('orders-%s-%s.xlsx', $date, uniqid());
        if ($useOptimize) {
            $this->writer2->initCache()->setFileName($fileNameUniqueId)
                ->setOrderIds(array_unique($orderIds))
                ->writeHeader()
                ->writeRecords();
            $this->writer2->save();
            return $this->writer2->getFilePath();
        } else {
            $this->writer->setFileName($fileNameUniqueId)
                ->setOrderIds(array_unique($orderIds))
                ->writeHeader()
                ->writeRecords();
            $this->writer->save();
            return $this->writer->getFilePath();
        }
    }

    protected function writeLog(string|\Stringable $message, array $context = []){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'order_export')){
            $this->logger->info($message, $context);
        }
    }
}
