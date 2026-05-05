<?php
namespace Branch8\RoleDelegate\Cron;

use Branch8\RoleDelegate\Model\ResourceModel\Delegate\CollectionFactory;
use Psr\Log\LoggerInterface;
use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class ProcessDelegation
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /**
     * Constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param DelegateRepositoryInterface $delegateRepository
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        DelegateRepositoryInterface $delegateRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->delegateRepository = $delegateRepository;
    }

    public function execute()
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $nowStr = $now->format('Y-m-d H:i:s');

        $pending = $this->collectionFactory->create();
        $pending->addFieldToFilter('status', 'pending');
        $pending->addFieldToFilter('start_at', ['lteq' => $nowStr]);
        foreach ($pending as $item) {
            try {
                $item->setData('status', 'active');
                $item->save();
                $this->logger->info("Delegate activated id={$item->getId()} user={$item->getUserId()} delegate={$item->getDelegateUserId()}");
                // TODO: dispatch event, write history, write to action log
            } catch (\Exception $e) {
                $this->logger->error('Error activating delegation: ' . $e->getMessage());
            }
        }

        $active = $this->collectionFactory->create();
        $active->addFieldToFilter('status', 'active');
        $active->addFieldToFilter('end_at', ['lt' => $nowStr]);
        foreach ($active as $item) {
            try {
                $item->setData('status', 'expired');
                $item->save();
                $this->logger->info("Delegate expired id={$item->getId()} user={$item->getUserId()} delegate={$item->getDelegateUserId()}");
                // TODO: dispatch event, write history, write to action log
            } catch (\Exception $e) {
                $this->logger->error('Error expiring delegation: ' . $e->getMessage());
            }
        }

        return $this;
    }
}
