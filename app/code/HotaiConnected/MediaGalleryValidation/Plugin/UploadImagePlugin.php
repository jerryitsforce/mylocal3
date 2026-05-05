<?php
/**
 * app/code/HotaiConnected/MediaGalleryValidation/Plugin/UploadImagePlugin.php
 */
namespace HotaiConnected\MediaGalleryValidation\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class UploadImagePlugin
{
    /**
     * 最大檔案大小 (5MB)
     */
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * 驗證檔案大小 - 用於 MediaGalleryUi
     */
    public function beforeExecute(
        \Magento\MediaGalleryUi\Model\UploadImage $subject,
        string $targetFolder,
        string $type
    ): array {
        $this->validateFileSize('MediaGalleryUi');
        return [$targetFolder, $type];
    }
    
    /**
     * 檔案大小驗證邏輯
     */
    private function validateFileSize(string $context): void
    {
        // 檢查是否有上傳檔案
        if (empty($_FILES)) {
            $this->logger->warning("HotaiConnected Plugin ({$context}): 沒有檢測到上傳檔案");
            return;
        }
        
        // 取得第一個檔案進行驗證
        $firstFile = reset($_FILES);
        
        // 檢查檔案大小
        if (isset($firstFile['size'])) {
            $size = is_array($firstFile['size']) ? $firstFile['size'][0] : $firstFile['size'];
            $fileName = is_array($firstFile['name']) ? $firstFile['name'][0] : $firstFile['name'];
            
            $sizeInMB = round($size / 1024 / 1024, 2);
            $maxSizeInMB = round(self::MAX_FILE_SIZE / 1024 / 1024, 1);
            
            $this->logger->info("HotaiConnected Plugin ({$context}): 檔案 {$fileName} 大小：{$sizeInMB} MB");
            
            if ($size > self::MAX_FILE_SIZE) {
                $this->logger->error("HotaiConnected Plugin ({$context}): 檔案大小超過限制");
                throw new LocalizedException(
                    __(
                        '檔案大小超過限制！檔案大小：%1 MB，最大允許：%2 MB。請選擇較小的檔案後重新上傳。',
                        $sizeInMB,
                        $maxSizeInMB
                    )
                );
            }
        }
    }
}