<?php

declare(strict_types=1);

namespace HotaiConnected\HotaiPoint\Model;

use Psr\Log\LoggerInterface;

class PointFileParser
{
    const FIELD_SEPARATOR = '|';
    const FILE_END_STRING = 'end';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Parse point file content
     *
     * Format: OneId|MemberAccount|TotalPoints|ReminderPoints
     *
     * @param string $content
     * @return array
     */
    public function parse(string $content): array
    {
        $records = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Skip end marker
            if (strtolower($line) === self::FILE_END_STRING) {
                continue;
            }

            $fields = explode(self::FIELD_SEPARATOR, $line);

            if (count($fields) < 4) {
                $this->logger->warning('[PointFileParser] Invalid line ' . ($lineNum + 1) . ': ' . $line);
                continue;
            }

            $records[] = [
                'one_id' => trim($fields[0]),
                'member_account' => trim($fields[1]),
                'total_points' => (int)trim($fields[2]),
                'reminder_points' => (int)trim($fields[3]),
            ];
        }

        return $records;
    }

    /**
     * Filter records by minimum points threshold
     *
     * @param array $records
     * @param int $minPoints
     * @return array
     */
    public function filter(array $records, int $minPoints = 100): array
    {
        return array_filter($records, function ($record) use ($minPoints) {
            return $record['total_points'] > $minPoints;
        });
    }
}
