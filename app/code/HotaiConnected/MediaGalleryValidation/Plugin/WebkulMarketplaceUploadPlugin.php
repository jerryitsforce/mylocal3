<?php
/**
 * app/code/HotaiConnected/MediaGalleryValidation/Plugin/WebkulMarketplaceUploadPlugin.php
 */
namespace HotaiConnected\MediaGalleryValidation\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class WebkulMarketplaceUploadPlugin
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
     * @var JsonFactory
     */
    private $jsonResultFactory;
    
    /**
     * @param LoggerInterface $logger
     * @param JsonFactory $jsonResultFactory
     */
    public function __construct(
        LoggerInterface $logger,
        JsonFactory $jsonResultFactory
    ) {
        $this->logger = $logger;
        $this->jsonResultFactory = $jsonResultFactory;
    }
    
    /**
     * 驗證檔案大小 - 用於 Webkul Marketplace
     */
    public function aroundExecute(
        \Webkul\Marketplace\Controller\Wysiwyg\Gallery\Upload $subject,
        \Closure $proceed
    ) {
        try {
            $this->validateFileSize('Webkul Marketplace Wysiwyg Gallery');
            return $proceed();
        } catch (LocalizedException $e) {

            $result = $this->jsonResultFactory->create();
            return $result->setData([
                'error' => $e->getMessage(),
                'errorcode' => 'FILE_SIZE_EXCEEDED',
            ]);
        }
    }
    
    private function validateFileSize(string $context): void
    {
        // 檢查是否有上傳檔案
        if (empty($_FILES)) {
            return;
        }
        
        // 專門檢查 Webkul 使用的 'image' 欄位
        if (!isset($_FILES['image'])) {
            return;
        }
        
        $file = $_FILES['image'];
        
        // 檢查檔案上傳是否成功
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return;
        }
        
        // 檢查檔案大小
        if (isset($file['size']) && isset($file['name'])) {
            $size = $file['size'];
            $fileName = $file['name'];
            
            // 檢查檔案是否為空
            if ($size <= 0) {
                return;
            }
            
            // 檢查檔案大小限制
            if ($size > self::MAX_FILE_SIZE) {
                $sizeInMB = round($size / 1024 / 1024, 2);
                $maxSizeInMB = round(self::MAX_FILE_SIZE / 1024 / 1024, 1);
                
                throw new LocalizedException(
                    __(
                        '檔案大小超過限制'
                    )
                );
            }
            
        }
        return;
    }
}