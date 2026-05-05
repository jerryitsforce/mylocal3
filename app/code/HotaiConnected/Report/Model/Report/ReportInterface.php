<?php

namespace HotaiConnected\Report\Model\Report;

interface ReportInterface
{
    /**
     * Get report name/title
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Execute SQL and get data
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Format data for Slack
     *
     * @param array $data
     * @return array
     */
    public function formatForSlack(array $data): array;
}
