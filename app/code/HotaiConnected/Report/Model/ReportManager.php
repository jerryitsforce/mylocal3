<?php

namespace HotaiConnected\Report\Model;

use HotaiConnected\Report\Model\Report\ReportInterface;
use Psr\Log\LoggerInterface;

class ReportManager
{
    /**
     * @var array
     */
    private $reportConfigs;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param array $reports
     * @param LoggerInterface $logger
     */
    public function __construct(
        array $reports,
        LoggerInterface $logger
    ) {
        $this->reportConfigs = $reports;
        $this->logger = $logger;
    }

    /**
     * Get all enabled reports
     *
     * @return ReportInterface[]
     */
    public function getAllReports(): array
    {
        $reports = [];
        foreach ($this->reportConfigs as $name => $config) {
            if ($this->isEnabled($config)) {
                $reports[$name] = $config['class'];
            }
        }
        return $reports;
    }

    /**
     * Get all report configs (including disabled)
     *
     * @return array
     */
    public function getAllReportConfigs(): array
    {
        return $this->reportConfigs;
    }

    /**
     * Check if report is enabled
     *
     * @param array $config
     * @return bool
     */
    private function isEnabled(array $config): bool
    {
        return isset($config['enabled']) && $config['enabled'] === true;
    }

    /**
     * Get report by name
     *
     * @param string $name
     * @return ReportInterface|null
     */
    public function getReport(string $name): ?ReportInterface
    {
        if (isset($this->reportConfigs[$name]) && $this->isEnabled($this->reportConfigs[$name])) {
            return $this->reportConfigs[$name]['class'];
        }
        return null;
    }

    /**
     * Execute all enabled reports
     *
     * @return array
     */
    public function executeAll(): array
    {
        $results = [];

        foreach ($this->reportConfigs as $name => $config) {
            // 跳過停用的報表
            if (!$this->isEnabled($config)) {
                $this->logger->info('Report skipped (disabled)', [
                    'report' => $name
                ]);
                continue;
            }

            /** @var ReportInterface $report */
            $report = $config['class'];

            try {
                $data = $report->getData();
                $results[$report->getName()] = [
                    'success' => true,
                    'data' => $data,
                    'formatted' => $report->formatForSlack($data)
                ];

                $this->logger->info('Report executed', [
                    'report' => $report->getName(),
                    'row_count' => count($data)
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Report execution failed', [
                    'report' => $report->getName(),
                    'error' => $e->getMessage()
                ]);

                $results[$report->getName()] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get report status summary
     *
     * @return array
     */
    public function getStatusSummary(): array
    {
        $summary = [
            'enabled' => [],
            'disabled' => []
        ];

        foreach ($this->reportConfigs as $name => $config) {
            /** @var ReportInterface $report */
            $report = $config['class'];
            $reportName = $report->getName();

            if ($this->isEnabled($config)) {
                $summary['enabled'][$name] = $reportName;
            } else {
                $summary['disabled'][$name] = $reportName;
            }
        }

        return $summary;
    }
}
