<?php

namespace Branch8\HotaiAuth\Model;

use Magento\Framework\App\ResourceConnection;

class CustomLogger
{
    /**
     * Resource instance.
     *
     * @var Resource
     */
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Save (insert new) log.
     *
     * @param int $customerId
     * @param array $data
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function log($customerId, array $data)
    {
        $data = array_filter($data);

        if (!$data) {
            throw new \InvalidArgumentException("Log data is empty");
        }

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $connection->insert(
            $this->resource->getTableName('branch_custom_customer_log'),
            array_merge(['customer_id' => $customerId], $data)
        );

        return $this;
    }

    /**
     * Get latest log_id for a customer with non-null last_login_at
     *
     * @param $customerId
     * @return int
     */
    public function getLatestLoginLogId($customerId): int
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $tableName = $this->resource->getTableName('branch_custom_customer_log');

        $select = $connection->select()
            ->from($tableName, ['log_id'])
            ->where('customer_id = ?', $customerId)
            ->where('last_login_at IS NOT NULL')
            ->order('log_id DESC')
            ->limit(1);

        $result = $connection->fetchOne($select);
        return $result !== false ? (int)$result : 0;
    }
}
