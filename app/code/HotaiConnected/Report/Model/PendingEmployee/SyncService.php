<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Model\PendingEmployee;

use HotaiConnected\Report\Model\SlackNotifier;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SyncService
{
    private const DEFAULT_CATEGORY_IDENTITY = 'HotaiEMP';

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var SearchApiClient
     */
    private SearchApiClient $searchApiClient;

    /**
     * @var SlackNotifier
     */
    private SlackNotifier $slackNotifier;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SearchApiClient $searchApiClient
     * @param SlackNotifier $slackNotifier
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SearchApiClient $searchApiClient,
        SlackNotifier $slackNotifier,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->searchApiClient = $searchApiClient;
        $this->slackNotifier = $slackNotifier;
        $this->logger = $logger;
    }

    /**
     * Sync pending employees by One IDs
     *
     * @param array $oneIds Array of One IDs
     * @param bool $dryRun Only query, don't insert/update or send Slack
     * @param bool $sendSlack Send Slack notification
     * @param bool $mockApi Use mock API response instead of real API
     * @return array Sync result
     */
    public function sync(array $oneIds, bool $dryRun = false, bool $sendSlack = true, bool $mockApi = false): array
    {
        $this->logger->info('SyncService: Starting sync', [
            'one_ids_count' => count($oneIds),
            'dry_run' => $dryRun,
            'send_slack' => $sendSlack,
            'mock_api' => $mockApi
        ]);

        $result = [
            'total_input' => count($oneIds),
            'group_a' => [
                'count' => 0,
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
                'failed_one_ids' => [],
                'one_ids' => []
            ],
            'group_b' => [
                'count' => 0,
                'query_count' => 0,
                'response_count' => 0,
                'inserted' => 0,
                'updated' => 0,
                'diff_count' => 0,
                'missing_one_ids' => [],
                'one_ids' => []
            ],
            'slack_sent' => false
        ];

        $connection = $this->resourceConnection->getConnection();

        // Step 1: Split One IDs into Group A (in customer_entity) and Group B (not in customer_entity)
        $groupA = [];
        $groupB = [];

        if (!empty($oneIds)) {
            $customerEntityTable = $connection->getTableName('customer_entity');
            $select = $connection->select()
                ->from($customerEntityTable, ['member_seq', 'phone_number'])
                ->where('member_seq IN (?)', $oneIds);

            $existingCustomers = $connection->fetchPairs($select);

            foreach ($oneIds as $oneId) {
                if (isset($existingCustomers[$oneId])) {
                    $groupA[$oneId] = $existingCustomers[$oneId]; // oneId => phone_number
                } else {
                    $groupB[] = $oneId;
                }
            }
        }

        $result['group_a']['count'] = count($groupA);
        $result['group_a']['one_ids'] = array_keys($groupA);
        $result['group_b']['count'] = count($groupB);
        $result['group_b']['one_ids'] = $groupB;
        $result['group_b']['query_count'] = count($groupB);

        $this->logger->info('SyncService: Split into groups', [
            'group_a_count' => $result['group_a']['count'],
            'group_b_count' => $result['group_b']['count']
        ]);

        if ($dryRun) {
            $this->logger->info('SyncService: Dry run mode - skipping insert/update and Slack');
            return $result;
        }

        // Step 2: Process Group A (existing customers)
        if (!empty($groupA)) {
            $processedA = $this->processGroupA($connection, $groupA);
            $result['group_a']['inserted'] = $processedA['inserted'];
            $result['group_a']['updated'] = $processedA['updated'];
            $result['group_a']['failed'] = $processedA['failed'];
            $result['group_a']['failed_one_ids'] = $processedA['failed_one_ids'];
        }

        // Step 3: Process Group B (non-customers, need API call)
        if (!empty($groupB)) {
            $processedB = $this->processGroupB($connection, $groupB, $mockApi);
            $result['group_b']['response_count'] = $processedB['response_count'];
            $result['group_b']['inserted'] = $processedB['inserted'];
            $result['group_b']['updated'] = $processedB['updated'];
            $result['group_b']['diff_count'] = $processedB['diff_count'];
            $result['group_b']['missing_one_ids'] = $processedB['missing_one_ids'];
        }

        // Step 4: Send Slack notification
        if ($sendSlack) {
            try {
                $slackMessage = $this->buildSlackMessage($result);
                $result['slack_sent'] = $this->slackNotifier->send(
                    $slackMessage,
                    SlackNotifier::TYPE_COMMAND
                );

                $this->logger->info('SyncService: Slack notification', [
                    'sent' => $result['slack_sent']
                ]);
            } catch (\Exception $e) {
                $this->logger->error('SyncService: Slack notification failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('SyncService: Sync completed', $result);

        return $result;
    }

    /**
     * Process Group A: Existing customers
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param array $groupA [oneId => phoneNumber]
     * @return array
     */
    private function processGroupA($connection, array $groupA): array
    {
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $failedOneIds = [];

        $pendingTable = $connection->getTableName('customer_pending_employee');
        $phoneNumbers = array_values($groupA);

        // Get existing phone numbers in pending_employee
        $select = $connection->select()
            ->from($pendingTable, ['cellphone', 'entity_id'])
            ->where('cellphone IN (?)', $phoneNumbers);
        $existingPhones = $connection->fetchPairs($select);

        $now = date('Y-m-d H:i:s', strtotime('+8 hours'));

        foreach ($groupA as $oneId => $phoneNumber) {
            try {
                if (isset($existingPhones[$phoneNumber])) {
                    // Update existing record
                    $connection->update(
                        $pendingTable,
                        [
                            'isEnabled' => 1,
                            'updated_at' => $now
                        ],
                        ['entity_id = ?' => $existingPhones[$phoneNumber]]
                    );
                    $updated++;

                    $this->logger->debug('SyncService: Group A - Updated', [
                        'one_id' => $oneId,
                        'phone' => $phoneNumber
                    ]);
                } else {
                    // Insert new record
                    $connection->insert($pendingTable, [
                        'cellphone' => $phoneNumber,
                        'isEnabled' => 1,
                        'organizationIdentity' => null,
                        'categoryIdentity' => self::DEFAULT_CATEGORY_IDENTITY,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $inserted++;

                    $this->logger->debug('SyncService: Group A - Inserted', [
                        'one_id' => $oneId,
                        'phone' => $phoneNumber
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                $failedOneIds[] = $oneId;
                $this->logger->error('SyncService: Group A - Failed', [
                    'one_id' => $oneId,
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('SyncService: Group A processed', [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed
        ]);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'failed_one_ids' => $failedOneIds
        ];
    }

    /**
     * Process Group B: Non-customers (need API call)
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param array $groupB Array of One IDs
     * @param bool $mockApi Use mock API response
     * @return array
     */
    private function processGroupB($connection, array $groupB, bool $mockApi = false): array
    {
        $result = [
            'response_count' => 0,
            'inserted' => 0,
            'updated' => 0,
            'diff_count' => 0,
            'missing_one_ids' => []
        ];

        try {
            if ($mockApi) {
                $this->logger->info('SyncService: Group B - Using mock API response');
                $apiResponse = $this->generateMockSearchMemberResponse($groupB);
            } else {
                $apiResponse = $this->searchApiClient->searchMembers($groupB);
            }
            $result['response_count'] = count($apiResponse);

            $this->logger->info('SyncService: Group B - API response', [
                'query_count' => count($groupB),
                'response_count' => $result['response_count']
            ]);

            // Build map by memberId
            $responseMap = [];
            foreach ($apiResponse as $member) {
                $memberId = $member['memberId'] ?? null;
                if ($memberId) {
                    $responseMap[$memberId] = $member;
                }
            }

            // Find missing One IDs
            $result['missing_one_ids'] = array_values(array_diff($groupB, array_keys($responseMap)));
            $result['diff_count'] = count($result['missing_one_ids']);

            // Insert/Update pending_employee
            $pendingTable = $connection->getTableName('customer_pending_employee');
            $now = date('Y-m-d H:i:s', strtotime('+8 hours'));

            foreach ($responseMap as $memberId => $memberData) {
                $phoneNumber = $memberData['mobilePhone'] ?? null;
                if (empty($phoneNumber)) {
                    $this->logger->warning('SyncService: Group B - No phone number for member', [
                        'member_id' => $memberId
                    ]);
                    continue;
                }

                // Check if phone already exists
                $select = $connection->select()
                    ->from($pendingTable, ['entity_id'])
                    ->where('cellphone = ?', $phoneNumber);
                $existingId = $connection->fetchOne($select);

                if ($existingId) {
                    // Update
                    $connection->update(
                        $pendingTable,
                        [
                            'isEnabled' => 1,
                            'updated_at' => $now
                        ],
                        ['entity_id = ?' => $existingId]
                    );
                    $result['updated']++;
                } else {
                    // Insert
                    $connection->insert($pendingTable, [
                        'cellphone' => $phoneNumber,
                        'isEnabled' => 1,
                        'organizationIdentity' => null,
                        'categoryIdentity' => self::DEFAULT_CATEGORY_IDENTITY,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $result['inserted']++;
                }
            }

            $this->logger->info('SyncService: Group B processed', [
                'inserted' => $result['inserted'],
                'updated' => $result['updated'],
                'diff_count' => $result['diff_count']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('SyncService: Group B processing failed', [
                'error' => $e->getMessage()
            ]);
            // All become missing
            $result['missing_one_ids'] = $groupB;
            $result['diff_count'] = count($groupB);
        }

        return $result;
    }

    /**
     * Build Slack message
     *
     * @param array $result
     * @return array
     */
    private function buildSlackMessage(array $result): array
    {
        $totalFailed = $result['group_a']['failed'];
        $allFailedOneIds = $result['group_a']['failed_one_ids'];

        $message = "1. 員工資料更新\n";
        $message .= sprintf("• 新增：%d 筆\n", $result['group_a']['inserted'] + $result['group_b']['inserted']);
        $message .= sprintf("• 更新：%d 筆\n", $result['group_a']['updated'] + $result['group_b']['updated']);
        $message .= sprintf("• 失敗：%d 筆\n", $totalFailed);

        if (!empty($allFailedOneIds)) {
            $message .= "\n失敗清單 (One ID)：\n";
            foreach ($allFailedOneIds as $oneId) {
                $message .= sprintf("- %s\n", $oneId);
            }
        }

        $message .= "\n---\n\n";

        $message .= "2. 非和泰購會員查詢\n";
        $message .= sprintf("• 查詢總數：%d 筆\n", $result['group_b']['query_count']);
        $message .= sprintf("• 回應總數：%d 筆\n", $result['group_b']['response_count']);
        $message .= sprintf("• 差異數量：%d 筆\n", $result['group_b']['diff_count']);

        if (!empty($result['group_b']['missing_one_ids'])) {
            $message .= "\n差異 One ID 清單：\n";
            foreach ($result['group_b']['missing_one_ids'] as $oneId) {
                $message .= sprintf("- %s\n", $oneId);
            }
        }

        return ['text' => $message];
    }

    /**
     * Generate mock API response for search-member
     *
     * @param array $oneIds
     * @return array
     */
    private function generateMockSearchMemberResponse(array $oneIds): array
    {
        $mockResponse = [];
        foreach ($oneIds as $oneId) {
            $mockResponse[] = [
                'memberId' => $oneId,
                'mobilePhone' => '09' . substr(hash('sha256', (string)$oneId), 0, 8),
                'countryCode' => '886'
            ];
        }
        return $mockResponse;
    }
}
