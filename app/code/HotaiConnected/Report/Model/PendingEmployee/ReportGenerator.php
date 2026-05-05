<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Model\PendingEmployee;

use HotaiConnected\Report\Model\SlackNotifier;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ReportGenerator
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var ApiClient
     */
    private ApiClient $apiClient;

    /**
     * @var SlackNotifier
     */
    private SlackNotifier $slackNotifier;

    /**
     * @var CsvExporter
     */
    private CsvExporter $csvExporter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ApiClient $apiClient
     * @param SlackNotifier $slackNotifier
     * @param CsvExporter $csvExporter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ApiClient $apiClient,
        SlackNotifier $slackNotifier,
        CsvExporter $csvExporter,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->apiClient = $apiClient;
        $this->slackNotifier = $slackNotifier;
        $this->csvExporter = $csvExporter;
        $this->logger = $logger;
    }

    /**
     * Generate pending employee report
     *
     * @param bool $dryRun Only run queries, don't call API or send Slack
     * @param bool $sendSlack Send Slack notification
     * @param bool $exportCsv Export CSV file
     * @param bool $mockApi Use mock API response instead of real API
     * @return array Report result
     */
    public function generate(bool $dryRun = false, bool $sendSlack = true, bool $exportCsv = true, bool $mockApi = false): array
    {
        $this->logger->info('Starting Pending Employee Report generation', [
            'dry_run' => $dryRun,
            'send_slack' => $sendSlack,
            'export_csv' => $exportCsv,
            'mock_api' => $mockApi
        ]);

        $result = [
            'registered_count' => 0,
            'query_count' => 0,
            'response_count' => 0,
            'diff_count' => 0,
            'missing_phones' => [],
            'export_file_path' => null,
            'slack_sent' => false
        ];

        $connection = $this->resourceConnection->getConnection();

        // Step 1: Query registered employees (已在 customer_entity 的 member_seq)
        $sqlRegistered = "SELECT DISTINCT ce.member_seq
            FROM customer_pending_employee cpee
            LEFT JOIN customer_entity ce ON ce.phone_number = cpee.cellphone
            WHERE ce.entity_id IS NOT NULL
            AND cpee.isEnabled = 1";

        $registeredMembers = $connection->fetchCol($sqlRegistered);
        $result['registered_count'] = count($registeredMembers);

        $this->logger->info('Step 1: Registered employees count', [
            'count' => $result['registered_count']
        ]);

        // Step 2: Query unregistered phone numbers (不在 customer_entity)
        $sqlUnregistered = "SELECT cpee.*
            FROM customer_pending_employee cpee
            LEFT JOIN customer_entity ce ON ce.phone_number = cpee.cellphone
            WHERE ce.entity_id IS NULL";

        $unregisteredEmployees = $connection->fetchAll($sqlUnregistered);
        $result['query_count'] = count($unregisteredEmployees);

        $this->logger->info('Step 2: Unregistered employees count', [
            'count' => $result['query_count']
        ]);

        if ($dryRun) {
            $this->logger->info('Dry run mode - skipping API call, CSV export, and Slack notification');
            return $result;
        }

        // Step 3: Call API with unregistered phone numbers
        $phoneNumbers = array_column($unregisteredEmployees, 'cellphone');
        $apiResponse = [];
        $apiResponseMap = [];

        if (!empty($phoneNumbers)) {
            try {
                if ($mockApi) {
                    $this->logger->info('Step 3: Using mock API response');
                    $apiResponse = $this->generateMockFindMemberResponse($phoneNumbers);
                } else {
                    $apiResponse = $this->apiClient->findMembers($phoneNumbers);
                }
                $result['response_count'] = count($apiResponse);

                // Build map by phone number for easy lookup
                foreach ($apiResponse as $member) {
                    $phone = $member['mobilePhone'] ?? null;
                    if ($phone) {
                        $apiResponseMap[$phone] = $member;
                    }
                }

                $this->logger->info('Step 3: API response count', [
                    'count' => $result['response_count']
                ]);

            } catch (\Exception $e) {
                $this->logger->error('API call failed', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Step 4: Calculate difference
        $result['diff_count'] = $result['query_count'] - $result['response_count'];

        // Find phones that were sent but not in response
        $responsedPhones = array_keys($apiResponseMap);
        $missingPhones = array_diff($phoneNumbers, $responsedPhones);
        $result['missing_phones'] = array_values($missingPhones);

        $this->logger->info('Step 4: Difference calculated', [
            'diff_count' => $result['diff_count'],
            'missing_phones_count' => count($missingPhones)
        ]);

        // Step 5: Export CSV
        if ($exportCsv) {
            try {
                $result['export_file_path'] = $this->csvExporter->export(
                    $unregisteredEmployees,
                    $apiResponseMap
                );
                $this->logger->info('Step 5: CSV exported', [
                    'path' => $result['export_file_path']
                ]);
            } catch (\Exception $e) {
                $this->logger->error('CSV export failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Step 6: Send Slack notification
        if ($sendSlack && $result['export_file_path']) {
            try {
                $slackMessage = $this->buildSlackMessage($result);
                $result['slack_sent'] = $this->slackNotifier->send(
                    $slackMessage,
                    SlackNotifier::TYPE_COMMAND
                );

                $this->logger->info('Step 6: Slack notification', [
                    'sent' => $result['slack_sent']
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Slack notification failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Pending Employee Report generation completed', $result);

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
        $message = "數據統計\n";
        $message .= sprintf("• 和泰購員工：%d 筆\n", $result['registered_count']);
        $message .= sprintf("• 非和泰購會員查詢：%d 筆\n", $result['query_count']);
        $message .= sprintf("• 回應總數：%d 筆\n", $result['response_count']);
        $message .= sprintf("• 差異數量：%d 筆\n", $result['diff_count']);

        if (!empty($result['missing_phones'])) {
            $message .= "\n差異 One ID 清單\n";
            foreach ($result['missing_phones'] as $phone) {
                $message .= sprintf("- %s\n", $phone);
            }
        }

        if ($result['export_file_path']) {
            $message .= sprintf("\n匯出檔案路徑\n%s", $result['export_file_path']);
        }

        return ['text' => $message];
    }

    /**
     * Generate mock API response for find-member
     *
     * @param array $phoneNumbers
     * @return array
     */
    private function generateMockFindMemberResponse(array $phoneNumbers): array
    {
        $mockResponse = [];
        foreach ($phoneNumbers as $phone) {
            $mockResponse[] = [
                'mobilePhone' => $phone,
                'memberId' => 'MOCK-' . $phone,
                'name' => 'Mock User',
                'email' => 'mock_' . $phone . '@test.com',
                'birthday' => '1990-01-01',
                'gender' => 'M',
                'id' => 'MOCKID-' . $phone,
                'signupTime' => date('Y-m-d H:i:s'),
                'countryCode' => '886'
            ];
        }
        return $mockResponse;
    }
}
