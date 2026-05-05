<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model\ProcessQueueType;

use Branch8\WishlistStockAlert\Model\ConfigData;
use Branch8\WishlistStockAlert\Model\QueueManager;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class Cron implements ProcessQueueTypeInterface
{
    /**
     * @var QueueManager
     */
    private QueueManager $queueManager;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var Branch8\WishlistStockAlert\Helper\Email
     */
    private \Branch8\WishlistStockAlert\Helper\Email $mailHelper;

    /**
     * @param QueueManager $queueManager
     * @param \Branch8\WishlistStockAlert\Helper\Email $mailHelper
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        QueueManager                             $queueManager,
        \Branch8\WishlistStockAlert\Helper\Email $mailHelper,
        CustomerRepositoryInterface              $customerRepository
    )
    {
        $this->mailHelper = $mailHelper;
        $this->queueManager = $queueManager;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param array $job
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(array $job)
    {
        try {
            $this->queueManager->markSentBy($job,
                ['queue_type' => \Branch8\WishlistStockAlert\Model\Config\ProcessQueueType::CRON]
            );
            $this->mailHelper->sendStockAlert($job);
            $this->queueManager->markSent($job);
        } catch (\Exception $e) {
            $this->queueManager->markFailed((int)$job['queue_id'], $e->getMessage(), $e->getTraceAsString());
        }
    }
}
