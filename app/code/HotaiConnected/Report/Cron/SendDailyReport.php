<?php

namespace HotaiConnected\Report\Cron;

use HotaiConnected\Report\Model\ReportManager;
use HotaiConnected\Report\Model\SlackNotifier;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;

class SendDailyReport
{
    /**
     * @var ReportManager
     */
    private $reportManager;

    /**
     * @var SlackNotifier
     */
    private $slackNotifier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $appState;

    /**
     * @param ReportManager $reportManager
     * @param SlackNotifier $slackNotifier
     * @param LoggerInterface $logger
     * @param State $appState
     */
    public function __construct(
        ReportManager $reportManager,
        SlackNotifier $slackNotifier,
        LoggerInterface $logger,
        State $appState
    ) {
        $this->reportManager = $reportManager;
        $this->slackNotifier = $slackNotifier;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * Get environment type
     *
     * @return string
     */
    private function getEnvType(): string
    {
        $envType = getenv('ENV_TYPE');
        return $envType !== false ? $envType : 'unknown';
    }

    /**
     * Check if current environment is production
     *
     * @return bool
     */
    private function isProductionEnvironment(): bool
    {
        return $this->getEnvType() == 'production';
    }

    /**
     * Check if triggered from admin panel
     *
     * @return bool
     */
    private function isTriggeredFromAdmin(): bool
    {
        try {
            return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if execution is allowed
     * - Production: always allowed
     * - Other environments: only allowed when triggered from admin
     *
     * @return bool
     */
    private function isExecutionAllowed(): bool
    {
        return $this->isProductionEnvironment() || $this->isTriggeredFromAdmin();
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        // 檢查是否允許執行
        // Production 環境：允許自動排程
        // 其他環境：只允許從後台手動觸發
        if (!$this->isExecutionAllowed()) {
            return;
        }

        $this->logger->info('Starting daily report cron job');

        try {
            // 執行所有報表
            $results = $this->reportManager->executeAll();

            // 收集需要發送的訊息
            $messages = [];
            $skippedReports = [];

            foreach ($results as $reportName => $result) {
                if ($result['success']) {
                    // 只有當有資料時才發送訊息
                    if (!empty($result['data'])) {
                        $messages[] = $result['formatted'];
                        $this->logger->info('Report has data, will send to Slack', [
                            'report' => $reportName,
                            'row_count' => count($result['data'])
                        ]);
                    } else {
                        $skippedReports[] = $reportName;
                        $this->logger->info('Report has no data, skipping Slack notification', [
                            'report' => $reportName
                        ]);
                    }
                } else {
                    $this->logger->error('Report failed, skipping Slack notification', [
                        'report' => $reportName,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // 發送到 Slack
            if (!empty($messages)) {
                $this->slackNotifier->sendMultiple($messages);
                $this->logger->info('Daily report sent to Slack', [
                    'message_count' => count($messages)
                ]);
            } else {
                $this->logger->info('No messages to send to Slack (all reports empty or failed)');
            }

            $this->logger->info('Daily report cron job completed', [
                'total_reports' => count($results),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success'])),
                'sent_to_slack' => count($messages),
                'skipped_empty' => count($skippedReports),
                'skipped_reports' => $skippedReports
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Daily report cron job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
