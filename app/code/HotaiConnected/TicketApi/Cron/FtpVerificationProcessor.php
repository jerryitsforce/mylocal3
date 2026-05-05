<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Cron;

use HotaiConnected\TicketApi\Helper\FtpHelper;
use HotaiConnected\TicketApi\Model\VerificationProcessor;
use Psr\Log\LoggerInterface;

class FtpVerificationProcessor
{
    const LOG_PREFIX = '[family_notify_ftp]';

    /**
     * @var FtpHelper
     */
    private FtpHelper $ftpHelper;

    /**
     * @var VerificationProcessor
     */
    private VerificationProcessor $verificationProcessor;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param FtpHelper $ftpHelper
     * @param VerificationProcessor $verificationProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        FtpHelper $ftpHelper,
        VerificationProcessor $verificationProcessor,
        LoggerInterface $logger
    ) {
        $this->ftpHelper = $ftpHelper;
        $this->verificationProcessor = $verificationProcessor;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $startTime = microtime(true);

        try {
            // Get today's date
            $date = date('Ymd');

            // Connect to FTP
            if (!$this->ftpHelper->connect()) {
                $this->logger->error(self::LOG_PREFIX . ' Failed to connect to FTP. Aborting.');
                return;
            }

            // Get remote ZIP file content
            $zipContent = $this->ftpHelper->getRemoteZipFilePath($date);

            if (!$zipContent) {
                $this->logger->warning(self::LOG_PREFIX . ' No ZIP file found for date: ' . $date);
                $this->ftpHelper->disconnect();
                return;
            }

            // Extract and read TXT files from ZIP content
            $txtContents = $this->ftpHelper->extractAndReadZipFile($zipContent);

            if (empty($txtContents)) {
                $this->logger->error(self::LOG_PREFIX . ' No TXT files extracted from ZIP');
                $this->ftpHelper->disconnect();
                return;
            }

            // Process each TXT file content
            $overallResults = [
                'total_files' => count($txtContents),
                'total_records' => 0,
                'total_success' => 0,
                'total_already_used' => 0,
                'total_failed' => 0,
                'success_serials' => [],
                'already_used_serials' => [],
                'failed_serials' => []
            ];

            foreach ($txtContents as $txtContent) {
                $filename = $txtContent['filename'];
                $content = $txtContent['content'];

                // Parse TXT content
                $txtData = $this->ftpHelper->parseTxtContent($content, $filename);

                if (empty($txtData)) {
                    continue;
                }

                // Process verification data
                $result = $this->verificationProcessor->processVerificationData($txtData);

                $overallResults['total_records'] += $result['total'];
                $overallResults['total_success'] += $result['success'];
                $overallResults['total_already_used'] += $result['already_used'];
                $overallResults['total_failed'] += $result['failed'];
                $overallResults['success_serials'] = array_merge(
                    $overallResults['success_serials'],
                    $result['success_serials']
                );
                $overallResults['already_used_serials'] = array_merge(
                    $overallResults['already_used_serials'],
                    $result['already_used_serials']
                );
                $overallResults['failed_serials'] = array_merge(
                    $overallResults['failed_serials'],
                    $result['failed_serials']
                );
            }

            // Disconnect from FTP
            $this->ftpHelper->disconnect();

            // Log overall results
            $executionTime = round(microtime(true) - $startTime, 2);

            // Build log message
            $logMessage = self::LOG_PREFIX . ' Cron job completed | Files: ' . $overallResults['total_files'] .
                ' | Records: ' . $overallResults['total_records'] .
                ' | Success: ' . $overallResults['total_success'] .
                ' | Already Used: ' . $overallResults['total_already_used'] .
                ' | Failed: ' . $overallResults['total_failed'] .
                ' | Time: ' . $executionTime . 's';

            // Add success serial numbers if any
            if (!empty($overallResults['success_serials'])) {
                $logMessage .= ' | Success Serials: ' . implode(', ', $overallResults['success_serials']);
            }

            // Add already used serial numbers if any
            if (!empty($overallResults['already_used_serials'])) {
                $logMessage .= ' | Already Used Serials: ' . implode(', ', $overallResults['already_used_serials']);
            }

            // Add failed serial numbers if any
            if (!empty($overallResults['failed_serials'])) {
                $logMessage .= ' | Failed Serials: ' . implode(', ', $overallResults['failed_serials']);
            }

            $this->logger->info($logMessage);

        } catch (\Exception $e) {
            $this->logger->critical(
                self::LOG_PREFIX . ' Critical error in cron execution: ' . $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString()
                ]
            );

            // Ensure FTP is disconnected
            $this->ftpHelper->disconnect();
        }
    }
}
