<?php
/**
 * app/code/HotaiConnected/MediaGalleryValidation/Plugin/CatalogUploadPlugin.php
 */
namespace HotaiConnected\MediaGalleryValidation\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\RawFactory;
use Psr\Log\LoggerInterface;

class CatalogUploadPlugin
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
     * @var RawFactory
     */
    private $resultRawFactory;
    
    /**
     * @param LoggerInterface $logger
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        LoggerInterface $logger,
        RawFactory $resultRawFactory
    ) {
        $this->logger = $logger;
        $this->resultRawFactory = $resultRawFactory;
    }
    
    /**
     * 圍繞執行方法進行檔案大小驗證
     * 針對 Catalog Product Gallery Upload
     */
    public function aroundExecute(
        \Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload $subject,
        \Closure $proceed
    ) {
        try {
            // 驗證檔案大小
            $this->validateFileSize();
            
            return $proceed();
            
        } catch (LocalizedException $e) {
            return $this->createErrorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->createErrorResponse('檔案上傳時發生未預期的錯誤', 0);
        }
    }
    
    /**
     * 檔案大小驗證邏輯
     */
    private function validateFileSize(): void
    {
        // 檢查是否有上傳檔案
        if (empty($_FILES) || !isset($_FILES['image'])) {
            return;
        }
        
        $file = $_FILES['image'];
        
        // 檢查檔案大小
        if (isset($file['size']) && $file['size'] > 0) {
            $size = $file['size'];
            
            $sizeInMB = round($size / 1024 / 1024, 2);
            $maxSizeInMB = round(self::MAX_FILE_SIZE / 1024 / 1024, 1);
            
            if ($size > self::MAX_FILE_SIZE) {
                throw new LocalizedException(
                    __(
                        '檔案大小超過限制！檔案大小：%1 MB，最大允許：%2 MB。請選擇較小的檔案後重新上傳。',
                        $sizeInMB,
                        $maxSizeInMB
                    )
                );
            }
            
        } else {
            throw new LocalizedException(__('檔案大小資訊無效或檔案為空'));
        }
    }
    
    /**
     * 創建錯誤回應 - 遵循 Magento 核心格式
     */
    private function createErrorResponse(string $message, int $errorCode = 0)
    {
        $result = ['error' => $message, 'errorcode' => $errorCode];
        
        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        
        return $response;
    }
}