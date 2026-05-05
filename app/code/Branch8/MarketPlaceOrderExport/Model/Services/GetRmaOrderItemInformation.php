<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;

class GetRmaOrderItemInformation
{
    private $rmaItems = [];
    public ResourceConnection $resourceConnection;
    public StoreManager $storeManager;
    public GetTZOffsetTransitions $getTZOffsetTransitions;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManager $storeManager
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection                          $resourceConnection,
        StoreManager                                $storeManager,
        GetTZOffsetTransitions                      $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        LoggerInterface                             $logger
    )
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->timezone = $timezone;
    }

    /**
     * @param $itemId
     * @param $rmaStatus
     * @param $col
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($itemId, $rmaStatus = 'no_status', $col = [])
    {
        $key = $itemId . '_' . $rmaStatus;
        if (isset($this->rmaItems[$key])) {
            return $this->rmaItems[$key];
        }
        $storeID = $this->storeManager->getStore()->getId();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $this->getTZOffsetTransitions->get($timezone);
        $connection = $this->resourceConnection->getConnection();
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'marketplace_rma_status_history.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $columns['date_add'] = $offsetDate;
        } else {
            $columns['date_add'] = 'created_at';
        }

        $select = $connection->select()
            ->from(
                'marketplace_rma_items',
                $col ?:$columns
            )->join('marketplace_rma_details',
                'marketplace_rma_items.rma_id = marketplace_rma_details.id',
                [
                    'rma_reason' => 'rma_reason',
                    'rma_delivery_time' => 'rma_delivery_time',
                    ]
            );
        $select->where('marketplace_rma_items.item_id = ? ', $itemId);
        if ($rmaStatus !== 'no_status') {
           $select->join(
                'marketplace_rma_status_history',
                'marketplace_rma_status_history.parent_id=marketplace_rma_details.id',
                ['created_at' => 'created_at']);
            $select->where('marketplace_rma_status_history.status = ?', $rmaStatus);
        }
        $row = $connection->fetchRow($select);
        //$this->logger->info($select->__toString());
        if ($row) {
            $this->rmaItems[$key] = $row;
        } else {
            $this->rmaItems[$key] = [];
        }
        return $this->rmaItems[$key];
    }
}
