<?php

namespace Branch8\CatalogCustom\Plugin;

use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Json\EncoderInterface;
use Webkul\Marketplace\Block\Product\Helper\Form\Gallery\Content;

class ChangeTemplatePlugin
{
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $_mediaConfig;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoderInterface;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @param Config $mediaConfig
     * @param EncoderInterface $jsonEncoderInterface
     * @param Filesystem $filesystem
     * @throws FileSystemException
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Json\EncoderInterface $jsonEncoderInterface,
        Filesystem $filesystem
    ) {
        $this->_mediaConfig = $mediaConfig;
        $this->_jsonEncoderInterface = $jsonEncoderInterface;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }
    /**
     * Custom setTemplate for product gallery
     * @param Content $subject
     * @param $result
     */
    public function afterGetTemplate(Content $subject, $result)
    {
        return 'Branch8_CatalogCustom::product/helper/gallery.phtml';
    }

    /**
     * Get product image data.
     *
     * @return array
     */
    public function aroundGetProductImagesJson($subject, \Closure $proceed)
    {
        $productColl = $subject->getProduct();
        if ($productColl) {
            $mediaGalleryImages = $productColl->getMediaGalleryImages();
            $productImages = [];
            if (count($mediaGalleryImages) > 0) {
                foreach ($mediaGalleryImages as &$mediaGalleryImage) {
                    $imagePath = $this->_mediaConfig->getMediaPath(
                        $mediaGalleryImage['file']
                    );
                    if ($this->mediaDirectory->isFile($imagePath)) {
                        $mediaGalleryImage['url'] = $this->_mediaConfig->getMediaUrl(
                            $mediaGalleryImage['file']
                        );
                    } else {
                        $mediaGalleryImage['url'] = $this->_mediaConfig->getBaseTmpMediaUrl().$mediaGalleryImage['file'];
                    }
                    array_push($productImages, $mediaGalleryImage->getData());
                }

                return $this->_jsonEncoderInterface->encode($productImages);
            }

        }
        return '[]';
    }
}
