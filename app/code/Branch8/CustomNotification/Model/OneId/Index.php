<?php

namespace Branch8\CustomNotification\Model\OneId;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;
use Zend_Db_Statement_Exception;

class Index
{
    private $resourceConnection;
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface    $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resource;
    }

    /**
     * @param int $notificationId
     * @param array $oneIdList
     * @return void
     */
    public function index(int $notificationId, array $oneIdList)
    {
        $this->remove($notificationId);
        $columns = [
            new Zend_Db_Expr($notificationId),
            'entity_id'
        ];
        $select = $this->resourceConnection->getConnection()->select()->from(
            'customer_entity',
            $columns
        )->where('member_seq IN (?)', $oneIdList)->group('entity_id');
        try {
            $query = $this->resourceConnection->getConnection()->insertFromSelect($select,
                'magenest_notification_customer_index',
                [
                    'notification_id',
                    'customer_id',
                ]
            );
            $this->resourceConnection->getConnection()->query($query);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param int $notificationId
     * @return int
     */
    public function remove(int $notificationId)
    {
        return $this->resourceConnection->getConnection()->delete(
            'magenest_notification_customer_index',
            [
                'notification_id = ?' => $notificationId
            ]
        );
    }
}
