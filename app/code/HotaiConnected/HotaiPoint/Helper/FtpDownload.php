<?php

declare(strict_types=1);

namespace HotaiConnected\HotaiPoint\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Io\Sftp;
use Psr\Log\LoggerInterface;

class FtpDownload
{
    const XML_PATH_ENABLE = 'hotaiconnected_hotaipoint/ftp_download/enable';
    const XML_PATH_HOST = 'hotaiconnected_hotaipoint/ftp_download/host';
    const XML_PATH_PORT = 'hotaiconnected_hotaipoint/ftp_download/port';
    const XML_PATH_USERNAME = 'hotaiconnected_hotaipoint/ftp_download/username';
    const XML_PATH_PASSWORD = 'hotaiconnected_hotaipoint/ftp_download/password';
    const XML_PATH_REMOTE_PATH = 'hotaiconnected_hotaipoint/ftp_download/remote_path';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Sftp
     */
    private Sftp $sftp;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var bool
     */
    private bool $isConnected = false;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Sftp $sftp
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Sftp $sftp,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->sftp = $sftp;
        $this->logger = $logger;
    }

    /**
     * Check if FTP download is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE);
    }

    /**
     * Connect to SFTP server
     *
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $host = $this->scopeConfig->getValue(self::XML_PATH_HOST);
            $port = (int)$this->scopeConfig->getValue(self::XML_PATH_PORT) ?: 22;
            $username = $this->scopeConfig->getValue(self::XML_PATH_USERNAME);
            $password = $this->scopeConfig->getValue(self::XML_PATH_PASSWORD);

            if (!$host || !$username) {
                $this->logger->error('[PointFtpDownload] FTP configuration is incomplete');
                return false;
            }

            $this->sftp->open([
                'host' => $host . ':' . $port,
                'username' => $username,
                'password' => $password,
            ]);

            $this->isConnected = true;
            return true;
        } catch (\Exception $e) {
            $this->logger->error('[PointFtpDownload] Connection error: ' . $e->getMessage());
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * Download point file from SFTP
     *
     * @param string $date Format: YYYYMMDD
     * @return string|false File content or false on failure
     */
    public function download(string $date)
    {
        if (!$this->isConnected) {
            $this->logger->error('[PointFtpDownload] No active SFTP connection');
            return false;
        }

        try {
            $remotePath = $this->scopeConfig->getValue(self::XML_PATH_REMOTE_PATH) ?: '/Export';
            $remoteFile = rtrim($remotePath, '/') . '/OneIdPoints' . $date . '.txt';

            $this->logger->info('[PointFtpDownload] Downloading: ' . $remoteFile);

            $content = $this->sftp->read($remoteFile);

            if ($content === false || empty($content)) {
                $this->logger->warning('[PointFtpDownload] File not found or empty: ' . $remoteFile);
                return false;
            }

            return $content;
        } catch (\Exception $e) {
            $this->logger->error('[PointFtpDownload] Download error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect from SFTP
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->isConnected) {
            $this->sftp->close();
            $this->isConnected = false;
        }
    }
}
