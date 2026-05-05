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
namespace Branch8\MarketplaceProduct\Plugin\Webkul\Marketplace\Block\Product;

use Branch8\MarketplaceProduct\Model\Product\BuildBundleProduct;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\BuildCustomOptions;
use Branch8\MarketplaceProduct\Model\Product\BuildDownloadableProductLinks;
use Branch8\MarketplaceProduct\Model\Product\BuildGroupedProductLinks;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Branch8\MarketplaceProduct\Model\Product\BuildProductLinks;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

/**
 * Marketplace Product helper
 */
class Create
{
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
     * @param MarketplaceStagingHelper $marketplaceStagingHelper
     * @param BuildProductLinks $buildProductLinks
     * @param BuildDownloadableProductLinks $buildDownloadableProductLinks
     * @param BuildConfigurableProduct $buildConfigurableProduct
     * @param BuildGroupedProductLinks $buildGroupedProductLinks
     * @param BuildBundleProduct $buildBundleProduct
     * @param BuildCustomOptions $buildCustomOptions
     * @param CollectionFactory $collectionFactory
     * @param Json|null $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
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
     * Get product
     *
     * @param [int] $id
     * @return product
     * @throws LocalizedException
     */
    public function afterGetProduct(\Webkul\Marketplace\Block\Product\Create $subject, $result, $id)
    {
        $tempId = (int) $subject->getRequest()->getParam('temp_id');
        $productTemp = null;
        if ($id) $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryByProductId($id);
        elseif ($tempId) $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryById($tempId);
        if ($productTemp) {
            $result->setData('is_draft', true);
            $configurableData = [
                ProductInterface::WEIGHT => $result->getWeight(),
                ProductInterface::ATTRIBUTE_SET_ID => $result->getAttributeSetId()
            ];
            $productType = $result->getTypeId();
            $productTemp = $this->serializer->unserialize($productTemp->getInformation());
            if (isset($productTemp['type'])) {
                $result->setData('type_id', $productTemp['type']);
                $productType = $productTemp['type'];
            }
            if (isset($productTemp['set'])) {
                $result->setData('attribute_set_id', $productTemp['set']);
            }
            if (isset($productTemp['status'])) {
                $result->setData('status', $productTemp['status']);
            }
            foreach ($productTemp['product'] as $key => $value) {
                $result->setData($key, $value);
                if ($key == 'tier_price') {
                    $result->setTierPrice($value);
                }
                if ($key == 'media_gallery') {
                    $images = $this->_collectionFactory->create();
                    foreach ($value['images'] as $image) {
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
                        $result->setData('media_gallery_images', $images);
                    }
                }
                if ($key == 'image' || $key == 'small_image' || $key == 'thumbnail' || $key == 'dpa_image') {
                    if ($value && strrpos((string)$value, '.tmp') == strlen($value) - 4) {
                        $value = substr($value, 0, strlen($value) - 4);
                    }
                    $result->setData($key, $value);
                }
                if ($key == 'quantity_and_stock_status' && !isset($value['qty'])) {
                    $value['qty'] = 0;
                    $result->setData($key, $value);
                }
                if ($key === BuildCustomOptions::KEY_CUSTOM_OPTIONS && !empty($value)) {
                    // Add changes related to custom options product
                    $this->buildCustomOptions->execute($result, $value);
                }
            }

            if(isset($productTemp['links'])) {
                $this->buildProductLinks->execute($result, $productTemp['links']);
            }

            if (isset($productTemp[BuildCustomOptions::KEY_CUSTOM_OPTIONS])) {
                // Add changes related to custom options product
                $this->buildCustomOptions->execute($result, $productTemp[BuildCustomOptions::KEY_CUSTOM_OPTIONS]);
            }

            if (isset($productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_LINK])) {
                // Add changes related to downloadable product
                $this->buildDownloadableProductLinks->execute($result, $productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_LINK], BuildDownloadableProductLinks::DOWNLOADABLE_LINK);
            }
            if (isset($productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE])) {
                // Add changes related to downloadable product
                $this->buildDownloadableProductLinks->execute($result, $productTemp[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE], BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE);
            }

            if ($productType === BundleType::TYPE_CODE && isset($productTemp[BuildBundleProduct::BUNDLE_OPTIONS])) {
                // Add changes related to bundle product
                $bundleData[BuildBundleProduct::BUNDLE_OPTIONS] = $productTemp[BuildBundleProduct::BUNDLE_OPTIONS];
                $bundleData[BuildBundleProduct::BUNDLE_SELECTIONS] = $productTemp[BuildBundleProduct::BUNDLE_SELECTIONS];
                $bundleData[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS] = $productTemp[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS];
                $this->buildBundleProduct->execute($result, $bundleData);
            }

            if ($productType === Grouped::TYPE_CODE && isset($productTemp[BuildGroupedProductLinks::KEY_GROUPED_TYPE])) {
                // Add changes related to product grouped link
                $this->buildGroupedProductLinks->execute($result, $productTemp[BuildGroupedProductLinks::KEY_GROUPED_TYPE]);
            }

            /*if (isset($productTemp['attribute_selected'])) {
                $result->setData('attribute_selected', $productTemp['attribute_selected']);
            }*/
        }
        return $result;
    }
}
