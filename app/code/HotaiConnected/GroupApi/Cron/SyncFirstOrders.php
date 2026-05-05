<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 */
declare(strict_types=1);

namespace HotaiConnected\GroupApi\Cron;

use HotaiConnected\MssqlBridge\Helper\MssqlConnection;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SyncFirstOrders
{
    private const MSSQL_CONNECTION = 'hopes_hifi';
    private const TIMEZONE = 'Asia/Taipei';

    private ResourceConnection $resourceConnection;
    private MssqlConnection $mssqlConnection;
    private LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        MssqlConnection $mssqlConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mssqlConnection = $mssqlConnection;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $yesterday = (new \DateTime('now', new \DateTimeZone(self::TIMEZONE)))
            ->modify('-1 day')
            ->format('Y-m-d');

        // created_at is UTC, convert Taipei time to UTC by subtracting 8 hours
        $startDate = $yesterday . ' 00:00:00 +08:00';
        $endDate = $yesterday . ' 23:59:59 +08:00';
        $startDateUtc = (new \DateTime($startDate))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $endDateUtc = (new \DateTime($endDate))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $this->logger->info('[GroupApi] SyncFirstOrders started', [
            'date' => $yesterday,
            'startDateUtc' => $startDateUtc,
            'endDateUtc' => $endDateUtc,
        ]);

        try {
            $rows = $this->fetchFirstOrders($startDateUtc, $endDateUtc);
            $total = count($rows);

            if ($total === 0) {
                $this->logger->info('[GroupApi] SyncFirstOrders: no first orders found for ' . $yesterday);
                return;
            }

            $success = 0;
            $failed = 0;

            foreach ($rows as $row) {
                try {
                    $this->mssqlConnection->execute(
                        self::MSSQL_CONNECTION,
                        'INSERT INTO FirstOrder (memberSeq, mobilePhone, hotaigoCustomerEntityID, hotaigoOrderEntityID, hotaigoOrderNo, create_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $row['member_seq'],
                            $row['phone_number'],
                            $row['customer_id'],
                            $row['entity_id'],
                            $row['increment_id'],
                            $row['created_at'],
                            $row['status'],
                        ]
                    );
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->logger->error('[GroupApi] SyncFirstOrders INSERT failed', [
                        'order_id' => $row['entity_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('[GroupApi] SyncFirstOrders completed', [
                'date' => $yesterday,
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[GroupApi] SyncFirstOrders error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function fetchFirstOrders(string $startDate, string $endDate): array
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = "SELECT ce.member_seq,
                    ce.phone_number,
                    so.entity_id,
                    so.customer_id,
                    so.increment_id,
                    so.created_at,
                    so.status
                FROM
                (SELECT entity_id,
                        customer_id,
                        increment_id,
                        created_at,
                        status,
                        row_number() OVER (PARTITION BY customer_id
                                            ORDER BY created_at ASC, entity_id ASC) AS rn
                FROM sales_order
                WHERE customer_id IS NOT NULL ) AS so
                LEFT JOIN customer_entity AS ce ON so.customer_id = ce.entity_id
                WHERE so.rn = 1
                AND so.created_at BETWEEN :startDate AND :endDate
                ORDER BY so.created_at DESC";

        return $connection->fetchAll($sql, [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
