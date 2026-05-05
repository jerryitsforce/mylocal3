<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Model\Queue;

use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use HotaiConnected\FinancialReconciliation\Helper\EcpaySellerRevenueExporter;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use ZipArchive;

/**
 * Class RevenueExportConsumer
 */
class RevenueExportConsumer
{
    private $logger;
    private $jsonSerializer;
    private $ecpaySellerRevenueExporter;
    private $fileSystem;

    /**
     * @param LoggerInterface $logger
     * @param JsonSerializer $jsonSerializer
     * @param EcpaySellerRevenueExporter $ecpaySellerRevenueExporter
     * @param Filesystem $fileSystem
     */
    public function __construct(
        LoggerInterface $logger,
        JsonSerializer $jsonSerializer,
        EcpaySellerRevenueExporter $ecpaySellerRevenueExporter,
        Filesystem $fileSystem
    ) {
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->ecpaySellerRevenueExporter = $ecpaySellerRevenueExporter;
        $this->fileSystem = $fileSystem;
    }

    /**
     * 當 Queue 收到訊息時，Magento 會自動呼叫此方法
     *
     * @param string $message
     * @return void
     */
    public function process(string $message): void
    {
        $startTime = microtime(true);
        ini_set("memory_limit", "-1");

        try {
            $this->logger->info('RevenueExportConsumer processing message: ' . $message);
            $messageArr = json_decode($message, true);

            if (empty($messageArr)) {
                $this->logger->warning('RevenueExportConsumer: No valid seller data in message.');
                return;
            }

            $generatedExcelFiles = [];
            $adminName = '';
            if (!empty($messageArr)) {
                $adminName = $messageArr[0]['admin_name'] ?? '';
            }

            $directoryWrite = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $exportRelativePath = 'export/';
            $exportDir = $directoryWrite->getAbsolutePath($exportRelativePath);

            if (!$directoryWrite->isExist($exportRelativePath)) {
                $directoryWrite->create($exportRelativePath);
            }
            foreach ($messageArr as $dataItem) {
                try {
                    $excelFilePath = $this->ecpaySellerRevenueExporter->exportAndZip(
                        $dataItem['from'],
                        $dataItem['to'],
                        $dataItem['seller_code']
                    );
                    if (file_exists($excelFilePath)) {
                        $generatedExcelFiles[] = $excelFilePath;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('RevenueExportConsumer Export error: ' . $e->getMessage());
                }
            }
            if (empty($generatedExcelFiles)) {
                $this->logger->error('RevenueExportConsumer: No excel files were generated.');
                return;
            }

            // 打包成 Zip
            $timestamp = (new \DateTime('now', new \DateTimeZone('Asia/Taipei')))->format('YmdHi');
            $finalZipFileName = $adminName . ' - 批次廠商月結對帳單 - ' . $timestamp . '.zip';
            $finalZipFilePath = $exportDir . $finalZipFileName;

            $zip = new ZipArchive();
            if ($zip->open($finalZipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($generatedExcelFiles as $excelFile) {
                    $zip->addFile($excelFile, basename($excelFile));
                }
                $zip->close();

                foreach ($generatedExcelFiles as $excelFile) {
                    if (file_exists($excelFile)) {
                        unlink($excelFile);
                    }
                }
                $this->logger->info('RevenueExportConsumer: Successfully created zip at ' . $finalZipFilePath);
            }

            // 清理暫存目錄
            $this->cleanupTempDir('tmp/seller_revenue_download/');

            // 紀錄效能
            $duration = microtime(true) - $startTime;
            $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
            $this->logger->info(sprintf(
                'RevenueExportConsumer Finished. Time: %.2fs, Memory: %.2f MB',
                $duration, $memoryUsage
            ));

        } catch (\Exception $e) {
            $this->logger->error('RevenueExportConsumer Error: ' . $e->getMessage());
        }
    }

    /**
     * 清理暫存目錄
     *
     * @param string $path
     * @return void
     */
    private function cleanupTempDir(string $path): void
    {
        try {
            $directoryWrite = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            if ($directoryWrite->isExist($path)) {
                $directoryWrite->delete($path);
                $this->logger->info('RevenueExportConsumer: Cleaned up temp directory ' . $path);
            }
        } catch (\Exception $e) {
            $this->logger->warning('RevenueExportConsumer Cleanup Warning: ' . $e->getMessage());
        }
    }
}
