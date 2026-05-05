<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Cron;

use Branch8\WishlistStockAlert\Model\Config\ProcessQueueType;
use Branch8\WishlistStockAlert\Model\ConfigData;
use Branch8\WishlistStockAlert\Model\ProcessQueueType\Cron;
use Branch8\WishlistStockAlert\Model\ProcessQueueType\Rabbitmq;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;

/**
 *
 */
class ProcessQueue
{
    protected $resource;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    private ConfigData $configData;
    /**
     * @var QueueManager
     */
    private QueueManager $queueManager;
    private Cron $cronProcess;
    private Rabbitmq $rabbitMQProcess;
    private CustomLogger $logger;

    /**
     * @param ResourceConnection $resource
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ConfigData $configData
     * @param QueueManager $queueManager
     * @param Cron $cronProcess
     * @param Rabbitmq $rabbitmqProcess
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection          $resource,
        TransportBuilder            $transportBuilder,
        StoreManagerInterface       $storeManager,
        ConfigData                  $configData,
        QueueManager                $queueManager,
        Cron                        $cronProcess,
        Rabbitmq                    $rabbitmqProcess,
        CustomerRepositoryInterface $customerRepository,
        CustomLogger                $logger
    )
    {
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->configData = $configData;
        $this->resource = $resource;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->cronProcess = $cronProcess;
        $this->rabbitMQProcess = $rabbitmqProcess;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->configData->enabled()) {
            return;
        }
        $processType = $this->configData->getConfigValue('process_queue_type');
        foreach ($this->queueManager->getPending() as $job) {
            try {
                if ($processType === ProcessQueueType::RABITTMQ) {
                    $this->rabbitMQProcess->execute($job);
                } else {
                    $this->cronProcess->execute($job);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                $this->logger->critical($e->getTraceAsString());
            }
        }
    }

}
