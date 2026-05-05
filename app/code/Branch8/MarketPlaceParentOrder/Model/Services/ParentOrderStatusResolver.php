<?php

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderStatusResolverInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority\Collection;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Resolve parent order status base on sub order status;
 * @property ResourceConnection $resourceConnection
 */
class ParentOrderStatusResolver implements ParentOrderStatusResolverInterface
{
    const CACHE_KEY = 'PARENT_ORDER_STATUS_PRIORITY';

    const PENDING_STATUS = 'pending';
    private \Magento\Framework\App\CacheInterface $cache;
    private \Magento\Framework\Serialize\Serializer\Json $serializer;
    public CollectionFactory $collectionFactory;
    public ResourceConnection $resourceConnection;
    private \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail $parentOrderDetailResource;
    private Log $log;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param CollectionFactory $collectionFactory
     * @param \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail $parentOrderDetailResource
     * @param Log $log
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface                                 $cache,
        \Magento\Framework\Serialize\Serializer\Json                          $serializer,
        CollectionFactory                                                     $collectionFactory,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail $parentOrderDetailResource,
        Log                                                                   $log,
        ResourceConnection                                                    $resourceConnection
    )
    {
        $this->log = $log;
        $this->cache = $cache;
        $this->collectionFactory = $collectionFactory;
        $this->serializer = $serializer;
        $this->resourceConnection = $resourceConnection;
        $this->parentOrderDetailResource = $parentOrderDetailResource;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface|ParentOrderInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function resolve(ParentOrderInterface $parentOrder)
    {
        $lowestStatus = $this->calculateLowestStatus($parentOrder);
        $lowestState = $this->getStateFromStatus($lowestStatus);
        $detail = $parentOrder->getDetail()->setStatus($lowestStatus)->setState($lowestState);
        $this->parentOrderDetailResource->save($detail);
        return $detail;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return string
     */
    private function calculateLowestStatus(ParentOrderInterface $parentOrder)
    {

        $statusPriority = $this->getStatusPriority();
        $status = self::PENDING_STATUS;
        try {
            if (!$parentOrder->getSubOrders() || empty($statusPriority)) {
                return $status;
            }
            $currentPriority = $statusPriority[$parentOrder->getSubOrders()->getFirstItem()->getData('status')];
            $status = $parentOrder->getSubOrders()->getFirstItem()->getData('status');
            foreach ($parentOrder->getSubOrders() as $subOrder) {
                if (isset($statusPriority[$subOrder->getStatus()])
                    && $statusPriority[$subOrder->getStatus()] < $currentPriority) {
                    $currentPriority = $statusPriority[$subOrder->getStatus()];
                    $status = $subOrder->getStatus();
                }
            }
        } catch (\Throwable $exception) {
            $this->log->logException('ParentOrderStatusResolver', $exception);
        }
        return $status;
    }

    /**
     * @return array
     * Load from cache
     */
    private function getStatusPriority()
    {
        $data = $this->cache->load(self::CACHE_KEY);
        if ($data) {
            return (array)$this->serializer->unserialize($data);
        }
        $statues = [];
        /**
         * @var $collection Collection
         */
        $collection = $this->collectionFactory->create();
        foreach ($collection as $item) {
            $statues[$item->getStatus()] = $item->getPriority();
        }
        $this->cache->save($this->serializer->serialize($statues), self::CACHE_KEY);
        return $statues;
    }

    /**
     * @param string $status
     * @return string
     */
    private function getStateFromStatus(string $status)
    {
        $connection = $this->resourceConnection->getConnection();
        return (string)$connection->fetchOne(
            $connection->select()
                ->from(['sss' => 'sales_order_status_state'], [])
                ->where('status = ?', $status)
                ->limit(1)
                ->columns(['state'])
        );
    }
}
