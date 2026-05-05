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

namespace Branch8\MarketplaceProduct\Plugin\Webkul\Marketplace\Controller\Product;

use Branch8\MarketplaceProduct\Model\Product\BuildProductLinks;
use Branch8\MarketplaceProduct\Model\Product\BuildCustomOptions;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\BuildBundleProduct;
use Branch8\MarketplaceProduct\Model\Product\BuildDownloadableProductLinks;
use Branch8\MarketplaceProduct\Model\Product\BuildGroupedProductLinks;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data;

/**
 * Webkul Marketplace Product Builder Controller Class.
 */
class Builder
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var MarketplaceStagingHelper
     */
    protected $marketplaceStagingHelper;

    /**
     * @var BuildProductLinks
     */
    protected $buildProductLinks;

    /**
     * @var BuildDownloadableProductLinks
     */
    private BuildDownloadableProductLinks $buildDownloadableProductLinks;

    /**
     * @var BuildConfigurableProduct
     */
    private BuildConfigurableProduct $buildConfigurableProduct;

    /**
     * @var BuildGroupedProductLinks
     */
    private BuildGroupedProductLinks $buildGroupedProductLinks;

    /**
     * @var BuildBundleProduct
     */
    private BuildBundleProduct $buildBundleProduct;

    /**
     * @var BuildCustomOptions
     */
    private BuildCustomOptions $buildCustomOptions;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param Data $helper
     * @param LoggerInterface $loggerInterface
     * @param MarketplaceStagingHelper $marketplaceStagingHelper
     * @param BuildProductLinks $buildProductLinks
     * @param BuildDownloadableProductLinks $buildDownloadableProductLinks
     * @param BuildConfigurableProduct $buildConfigurableProduct
     * @param BuildGroupedProductLinks $buildGroupedProductLinks
     * @param BuildBundleProduct $buildBundleProduct
     * @param BuildCustomOptions $buildCustomOptions
     * @param CollectionFactory $collectionFactory
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry,
        \Webkul\Marketplace\Helper\Data $helper,
        \Psr\Log\LoggerInterface $loggerInterface,
        MarketplaceStagingHelper $marketplaceStagingHelper,
        BuildProductLinks $buildProductLinks,
        BuildDownloadableProductLinks $buildDownloadableProductLinks,
        BuildConfigurableProduct $buildConfigurableProduct,
        BuildGroupedProductLinks $buildGroupedProductLinks,
        BuildBundleProduct $buildBundleProduct,
        BuildCustomOptions $buildCustomOptions,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        Json $serializer = null
    ) {
        $this->_productFactory = $productFactory;
        $this->_logger = $loggerInterface;
        $this->_helper = $helper;
        $this->_registry = $registry;
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->buildProductLinks = $buildProductLinks;
        $this->buildDownloadableProductLinks = $buildDownloadableProductLinks;
        $this->buildConfigurableProduct = $buildConfigurableProduct;
        $this->buildGroupedProductLinks = $buildGroupedProductLinks;
        $this->buildBundleProduct = $buildBundleProduct;
        $this->buildCustomOptions = $buildCustomOptions;
        $this->_collectionFactory = $collectionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * Build product based on requestData.
     *
     * @param object $requestData
     * @param int $store
     *
     * @return \Magento\Catalog\Model\Product $mageProduct
     */
    public function aroundBuild($subject, \Closure $proceed, $requestData, $store = 0)
    {
        if (!empty($requestData['id'])) {
            $mageProductId = (int) $requestData['id'];
        } else {
            $mageProductId = '';
        }
        if (!empty($requestData['temp_id'])) {
            $tempId = (int) $requestData['temp_id'];
        } else {
            $tempId = '';
        }
        /** @var $mageProduct \Magento\Catalog\Model\Product */
        $mageProduct = $this->_productFactory->create();
        if (!empty($requestData['set'])) {
            $mageProduct->setAttributeSetId($requestData['set']);
        }
        if (!empty($requestData['type'])) {
            $mageProduct->setTypeId($requestData['type']);
        }
        $mageProduct->setStoreId($store);
        if ($mageProductId || $tempId) {
            try {
                $isPartner = $this->_helper->isSeller();
                $flag = false;
                if ($isPartner == 1 && $mageProductId) {
                    $rightseller = $this->_helper->isRightSeller($mageProductId);
                    if ($rightseller) {
                        $flag = true;
                    }
                } else {
                    $flag = true;
                }
                if ($flag) {
                    if ($mageProductId) $mageProduct->load($mageProductId);
                    if ($mageProductId) {
                        $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryByProductId($mageProductId);
                    } else {
                        $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryById($tempId);
                    }
                    if ($productTemp) {
                        $productType = $mageProduct->getTypeId();
                        $productTemp = $this->serializer->unserialize($productTemp->getInformation());
                        if (isset($productTemp['type'])) {
                            $mageProduct->setData('type_id', $productTemp['type']);
                            $productType = $productTemp['type'];
                        }
                        if (isset($productTemp['set'])) {
                            $mageProduct->setData('attribute_set_id', $productTemp['set']);
                        }
                        if (isset($productTemp['status'])) {
                            $mageProduct->setData('status', $productTemp['status']);
                        }
                        foreach ($productTemp['product'] as $key => $value) {
                            $mageProduct->setData($key, $value);
                            if ($key == 'tier_price') {
                                $mageProduct->setTierPrice($value);
                            }
                            if ($key == 'media_gallery') {
                                $images = $this->_collectionFactory->create();
                                foreach ($value['images'] as $image) {
                                    if (!empty($image['disabled'])
                                        || !empty($image['removed'])
                                    ) {
                                        continue;
                                    }
                                    $file = $image['file'] ?? '';
                                    if ($file && strrpos((string)$file, '.tmp') == strlen($file) - 4) {
                                        $file = substr($file, 0, strlen($file) - 4);
                                    }
                                    $image = [
                                        'position' => $image['position'] ?? null,
                                        'media_type' => $image['media_type'] ?? null,
                                        'video_provider' => $image['video_provider'] ?? null,
                                        'file' => $file,
                                        'value_id' => $image['value_id']?? null,
                                        'label' => $image['label'] ?? null,
                                        'disabled' => $image['disabled'] ?? null,
                                        'removed' => $image['removed'] ?? null,
                                        'video_url' => $image['video_url'] ?? null,
                                        'video_title' => $image['video_title'] ?? null,
                                        'video_description' => $image['video_description'] ?? null,
                                        'video_metadata' => $image['video_metadata'] ?? null,
                                        'image' => $image['image']?? null,
                                    ];
                                    $images->addItem(new \Magento\Framework\DataObject($image));
                                }
                                if ($images->count()) {
                                    $mageProduct->setData('media_gallery_images', $images);
                                }
                            }
                            if ($key == 'image' || $key == 'small_image' || $key == 'thumbnail' || $key == 'dpa_image' || $key == 'swatch_image') {
                                if ($value && strrpos((string)$value, '.tmp') == strlen($value) - 4) {
                                    $value = substr($value, 0, strlen($value) - 4);
                                }
                                $mageProduct->setData($key, $value);
                            }
                            if ($key == 'quantity_and_stock_status' && !isset($value['qty'])) {
                                $value['qty'] = 0;
                                $mageProduct->setData($key, $value);
                            }
                            if ($key === BuildCustomOptions::KEY_CUSTOM_OPTIONS && !empty($value)) {
                                $mageProduct->setData('draft_content', true);
                                // Add changes related to custom options product
                                $this->buildCustomOptions->execute($mageProduct, $value);
                            }
                        }

                        if(isset($productTemp['links'])) {
                            $this->buildProductLinks->execute($mageProduct, $productTemp['links']);
                        }

                        if (isset($productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_LINK])) {
                            // Add changes related to downloadable product
                            $this->buildDownloadableProductLinks->execute($mageProduct, $productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_LINK], BuildDownloadableProductLinks::DOWNLOADABLE_LINK);
                        }
                        if (isset($productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE])) {
                            // Add changes related to downloadable product
                            $this->buildDownloadableProductLinks->execute($mageProduct, $productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE], BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE);
                        }

                        if ($productType === BundleType::TYPE_CODE && isset($productTemp[BuildBundleProduct::BUNDLE_OPTIONS])) {
                            // Add changes related to bundle product
                            $bundleData[BuildBundleProduct::BUNDLE_OPTIONS] = $productTemp[BuildBundleProduct::BUNDLE_OPTIONS];
                            $bundleData[BuildBundleProduct::BUNDLE_SELECTIONS] = $productTemp[BuildBundleProduct::BUNDLE_SELECTIONS];
                            $bundleData[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS] = $productTemp[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS];
                            $this->buildBundleProduct->execute($mageProduct, $bundleData);
                        }

                        if ($productType === Grouped::TYPE_CODE && isset($productTemp[BuildGroupedProductLinks::KEY_GROUPED_TYPE])) {
                            // Add changes related to product grouped link
                            $this->buildGroupedProductLinks->execute($mageProduct, $productTemp[BuildGroupedProductLinks::KEY_GROUPED_TYPE]);
                        }

                        /*if (isset($productTemp['attribute_selected'])) {
                            $mageProduct->setData('attribute_selected', $productTemp['attribute_selected']);
                        }*/
                    }
                }
            } catch (\Exception $e) {
                $this->_helper->logDataInLogger(
                    "Controller_Product_Builder execute : ".$e->getMessage()
                );
                $this->_logger->critical($e);
            }
        }
        if (!$this->_registry->registry('product')) {
            $this->_registry->register('product', $mageProduct);
        }
        if (!$this->_registry->registry('current_product')) {
            $this->_registry->register('current_product', $mageProduct);
        }
        return $mageProduct;
    }
}
