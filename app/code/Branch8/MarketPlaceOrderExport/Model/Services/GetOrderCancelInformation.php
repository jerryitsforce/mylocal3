<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;

class GetOrderCancelInformation
{
    private $cached = [];
    public ResourceConnection $resourceConnection;
    public StoreManager $storeManager;
    public GetTZOffsetTransitions $getTZOffsetTransitions;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManager $storeManager
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     */
    public function __construct(
        ResourceConnection                          $resourceConnection,
        StoreManager                                $storeManager,
        GetTZOffsetTransitions                      $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    )
    {
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->timezone = $timezone;
    }

    /**
     * @param $orderId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($orderId)
    {
        if (isset($this->cached[$orderId])) {
            return $this->cached[$orderId];
        }
        $storeID = $this->storeManager->getStore()->getId();
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $this->getTZOffsetTransitions->get($timezone);
        $connection = $this->resourceConnection->getConnection();
        $columns = [
            'status' => 'status',
            'order_cancellation_time' => 'created_at',
            'reason_for_cancellation' => 'comment'
        ];
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'sales_order_status_history.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $columns['date_add'] = $offsetDate;
        } else {
            $columns['date_add'] = 'created_at';
        }
        $select = $connection->select()
            ->from(
                'sales_order_status_history',
                $columns
            )->where('parent_id = ?', $orderId)
            ->where('status = ? ', 'canceled')
            ->where('entity_name = ?', 'order');
        $row = $connection->fetchRow($select);
        if ($row) {
            $this->cached[$orderId] = $row;
        } else {
            $this->cached[$orderId] = [];
        }
        return $this->cached[$orderId];
    }
}
