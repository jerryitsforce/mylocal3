<?php

declare(strict_types=1);

namespace HotaiConnected\HotaiPoint\Cron;

use HotaiConnected\HotaiPoint\Helper\FtpDownload;
use HotaiConnected\HotaiPoint\Model\PointFileParser;
use HotaiConnected\HotaiPoint\Model\PointNotificationSender;
use Psr\Log\LoggerInterface;

class DownloadAndNotifyPoints
{
    /**
     * @var FtpDownload
     */
    private FtpDownload $ftpDownload;

    /**
     * @var PointFileParser
     */
    private PointFileParser $parser;

    /**
     * @var PointNotificationSender
     */
    private PointNotificationSender $notificationSender;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param FtpDownload $ftpDownload
     * @param PointFileParser $parser
     * @param PointNotificationSender $notificationSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        FtpDownload $ftpDownload,
        PointFileParser $parser,
        PointNotificationSender $notificationSender,
        LoggerInterface $logger
    ) {
        $this->ftpDownload = $ftpDownload;
        $this->parser = $parser;
        $this->notificationSender = $notificationSender;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('[PointFtpDownload] Cron start');

        if (!$this->ftpDownload->isEnabled()) {
            $this->logger->info('[PointFtpDownload] Cron skipped - FTP download disabled');
            return;
        }

        try {
            // 昨天日期
            $yesterday = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
            $yesterday->modify('-1 day');
            $fileDate = $yesterday->format('Ymd');

            // 1. Connect to SFTP
            if (!$this->ftpDownload->connect()) {
                $this->logger->error('[PointFtpDownload] Failed to connect to SFTP');
                return;
            }

            // 2. Download file
            $content = $this->ftpDownload->download($fileDate);
            $this->ftpDownload->disconnect();

            if ($content === false) {
                $this->logger->error('[PointFtpDownload] Failed to download file for date: ' . $fileDate);
                return;
            }

            // 3. Parse file
            $records = $this->parser->parse($content);
            $this->logger->info('[PointFtpDownload] Parsed ' . count($records) . ' records');

            if (empty($records)) {
                $this->logger->info('[PointFtpDownload] No records found, skipping');
                return;
            }

            // 4. Filter by min points
            $minPoints = $this->notificationSender->getMinPoints();
            $qualified = $this->parser->filter($records, $minPoints);
            $this->logger->info('[PointFtpDownload] Qualified records (TotalPoints > ' . $minPoints . '): ' . count($qualified));

            if (empty($qualified)) {
                $this->logger->info('[PointFtpDownload] No qualified records, skipping notification');
                return;
            }

            // 5. Send notification
            if (!$this->notificationSender->isEnabled()) {
                $this->logger->info('[PointFtpDownload] Notification disabled, skipping');
                return;
            }

            $result = $this->notificationSender->send($qualified, $fileDate);

            $this->logger->info('[PointFtpDownload] Notification result', [
                'total' => $result['total'],
                'success' => $result['success'],
                'failed' => $result['failed'],
                'error' => $result['error'],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[PointFtpDownload] Cron error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Ensure disconnect
            $this->ftpDownload->disconnect();
        }

        $this->logger->info('[PointFtpDownload] Cron end');
    }
}
