<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Model\PendingEmployee;

use HotaiConnected\Report\Helper\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class CsvExporter
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Config $config
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * Export pending employee data to CSV
     *
     * @param array $pendingEmployees Data from customer_pending_employee
     * @param array $apiResponseMap API response mapped by phone number [phone => data]
     * @return string Exported file path
     * @throws \Exception
     */
    public function export(array $pendingEmployees, array $apiResponseMap): string
    {
        $exportPath = $this->config->getExportPath();
        $rootPath = $this->directoryList->getRoot();
        $fullExportPath = $rootPath . '/' . $exportPath;

        // Ensure directory exists
        if (!is_dir($fullExportPath)) {
            mkdir($fullExportPath, 0755, true);
        }

        $filename = sprintf('pending_employee_report_%s.csv', date('Y-m-d'));
        $filePath = $fullExportPath . '/' . $filename;

        $this->logger->info('Exporting CSV', [
            'path' => $filePath,
            'record_count' => count($pendingEmployees)
        ]);

        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            throw new \Exception(sprintf('Failed to open file for writing: %s', $filePath));
        }

        // Write BOM for Excel UTF-8 compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Write header
        $headers = [
            'cellphone',
            'isEnabled',
            'organizationIdentity',
            'categoryIdentity',
            'created_at',
            'updated_at',
            'api_member_id',
            'api_name',
            'api_email',
            'api_birthday',
            'api_gender',
            'api_id',
            'api_signup_time',
            'api_found'
        ];
        fputcsv($handle, $headers);

        // Write data
        foreach ($pendingEmployees as $employee) {
            $phone = $employee['cellphone'];
            $apiData = $apiResponseMap[$phone] ?? null;

            $row = [
                $phone,
                $employee['isEnabled'] ?? '',
                $employee['organizationIdentity'] ?? '',
                $employee['categoryIdentity'] ?? '',
                $employee['created_at'] ?? '',
                $employee['updated_at'] ?? '',
                $apiData['memberId'] ?? '',
                $apiData['name'] ?? '',
                $apiData['email'] ?? '',
                $apiData['birthday'] ?? '',
                $apiData['gender'] ?? '',
                $apiData['id'] ?? '',
                $apiData['signupTime'] ?? '',
                $apiData ? 'true' : 'false'
            ];

            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->logger->info('CSV export completed', [
            'path' => $filePath,
            'file_size' => filesize($filePath)
        ]);

        // Return relative path for display
        return $exportPath . '/' . $filename;
    }
}
