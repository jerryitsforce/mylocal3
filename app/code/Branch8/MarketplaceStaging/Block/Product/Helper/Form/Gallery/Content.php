<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceStaging\Block\Product\Helper\Form\Gallery;

use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

class Content extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $_mediaConfig;

    /**
     * @var \Magento\Framework\File\Size
     */
    protected $_fileSizeService;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoderInterface;

    /**
     * @var StagingLocator
     */
    private $locator;

    /**
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param \Magento\Framework\File\Size                $fileSize
     * @param \Magento\Framework\Json\EncoderInterface    $jsonEncoderInterface
     * @param StagingLocator                              $locator
     * @param array                                       $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\File\Size $fileSize,
        \Magento\Framework\Json\EncoderInterface $jsonEncoderInterface,
        \Magento\Framework\Registry $coreRegistry,
        StagingLocator $locator,
        array $data = []
    ) {
        $this->_mediaConfig = $mediaConfig;
        $this->_fileSizeService = $fileSize;
        $this->_jsonEncoderInterface = $jsonEncoderInterface;
        $this->locator = $locator;
        parent::__construct($context, $data);
    }

    /**
     * Get file size service
     *
     * @return \Magento\Framework\File\Size
     */
    public function getFileSizeService()
    {
        return $this->_fileSizeService;
    }

    /**
     * Retrieve product.
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->locator->getProduct();
    }

    /**
     * Get product image data.
     *
     * @return array
     */
    public function getProductImagesJson()
    {
        $productColl = $this->getProduct();
        if ($productColl) {
            $mediaGalleryImages = $productColl->getMediaGalleryImages();
            $productImages = [];
            if (count($mediaGalleryImages) > 0) {
                foreach ($mediaGalleryImages as &$mediaGalleryImage) {
                    $mediaGalleryImage['url'] = $this->_mediaConfig->getMediaUrl(
                        $mediaGalleryImage['file']
                    );
                    array_push($productImages, $mediaGalleryImage->getData());
                }

                return $this->_jsonEncoderInterface->encode($productImages);
            }

        }
        return '[]';
    }

    /**
     * Get product image types
     *
     * @return array
     */
    public function getProductImageTypes()
    {
        $productImageTypes = [];
        $productColl = $this->getProduct();
        foreach ($this->getProductMediaAttributes() as $attribute) {
            $productImageTypes[$attribute->getAttributeCode()] = [
                'code' => $attribute->getAttributeCode(),
                'value' => $productColl[$attribute->getAttributeCode()],
                'label' => $attribute->getFrontend()->getLabel(),
                'name' => 'product['.$attribute->getAttributeCode().']',
            ];
        }

        return $productImageTypes;
    }

    /**
     * Get media attribute
     *
     * @return array
     */
    public function getProductMediaAttributes()
    {
        $mediaAttributes = [];
        $allowedMediaAttributes = $this->getAllowedMediaAttributes();
        $productMediaAttributes = $this->getProduct()->getMediaAttributes();
        foreach ($productMediaAttributes as $attribute) {
            if (in_array($attribute->getAttributeCode(), $allowedMediaAttributes)) {
                $mediaAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $mediaAttributes;
    }

    /**
     * GetAllowedMediaAttributes returns the allowed media attributes
     *
     * @return array
     */
    public function getAllowedMediaAttributes()
    {
        return ['image', 'small_image', 'thumbnail', 'swatch_image', 'dpa_image'];
    }
}
