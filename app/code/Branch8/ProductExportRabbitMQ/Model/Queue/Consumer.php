<?php
declare(strict_types=1);

namespace Branch8\ProductExportRabbitMQ\Model\Queue;

use Branch8\ProductExportRabbitMQ\Model\Config;
use Branch8\ProductExportRabbitMQ\Model\Mail;
use Branch8\ProductExportRabbitMQ\Model\Profile;
use Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile as ProfileResource;
use Branch8\ProductExportRabbitMQ\Model\ProfileFactory;
use JustBetter\ProductGridExport\Model\Export\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;

class Consumer
{
    private LoggerInterface $logger;
    private DateTime $date;
    private ProfileFactory $profileFactory;
    private $startTime = 0;
    private $startMemory = 0;
    private ProfileResource $resource;
    private Mail $mail;
    private Config $config;
    private State $state;
    protected Emulation $appEmulation;
    protected Product $converter;
    protected Filesystem $filesystem;
    protected Registry $coreRegistry;

    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param DateTime $date
     * @param ProfileFactory $profileFactory
     * @param ProfileResource $profileResource
     * @param Product $converter
     * @param Filesystem $filesystem
     * @param State $state
     * @param Emulation $appEmulation
     * @param Mail $mail
     * @param Registry $coreRegistry
     */
    public function __construct(
        Config          $config,
        LoggerInterface $logger,
        DateTime        $date,
        ProfileFactory  $profileFactory,
        ProfileResource $profileResource,
        Product         $converter,
        Filesystem      $filesystem,
        State           $state,
        Emulation       $appEmulation,
        Mail            $mail,
        Registry        $coreRegistry
    ){
        $this->config = $config;
        $this->profileFactory = $profileFactory;
        $this->date = $date;
        $this->logger = $logger;
        $this->resource = $profileResource;
        $this->converter = $converter;
        $this->filesystem = $filesystem;
        $this->state = $state;
        $this->appEmulation = $appEmulation;
        $this->mail = $mail;
        $this->coreRegistry = $coreRegistry;
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
        $this->coreRegistry->register('amasty_ignore_product_filter', true);
        $profileId = $data['profile_id'];
        $isAdmin =  $data['is_admin'] ?? false;
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
        $filePath = $this->exportProducts($profile, $isAdmin);
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
     * @param Profile $profile
     * @param bool $isAdmin
     * @return string
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function exportProducts(Profile $profile, bool $isAdmin = false): string
    {
        $productIds = explode(',', (string)$profile->getProductIds());
        if (empty($productIds)) {
            return '';
        }
        $date = $this->date->date('Y-m-d_H-i-s');
        $fileNameUniqueId = sprintf('products-%s-%s.xlsx', $date, uniqid());
        $dir = $this->filesystem->getDirectoryWrite('var');
        $isFile = false;
        $file = null;
        $content = $this->converter->getXlsFile($productIds, $isAdmin);
        $fileContent = $this->getFileContent($content);
        if (is_array($content)) {
            if (!isset($content['type']) || !isset($content['value'])) {
                $this->writeLog("Invalid arguments. Keys 'type' and 'value' are required.");
            }
            if ($content['type'] == 'filename') {
                $isFile = true;
                $file = $content['value'];
                if (!$dir->isFile($file)) {
                    $this->writeLog('File not found');
                }
            }
        }

        if ($content !== null) {
            if (!$isFile) {
                $dir->writeFile($fileNameUniqueId, $fileContent);
                $file = $fileNameUniqueId;
            }
        }
        return $file;
    }

    /**
     * Returns file content for writing.
     *
     * @param string|array $content
     * @return string|array
     */
    private function getFileContent($content)
    {
        if (isset($content['type']) && $content['type'] === 'string') {
            return $content['value'];
        }

        return $content;
    }

    protected function writeLog(string|\Stringable $message, array $context = []){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_ProductExportRabbitMQ', 'productexport')){
            $this->logger->info($message, $context);
        }
    }
}
