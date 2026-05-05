<?php
/**
 * Plugin to fix CopyHandler getMediaGalleryCollection returning null issue
 */
namespace HotaiConnected\Qware\Plugin;

use Magento\Catalog\Model\Product\Gallery\CopyHandler;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;

class CopyHandlerPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Around execute to handle null gallery data gracefully
     *
     * @param CopyHandler $subject
     * @param \Closure $proceed
     * @param Product $product
     * @param array $arguments
     * @return void
     */
    public function aroundExecute(
        CopyHandler $subject,
        \Closure $proceed,
        Product $product,
        $arguments = []
    ): void {
        try {
            // 先檢查並修正 media_gallery 資料
            $this->ensureMediaGalleryData($product);
            
            $proceed($product, $arguments);
            
        } catch (\Exception $e) {
            $this->logger->error('CopyHandler error: ' . $e->getMessage());
            return;
        }
    }

    /**
     * 確保產品有有效的 media_gallery 資料
     */
    private function ensureMediaGalleryData(Product $product): void
    {
        $mediaGallery = $product->getData('media_gallery');
        
        if ($mediaGallery === null) {
            $product->setData('media_gallery', ['images' => []]);
        } elseif (is_array($mediaGallery) && !isset($mediaGallery['images'])) {
            $mediaGallery['images'] = [];
            $product->setData('media_gallery', $mediaGallery);
        }
    }
}