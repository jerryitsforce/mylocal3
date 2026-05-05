<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Helper;

use Branch8\FamilyBonusPin\Helper\FtpConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Io\Sftp;

class FtpHelper
{
    const LOG_PREFIX = '[family_notify_ftp]';

    /**
     * @var FtpConfig
     */
    private FtpConfig $ftpConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Sftp
     */
    private Sftp $sftp;

    /**
     * @var bool
     */
    private bool $isConnected = false;

    /**
     * @param FtpConfig $ftpConfig
     * @param LoggerInterface $logger
     * @param Sftp $sftp
     */
    public function __construct(
        FtpConfig $ftpConfig,
        LoggerInterface $logger,
        Sftp $sftp
    ) {
        $this->ftpConfig = $ftpConfig;
        $this->logger = $logger;
        $this->sftp = $sftp;
    }

    /**
     * Connect to SFTP server
     *
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $config = $this->ftpConfig->getFtpConfig();

            if (!$config['ConnectionPath'] || !$config['ConnectionAccount']) {
                $this->logger->error(self::LOG_PREFIX . ' FTP configuration is incomplete');
                return false;
            }

            // Prepare connection args for Magento SFTP
            $connectionArgs = [
                'host' => $config['ConnectionPath'] . ':' . $config['ConnectionPathPort'],
                'username' => $config['ConnectionAccount'],
                'password' => $config['ConnectionPassword']
            ];

            // Open SFTP connection
            $this->sftp->open($connectionArgs);
            $this->isConnected = true;

            return true;

        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' Connection error: ' . $e->getMessage());
            $this->isConnected = false;
            return false;
        }
    }

    /**
     * Get remote ZIP file content
     *
     * @param string $date Format: YYYYMMDD
     * @return string|false Returns ZIP file content or false on failure
     */
    public function getRemoteZipFilePath(string $date)
    {
        if (!$this->isConnected) {
            $this->logger->error(self::LOG_PREFIX . ' No active SFTP connection');
            return false;
        }

        try {
            $storeNo = $this->ftpConfig->getStoreNo();
            $remoteFileName = "{$storeNo}0001_B_{$date}.zip";
            $remoteFilePath = "./Download/{$remoteFileName}";

            // Read file content from remote (Magento SFTP read with null destination returns content)
            $content = $this->sftp->read($remoteFilePath, null);

            if ($content === false || empty($content)) {
                $this->logger->warning(self::LOG_PREFIX . ' Remote file not found: ' . $remoteFilePath);
                return false;
            }

            // Return the file content directly
            return $content;

        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' Error accessing remote file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract and read TXT files from ZIP content
     *
     * @param string $zipContent ZIP file binary content
     * @return array Array of TXT file contents (not paths)
     */
    public function extractAndReadZipFile(string $zipContent): array
    {
        try {
            if (empty($zipContent)) {
                $this->logger->error(self::LOG_PREFIX . ' ZIP content is empty');
                return [];
            }

            // Create temporary file
            $tempZip = tempnam(sys_get_temp_dir(), 'ftp_zip_');
            file_put_contents($tempZip, $zipContent);

            $zip = new \ZipArchive();
            if ($zip->open($tempZip) !== true) {
                $this->logger->error(self::LOG_PREFIX . ' Failed to open ZIP file');
                unlink($tempZip);
                return [];
            }

            // Set password as StoreNo
            $password = $this->ftpConfig->getStoreNo();
            if (!$zip->setPassword($password)) {
                $this->logger->error(self::LOG_PREFIX . ' Failed to set ZIP password');
                $zip->close();
                unlink($tempZip);
                return [];
            }

            $txtContents = [];
            $numFiles = $zip->numFiles;

            // Read each file directly from ZIP
            for ($i = 0; $i < $numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Only process TXT files
                if (pathinfo($filename, PATHINFO_EXTENSION) !== 'txt') {
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    continue;
                }

                $txtContents[] = [
                    'filename' => $filename,
                    'content' => $content
                ];
            }

            $zip->close();
            unlink($tempZip);

            return $txtContents;

        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' Extraction error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse TXT content with fixed-width format and return data array
     *
     * @param string $content TXT file content
     * @param string $filename Filename for logging
     * @return array
     */
    public function parseTxtContent(string $content, string $filename = 'unknown'): array
    {
        try {
            $lines = explode("\n", $content);

            $data = [];

            foreach ($lines as $lineNum => $line) {
                // Remove BOM if exists
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

                if (empty(trim($line))) {
                    continue;
                }

                // Skip first line (file header)
                if ($lineNum === 0) {
                    continue;
                }

                // Data lines: Parse by fixed positions
                // Ensure line has minimum length
                if (strlen($line) < 47) {
                    continue;
                }

                // Extract 20-character PIN code from position 27-47
                $pinCode = trim(substr($line, 26, 20));

                $data[] = $pinCode;
            }

            return $data;

        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' Parse error in ' . $filename . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Disconnect from SFTP
     */
    public function disconnect(): void
    {
        if ($this->isConnected) {
            $this->sftp->close();
            $this->isConnected = false;
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
