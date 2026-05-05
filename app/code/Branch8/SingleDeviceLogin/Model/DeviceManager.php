<?php
namespace Branch8\SingleDeviceLogin\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Service class responsible for persisting and retrieving a customer's active device ID.
 *
 * The module enforces a single-device login by storing a unique device identifier
 * (UUID) for each logged in customer.  When a customer logs in on a new device
 * the value in this table is rotated to the new UUID, thereby invalidating any
 * previous device references on subsequent requests.
 */
class DeviceManager
{
    /**
     * Name of the database table storing device identifiers.  Magento will
     * automatically prefix this with the configured table prefix when
     * resolving table names via ResourceConnection.
     */
    public const TABLE_NAME = 'branch8_singledevice_customer_device';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Return the current active device ID for a customer, if any.
     *
     * @param int $customerId
     * @return string|null
     */
    public function getActiveDeviceId(int $customerId): ?string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(self::TABLE_NAME);
        $select = $connection->select()
            ->from($tableName, ['device_id'])
            ->where('customer_id = ?', $customerId)
            ->limit(1);
        $result = $connection->fetchOne($select);
        return $result ?: null;
    }

    /**
     * Persist or update the active device ID for the given customer.
     *
     * If a row already exists for the customer it will be updated with the
     * new device ID and timestamp.  Otherwise a new row is inserted.
     *
     * @param int    $customerId
     * @param string $deviceId
     * @return void
     */
    public function setActiveDeviceId(int $customerId, string $deviceId): void
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(self::TABLE_NAME);
        $data = [
            'customer_id' => $customerId,
            'device_id'   => $deviceId,
            // updated_at is managed by insertOnDuplicate via current timestamp
            'updated_at'  => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        // Insert or update the existing row
        $connection->insertOnDuplicate($tableName, $data, ['device_id', 'updated_at']);
    }
}
