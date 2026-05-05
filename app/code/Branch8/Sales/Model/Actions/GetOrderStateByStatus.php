<?php

namespace Branch8\Sales\Model\Actions;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class GetOrderStateByStatus
{
    private $state = [];

    private \Magento\Sales\Model\ResourceModel\Order $resource;
    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface    $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $status
     * @return mixed|string
     */
    public function getStateByStatus($status)
    {
        /**
         *
         */
        if (isset($this->state[$status])) {
            return $this->state[$status];
        }
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('sales_order_status_state', 'state')->where('status = ? ', $status);
        $row = $this->resourceConnection->getConnection()->fetchRow($select);
        $this->state[$status] = $row ? $row['state'] : '';
        return $this->state[$status];
    }
}
