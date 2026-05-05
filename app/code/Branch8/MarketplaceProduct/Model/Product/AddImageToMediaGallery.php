<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class AddImageToMediaGallery
{
    /**#@+
     * Constants for keys of image type.
     */
    const KEY_IMAGE = 'image';
    const KEY_SMALL_IMAGE = 'small_image';
    const KEY_THUMBNAIL = 'thumbnail';
    const KEY_SWATCH_IMAGE = 'swatch_image';
    const KEY_DPA_IMAGE = 'dpa_image';
    /**#@-*/

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var MediaConfig
     */
    private MediaConfig $mediaConfig;

    protected LoggerInterface $logger;

    /**
     * AddImageToMediaGallery constructor.
     *
     * @param Filesystem $filesystem
     * @param MediaConfig $mediaConfig
     */
    public function __construct(
        Filesystem  $filesystem,
        MediaConfig $mediaConfig,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->mediaConfig = $mediaConfig;
        $this->logger = $logger;
    }

    /**
     * Add image to media gallery.
     *
     * @param ProductInterface $product
     * @param array $imageGallery
     *
     * @return void
     *
     * @throws FileSystemException
     */
    public function execute(ProductInterface $product, array $imageGallery): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $baseMediaPath = $this->mediaConfig->getBaseMediaPath();
        $baseTmpMediaPath = $this->mediaConfig->getBaseTmpMediaPath();

        $product->setMediaGalleryEntries([]);
        foreach ($imageGallery as $image) {
            if (isset($image['removed']) && $image['removed'] == '1') {
                continue;
            }
            $mediaAttributes = null;
            $imageFile = $image['file'] ?? '';
            $mediaTypes = [
                self::KEY_IMAGE,
                self::KEY_SMALL_IMAGE,
                self::KEY_THUMBNAIL,
                self::KEY_SWATCH_IMAGE,
                self::KEY_DPA_IMAGE
            ];
            foreach ($mediaTypes as $type) {
                $file = $changedData[$type]['after'] ?? $product->getData($type) ?: null;
                if ($file && $file == $imageFile) {
                    $mediaAttributes[] = $type;
                }
            }

            $filePath = $mediaDirectory->getAbsolutePath($baseTmpMediaPath . $imageFile);
            if (!$mediaDirectory->isFile($filePath)) {
                $filePath = $mediaDirectory->getAbsolutePath($baseMediaPath . $imageFile);
            }

            try {
                $product->addImageToMediaGallery($filePath, $mediaAttributes, true, $image['disabled'] ?? false);
                $mediaDirectory->delete($filePath);
            } catch (\Exception $e) {
                $this->logger->error("Could not add image to media gallery for product with SKU: " . $product->getSku(). " ERROR: ".$e->getMessage());
                $this->logger->critical($e);
                continue;
            }
        }
    }
}
