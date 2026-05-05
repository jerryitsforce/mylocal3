<?php

namespace HotaiConnected\MediaGalleryValidation\Plugin;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class VariantionUploadPlugin
{
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    private $logger;
    private $jsonResultFactory;

    public function __construct(
        LoggerInterface $logger,
        JsonFactory $jsonResultFactory
    ) {
        $this->logger = $logger;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function aroundExecute(
        \Branch8\MarketplaceStaging\Controller\Variation\Upload $subject,
        \Closure $proceed
    ) {
        try {
            $this->validateFileSize('Branch8 MarketplaceProduct Gallery');
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
        if (!isset($_FILES['image'])) {
            return;
        }

        $fileData = $_FILES['image'];

        if (isset($fileData['error']) && $fileData['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        if (isset($fileData['size']) && isset($fileData['name'])) {
            $size = $fileData['size'];
            $fileName = $fileData['name'];

            if ($size <= 0) {
                return;
            }

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
    }
}
