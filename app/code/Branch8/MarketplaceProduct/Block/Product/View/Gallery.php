<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Gallery\ImagesConfigFactoryInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Stdlib\ArrayUtils;

/**
 * Product gallery block.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Gallery extends \Magento\Catalog\Block\Product\View\Gallery
{
    /**
     * @var array
     */
    private array $galleryImagesConfig;

    /**
     * @var ImagesConfigFactoryInterface
     */
    private ImagesConfigFactoryInterface $galleryImagesConfigFactory;

    /**
     * Gallery constructor.
     *
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param array $data
     * @param ImagesConfigFactoryInterface|null $imagesConfigFactory
     * @param array $galleryImagesConfig
     * @param UrlBuilder|null $urlBuilder
     */
    public function __construct(
        Context                      $context,
        ArrayUtils                   $arrayUtils,
        EncoderInterface             $jsonEncoder,
        array                        $data = [],
        ImagesConfigFactoryInterface $imagesConfigFactory = null,
        array                        $galleryImagesConfig = [],
        UrlBuilder                   $urlBuilder = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $data,
            $imagesConfigFactory,
            $galleryImagesConfig,
            $urlBuilder
        );
        $this->galleryImagesConfig = $galleryImagesConfig;
        $this->galleryImagesConfigFactory = $imagesConfigFactory ?: ObjectManager::getInstance()
            ->get(ImagesConfigFactoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_product_view_gallery' . $this->getProduct()->getId());
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    public function getGalleryImages(): Collection
    {
        $product = $this->getProduct();
        $images = $product->getMediaGalleryImages();
        if (!$images instanceof Collection) {
            return $images;
        }

        foreach ($images as $image) {
            $galleryImagesConfig = $this->getGalleryImagesConfig()->getItems();
            foreach ($galleryImagesConfig as $imageConfig) {
                $image->setData(
                    $imageConfig->getData('data_object_key'),
                    $image->getUrl()
                );
            }
        }

        return $images;
    }

    /**
     * Returns image gallery config object
     *
     * @return Collection
     */
    private function getGalleryImagesConfig(): Collection
    {
        if (false === $this->hasData('gallery_images_config')) {
            $galleryImageConfig = $this->galleryImagesConfigFactory->create($this->galleryImagesConfig);
            $this->setData('gallery_images_config', $galleryImageConfig);
        }

        return $this->getData('gallery_images_config');
    }
}
