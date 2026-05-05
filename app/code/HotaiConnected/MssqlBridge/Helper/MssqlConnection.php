<?php

declare(strict_types=1);

namespace HotaiConnected\MssqlBridge\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class MssqlConnection
{
    private const CONFIG_PATH_PREFIX = 'hotaiconnected_mssql/';

    /**
     * Connection name → [server group, database field]
     */
    private const CONNECTION_MAP = [
        'hopes_hifi' => ['server' => 'hotaishop_server', 'database_field' => 'hopes_hifi_database'],
        'hotaishop'  => ['server' => 'hotaishop_server', 'database_field' => 'hotaishop_database'],
        'hotaigo'    => ['server' => 'hotaigo_server',   'database_field' => 'database'],
        'yoxi'       => ['server' => 'yoxi_server',     'database_field' => 'database'],
    ];

    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;

    /** @var \PDO[] */
    private array $connections = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * Get config value for a server group field.
     */
    private function getServerConfigValue(string $serverGroup, string $field): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_PREFIX . $serverGroup . '/' . $field
        );
    }

    /**
     * Resolve connection name to server group and database field.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveConnection(string $connectionName): array
    {
        if (!isset(self::CONNECTION_MAP[$connectionName])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid connection name "%s". Valid names: %s',
                    $connectionName,
                    implode(', ', array_keys(self::CONNECTION_MAP))
                )
            );
        }

        return self::CONNECTION_MAP[$connectionName];
    }

    /**
     * Create and return a PDO connection to MS SQL Server.
     */
    public function connect(string $connectionName): \PDO
    {
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        $map = $this->resolveConnection($connectionName);
        $serverGroup = $map['server'];
        $databaseField = $map['database_field'];

        $host = $this->getServerConfigValue($serverGroup, 'host');
        $port = $this->getServerConfigValue($serverGroup, 'port') ?: '1433';
        $database = $this->getServerConfigValue($serverGroup, $databaseField);
        $username = $this->getServerConfigValue($serverGroup, 'username');
        $encryptedPassword = $this->getServerConfigValue($serverGroup, 'password');
        $password = $encryptedPassword ? $this->encryptor->decrypt($encryptedPassword) : '';

        if (!$host || !$database || !$username) {
            throw new \RuntimeException(
                sprintf(
                    'MS SQL connection "%s" is not configured. '
                    . 'Please fill in Stores > Configuration > Hotai Connected > MS SQL Bridge > %s.',
                    $connectionName,
                    $serverGroup
                )
            );
        }

        $dsn = sprintf('sqlsrv:Server=%s,%s;Database=%s', $host, $port, $database);

        try {
            $this->connections[$connectionName] = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                sprintf('MS SQL connection to "%s" failed.', $connectionName),
                (int) $e->getCode(),
                $e
            );
        }

        return $this->connections[$connectionName];
    }

    /**
     * Test the connection and return status info.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $connectionName): array
    {
        try {
            $pdo = $this->connect($connectionName);
            $stmt = $pdo->query('SELECT 1 AS test');
            $result = $stmt->fetch();

            return [
                'success' => true,
                'message' => sprintf(
                    'Connection "%s" successful. Test query returned: %s',
                    $connectionName,
                    $result['test'] ?? 'NULL'
                ),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Connection "%s" failed: %s',
                    $connectionName,
                    $e->getMessage()
                ),
            ];
        }
    }

    /**
     * Execute a SELECT query and return all rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function query(string $connectionName, string $sql, array $params = []): array
    {
        $pdo = $this->connect($connectionName);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Execute a non-SELECT statement (INSERT, UPDATE, DELETE) and return affected row count.
     */
    public function execute(string $connectionName, string $sql, array $params = []): int
    {
        $pdo = $this->connect($connectionName);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
