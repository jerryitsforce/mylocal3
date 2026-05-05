<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Model;

use Magento\Framework\App\ResourceConnection;

class QueueManager
{
    const STATUS_PENDING = 0;
    const STATUS_SENT = 1;
    const STATUS_FAILED = 2;

    protected $resource;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
    /**
     * @var string
     */
    protected $table;
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        ResourceConnection                          $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->date = $date;
        $this->table = $resource->getTableName(
            'branch8_wishlist_stock_alert_email_queue'
        );
    }

    /**
     * @param int $storeId
     * @return void
     */
    public function generateAndEnqueue(int $storeId)
    {
        $batch = 100;
        $connection = $this->resource->getConnection();
        $wishListTable = $connection->getTableName('wishlist');
        $customerTable = $connection->getTableName('customer_entity');
        $wishListItemTable = $connection->getTableName('wishlist_item');
        $alertTable = $connection->getTableName('branch8_wishlist_stock_alert');
        $stockTable = $connection->getTableName('branch8_options_stock_index');
        $select = $connection->select();
        $columns = [
            'customer_id' => 'w.customer_id',
            'customer_email' => 'c.buyer_email',
            'alert_id' => 'alert.alert_id',
            'product_id' => 'wi.product_id',
            'combo' => 'alert.combo',
            'wishlist_item_id' => 'wi.wishlist_item_id',
        ];
        $select->from(
            ['w' => $wishListTable], []
        )->join(
            ['c' => $customerTable], 'w.customer_id=c.entity_id', []
        )->join(
            ['wi' => $wishListItemTable],
            'w.wishlist_id = wi.wishlist_id',
            []
        )->join(
            ['alert' => $alertTable],
            'wi.wishlist_item_id = alert.wishlist_item_id',
            []
        )->joinLeft(
            ['stock' => $stockTable],
            'alert.product_id = stock.product_id AND alert.combo = stock.combo',
            []
        )->where('wi.store_id = ?', $storeId)
            ->where('stock.is_salable = ?', 1)
            ->where('alert.was_out_of_stock_when_added = ?', 1)
            ->where('alert.notification_sent = ?', 0);
        $select->order('customer_id')->columns($columns);
        $rows = $connection->fetchAll($select);
        if (!$rows) {
            return;
        }
        /**
         * Group by customer
         */
        $grouped = [];
        foreach ($rows as $row) {
            $data=[
                'alert_id' => $row['alert_id'],
                'product_id' => $row['product_id'],
                'customer_email' => $row['customer_email'],
                'item_id' => $row['wishlist_item_id'],
                'combo' => $row['combo']
            ];
            $grouped[$row['customer_id']][] = $data;
        }
        /**
         * Batch 100 customers
         */
        $batches = array_chunk($grouped, $batch, true);
        foreach ($batches as $batch) {
            foreach ($batch as $customerId => $data) {
                $this->enqueue($customerId, $storeId, $data);
            }
        }
    }

    /**
     * Add job to queue
     */
    public function enqueue(
        int   $customerId,
        int   $storeId,
        array $data
    )
    {
        return $this->connection->insertOnDuplicate(
            $this->table,
            [
                'customer_id' => $customerId,
                'customer_email' => $data[0]['customer_email'],
                'store_id' => $storeId,
                'data' => json_encode($data),
                'status' => self::STATUS_PENDING,
                'created_at' => $this->date->gmtDate()
            ],
            ['customer_email', 'data', 'status', 'created_at']
        );
    }

    /**
     * Get pending jobs
     */
    public function getPending(int $limit = 100): array
    {
        $select = $this->connection->select()
            ->from($this->table)
            ->where('status = ?', self::STATUS_PENDING)
            ->order('queue_id ASC')
            ->limit($limit);
        return $this->connection->fetchAll($select);
    }

    /**
     * @param array $job
     * @param array $update
     * @return int
     */
    public function markSentBy(array $job, array $update)
    {
        $queueId = $job['queue_id'];
        return $this->connection->update(
            $this->table,
            $update,
            ['queue_id = ?' => $queueId]
        );
    }

    /**
     * Mark as sent
     */
    public function markSent(array $job)
    {
        $queueId = $job['queue_id'];
        $sentAt = date('Y-m-d H:i:s');
        $update = [
            'status' => self::STATUS_SENT,
            'sent_at' => $sentAt
        ];
        $status = $this->connection->update(
            $this->table,
            $update,
            ['queue_id = ?' => $queueId]
        );
        $data = json_decode($job['data'], true);
        $alertIds = array_filter(array_map(
            fn($item) => $item['alert_id'] ?? null,
            $data ?: []
        ));
        if (!$alertIds) {
            return $status;
        }
        $this->connection->update(
            'branch8_wishlist_stock_alert',
            ['notification_sent' => 1, 'notified_at' => $sentAt],
            ['alert_id  IN (?)' => $alertIds]
        );
        return $status;
    }

    /**
     * @param int $queueId
     * @param $message
     * @param $trace
     * @return int
     */
    public function markFailed(int $queueId, $message = '', $trace = '')
    {
        return $this->connection->update(
            $this->table,
            [
                'status' => self::STATUS_FAILED,
                'trace' => $trace,
                'message' => $message,
            ],
            ['queue_id = ?' => $queueId]
        );
    }

    /**
     * Check if pending job exists
     * (avoid duplicate spam)
     */
    public function existsPending(
        int $customerId,
        int $storeId
    ): bool
    {
        $select = $this->connection->select()
            ->from($this->table, ['queue_id'])
            ->where('customer_id = ?', $customerId)
            ->where('store_id = ?', $storeId)
            ->where('status = ?', self::STATUS_PENDING)
            ->limit(1);

        return (bool)$this->connection->fetchOne($select);
    }

    /**
     * Delete old sent jobs (cleanup)
     */
    public function cleanOld(int $days = 30)
    {
        $date = date(
            'Y-m-d H:i:s',
            strtotime("-{$days} days")
        );
        return $this->connection->delete(
            $this->table,
            [
                'status = ?' => self::STATUS_SENT,
                'sent_at < ?' => $date
            ]
        );
    }

    /**
     * @param int $queueId
     * @return array
     */
    public function getQueue(int $queueId)
    {
        $select = $this->connection->select()
            ->from($this->table)
            ->where('queue_id = ?', $queueId);
        return $this->connection->fetchRow($select);
    }
}
