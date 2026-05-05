<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Model\Product;

use Magento\Backend\App\Action\Context;
use Magento\Bundle\Api\Data\OptionInterfaceFactory as OptionFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Downloadable\Api\Data\SampleInterfaceFactory as SampleFactory;
use Branch8\MarketplaceStaging\Controller\Product\Builder as ProductBuilder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product\TypeTransitionManager;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\VersionManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Hydrator implements HydratorInterface
{
    /**
     * @var Helper
     */
    protected $initializationHelper;

    /**
     * @var ProductBuilder
     */
    protected $productBuilder;

    /**
     * @var TypeTransitionManager
     */
    protected $productTypeManager;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Retriever
     */
    protected $entityRetriever;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\VariationHandler
     */
    protected $_variationHandler;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductCustomOptionInterfaceFactory
     */
    protected $customOptionFactory;

    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Bundle\Api\Data\LinkInterfaceFactory
     */
    protected $linkBundleFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var SampleFactory
     */
    protected $sampleFactory;

    /**
     * @var LinkFactory
     */
    protected $linkFactory;

    /**
     * @var \Magento\Downloadable\Model\Sample\Builder
     */
    protected $sampleBuilder;

    /**
     * @var \Magento\Downloadable\Model\Link\Builder
     */
    protected $linkBuilder;

    /**
     * @param Context $context
     * @param Helper $initializationHelper
     * @param ProductBuilder $productBuilder
     * @param TypeTransitionManager $productTypeManager
     * @param VersionManager $versionManager
     * @param UpdateRepositoryInterface $updateRepository
     * @param Retriever $entityRetriever
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ConfigurableProduct\Model\Product\VariationHandler $variationHandler
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeConfigurable
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param OptionFactory $optionFactory
     * @param \Magento\Bundle\Api\Data\LinkInterfaceFactory $linkBundleFactory
     * @param ProductRepository $productRepository
     * @param ProductCustomOptionInterfaceFactory $customOptionFactory
     * @param \Magento\Downloadable\Model\Link\Builder|null $linkBuilder
     * @param LinkFactory|null $linkFactory
     * @param \Magento\Downloadable\Model\Sample\Builder|null $sampleBuilder
     * @param SampleFactory|null $sampleFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context                                                      $context,
        Helper                                                       $initializationHelper,
        ProductBuilder                                               $productBuilder,
        TypeTransitionManager                                        $productTypeManager,
        VersionManager                                               $versionManager,
        UpdateRepositoryInterface                                    $updateRepository,
        Retriever                                                    $entityRetriever,
        \Magento\Framework\Json\Helper\Data                          $jsonHelper,
        \Magento\ConfigurableProduct\Model\Product\VariationHandler  $variationHandler,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeConfigurable,
        \Magento\Store\Model\StoreManagerInterface                   $storeManager,
        OptionFactory                                                $optionFactory,
        \Magento\Bundle\Api\Data\LinkInterfaceFactory                $linkBundleFactory,
        ProductRepository                                            $productRepository,
        ProductCustomOptionInterfaceFactory                          $customOptionFactory,
        \Magento\Downloadable\Model\Link\Builder                     $linkBuilder = null,
        LinkFactory                                                  $linkFactory = null,
        \Magento\Downloadable\Model\Sample\Builder                   $sampleBuilder = null,
        SampleFactory                                                $sampleFactory = null
    ) {
        $this->context = $context;
        $this->initializationHelper = $initializationHelper;
        $this->productBuilder = $productBuilder;
        $this->productTypeManager = $productTypeManager;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->entityRetriever = $entityRetriever;
        $this->_jsonHelper = $jsonHelper;
        $this->_variationHandler = $variationHandler;
        $this->_catalogProductTypeConfigurable = $productTypeConfigurable;
        $this->_storeManager = $storeManager;
        $this->optionFactory = $optionFactory;
        $this->linkBundleFactory = $linkBundleFactory;
        $this->productRepository = $productRepository;
        $this->customOptionFactory = $customOptionFactory;
        $this->linkBuilder = $linkBuilder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Model\Link\Builder::class);
        $this->linkFactory = $linkFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(LinkFactory::class);
        $this->sampleBuilder = $sampleBuilder ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Model\Sample\Builder::class);
        $this->sampleFactory = $sampleFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(SampleFactory::class);
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        $product = $this->initializationHelper->initialize(
            $this->productBuilder->build($data)
        );
        if (isset($data['product']['status'])) {
            $product->setStatus($data['product']['status']);
        }

        if (isset($data['product']['branch8_certifications_post'])) {
            $product->setData('branch8_certifications_post', $data['product']['branch8_certifications_post']);
        }

        if ($this->_storeManager->isSingleStoreMode() || !$product->getWebsiteIds()) {
            $product->setWebsiteIds([$this->getWebsiteId()]);
        }
        /*for bundle products start*/

        $product = $this->initBundle($product, $data);

        /*for bundle products end*/

        /*for downloadable products start*/

        $product = $this->buildDownloadableProduct($product, $data);

        /*for downloadable products end*/

        /*for configurable products start*/

        $associatedProductIds = [];

        $resultData = $this->buildConfigurableProduct($product, $data);

        $product = $resultData['catalogProduct'];
        $associatedProductIds = $resultData['associatedProductIds'];

        //$this->productTypeManager->processProduct($product);

        if (isset($data['product'][$product->getIdFieldName()])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The product was unable to be saved. Please try again.')
            );
        }

        $startTime = null;
        $endTime = null;
        if (isset($data['product']['is_new']) && $data['product']['is_new']) {
            $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
            $update = $this->updateRepository->get($currentVersionId);
            $startTime = $update->getStartTime();
            $endTime = $update->getEndTime();
        }
        $product->setNewsFromDate($startTime);
        $product->setNewsToDate($endTime);
        $this->handleImageRemoveError($data, $product->getId());

        return $product;
    }

    /**
     * Notify customer when image was not deleted in specific case.
     * TODO: temporary workaround must be eliminated in MAGETWO-45306
     *
     * @param array $postData
     * @param int $productId
     * @return void
     */
    private function handleImageRemoveError($postData, $productId)
    {
        if (isset($postData['product']['media_gallery']['images'])) {
            $removedImagesAmount = 0;
            foreach ($postData['product']['media_gallery']['images'] as $image) {
                if (!empty($image['removed'])) {
                    $removedImagesAmount++;
                }
            }
            if ($removedImagesAmount) {
                $expectedImagesAmount = count($postData['product']['media_gallery']['images']) - $removedImagesAmount;

                $product = $this->entityRetriever->getEntity($productId);
                if ($expectedImagesAmount != count($product->getMediaGallery('images'))) {
                    $this->context->getMessageManager()->addNotice(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
        }
    }

    /**
     * Set Downloadable Data in Product Model.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param array $wholedata
     *
     * @return \Magento\Catalog\Model\Product
     */
    private function buildDownloadableProduct($catalogProduct, $wholedata)
    {
        if (!empty($wholedata['downloadable']) && $downloadableParamData = $wholedata['downloadable']) {
            $downloadableParamData = $links = $this->getDownloadableParamData($downloadableParamData);
            $catalogProduct->setDownloadableData($downloadableParamData);

            $extension = $catalogProduct->getExtensionAttributes();

            if (isset($downloadableParamData['link']) && is_array($downloadableParamData['link'])) {
                $links = [];
                $links = $this->getDownloabaleLinkData($downloadableParamData, $links);
                $extension->setDownloadableProductLinks($links);
            }
            if (isset($downloadableParamData['sample']) && is_array($downloadableParamData['sample'])) {
                $samples = [];
                $samples = $this->getDownloabaleSampleData($downloadableParamData, $samples);
                $extension->setDownloadableProductSamples($samples);
            }
            $catalogProduct->setExtensionAttributes($extension);
            if ($catalogProduct->getLinksPurchasedSeparately()) {
                $catalogProduct->setTypeHasRequiredOptions(true)->setRequiredOptions(true);
            } else {
                $catalogProduct->setTypeHasRequiredOptions(false)->setRequiredOptions(false);
            }
        }
        return $catalogProduct;
    }

    /**
     * Set Downloadable Data in Product Model.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param array $wholedata
     *
     * @return mixed
     */
    private function buildConfigurableProduct($catalogProduct, $wholedata)
    {
        $associatedProductIds = [];
        if (!empty($wholedata['attributes'])) {
            $requestProductData = $wholedata['product'];
            $attributes = $wholedata['attributes'];
            $setId = $wholedata['set'] ?? $requestProductData['attribute_set_id'] ?? null;
            $catalogProduct->setAttributeSetId($setId);
            $this->_catalogProductTypeConfigurable->setUsedProductAttributeIds(
                $attributes,
                $catalogProduct
            );

            $extensionAttributes = $catalogProduct->getExtensionAttributes();

            $catalogProduct->setNewVariationsAttributeSetId($setId);
            $configurableOptions = [];

            $extensionAttributes->setConfigurableProductOptions($configurableOptions);

            if (!empty($wholedata['associated_product_ids'])) {
                $associatedProductIds = $wholedata['associated_product_ids'];
            }
            // Get variationsMatrix
            $variationsMatrix = [];
            if (!empty($wholedata['variations-matrix'])) {
                foreach ($wholedata['variations-matrix'] as $key => $value) {
                    if (empty($value['weight'])) {
                        if (!empty($wholedata['product']['weight'])) {
                            $productWeight = $wholedata['product']['weight'];
                            $wholedata['variations-matrix'][$key]['weight'] = $productWeight;
                        } else {
                            $wholedata['variations-matrix'][$key]['product_has_weight'] = 0;
                        }
                    }
                }
                $variationsMatrix = $wholedata['variations-matrix'];
            }

            if ($associatedProductIds || $variationsMatrix) {
                $this->_variationHandler->prepareAttributeSet($catalogProduct);
            }

            if (!empty($variationsMatrix)) {
                $generatedProductIds = $this->_variationHandler->generateSimpleProducts(
                    $catalogProduct,
                    $variationsMatrix
                );
                $associatedProductIds = array_merge($associatedProductIds, $generatedProductIds);
            }
            $extensionAttributes->setConfigurableProductLinks(array_filter($associatedProductIds));
            if (!isset($wholedata['affect_configurable_product_attributes'])) {
                $wholedata['affect_configurable_product_attributes'] = '';
            }
            $catalogProduct->setCanSaveConfigurableAttributes(
                (bool) $wholedata['affect_configurable_product_attributes']
            );

            $catalogProduct->setExtensionAttributes($extensionAttributes);
        }
        return ["catalogProduct" => $catalogProduct, "associatedProductIds" => $associatedProductIds];
    }

    /**
     * Get downloadable param data
     *
     * @param array $downloadableParamData
     * @return array
     */
    private function getDownloadableParamData($downloadableParamData)
    {
        if (isset($downloadableParamData['link']) && is_array($downloadableParamData['link'])) {
            foreach ($downloadableParamData['link'] as $key => $linkData) {
                if ($linkData['link_id'] == 0) {
                    $linkData['link_id'] = null;
                }
                $linkData['file'] = $this->_jsonHelper->jsonDecode($linkData['file']);
                $linkData['sample']['file'] = $this->_jsonHelper->jsonDecode($linkData['sample']['file']);
                $downloadableParamData['link'][$key]['link_id'] = $linkData['link_id'];
                $downloadableParamData['link'][$key]['file'] = $linkData['file'];
                $downloadableParamData['link'][$key]['sample']['file'] = $linkData['sample']['file'];
            }
        }
        if (isset($downloadableParamData['sample']) && is_array($downloadableParamData['sample'])) {
            foreach ($downloadableParamData['sample'] as $key => $sampleData) {
                if ($sampleData['sample_id'] == 0) {
                    $sampleData['sample_id'] = null;
                }
                $sampleData['file'] = $this->_jsonHelper->jsonDecode($sampleData['file']);
                $downloadableParamData['sample'][$key]['sample_id'] = $sampleData['sample_id'];
                $downloadableParamData['sample'][$key]['file'] = $sampleData['file'];
            }
        }
        return $downloadableParamData;
    }

    /**
     * Get Product Link Data from Post Downloadable Data.
     *
     * @param array $downloadableParamData
     * @param array $links
     *
     * @return array
     */
    private function getDownloabaleLinkData($downloadableParamData, $links)
    {
        foreach ($downloadableParamData['link'] as $linkData) {
            if (!$linkData || (isset($linkData['is_delete']) && $linkData['is_delete'])) {
                continue;
            } else {
                $links[] = $this->linkBuilder->setData(
                    $linkData
                )->build(
                    $this->linkFactory->create()
                );
            }
        }
        return $links;
    }

    /**
     * Get Product Sample Data from Post Downloadable Data.
     *
     * @param array $downloadableParamData
     * @param array $samples
     *
     * @return array
     */

    private function getDownloabaleSampleData($downloadableParamData, $samples)
    {
        foreach ($downloadableParamData['sample'] as $sampleData) {
            if (!$sampleData || (isset($sampleData['is_delete']) && (bool) $sampleData['is_delete'])) {
                continue;
            } else {
                $test =$this->sampleBuilder->setData(
                    $sampleData
                )->build(
                    $this->sampleFactory->create()
                );
                $samples[] = $this->sampleBuilder->setData(
                    $sampleData
                )->build(
                    $this->sampleFactory->create()
                );
            }
        }
        return $samples;
    }

    /**
     * Get website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        // give the current store id
        return $this->_storeManager->getStore(true)->getWebsite()->getId();
    }

    /**
     * Setting Bundle Items Data to product for further processing
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $data
     *
     * @return \Magento\Catalog\Model\Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function initBundle(
        \Magento\Catalog\Model\Product $product,
        $data
    ) {
        $compositeReadonly = $product->getCompositeReadonly();
        $result['bundle_selections'] = $result['bundle_options'] = [];
        if (isset($data['bundle_options']['bundle_options'])) {
            foreach ($data['bundle_options']['bundle_options'] as $key => $option) {
                if (empty($option['bundle_selections'])) {
                    continue;
                }
                $result['bundle_selections'][$key] = $option['bundle_selections'];
                unset($option['bundle_selections']);
                $result['bundle_options'][$key] = $option;
            }
            if ($result['bundle_selections'] && !$compositeReadonly) {
                $product->setBundleSelectionsData($result['bundle_selections']);
            }

            if ($result['bundle_options'] && !$compositeReadonly) {
                $product->setBundleOptionsData($result['bundle_options']);
            }

            $this->processBundleOptionsData($product);
            $this->processDynamicOptionsData($product);
        } elseif (!$compositeReadonly) {
            $extension = $product->getExtensionAttributes();
            $extension->setBundleProductOptions([]);
            $product->setExtensionAttributes($extension);
        }

        $affectProductSelections = isset($data['affect_bundle_product_selections']) && (bool)$data['affect_bundle_product_selections'];
        $product->setCanSaveBundleSelections($affectProductSelections && !$compositeReadonly);
        return $product;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processBundleOptionsData(\Magento\Catalog\Model\Product $product)
    {
        $bundleOptionsData = $product->getBundleOptionsData();
        if (!$bundleOptionsData) {
            return;
        }
        $options = [];
        foreach ($bundleOptionsData as $key => $optionData) {
            if (!empty($optionData['delete'])) {
                continue;
            }

            $option = $this->optionFactory->create(['data' => $optionData]);
            $option->setSku($product->getSku());

            $links = [];
            $bundleLinks = $product->getBundleSelectionsData();
            if (empty($bundleLinks[$key])) {
                continue;
            }

            foreach ($bundleLinks[$key] as $linkData) {
                if (!empty($linkData['delete'])) {
                    continue;
                }
                if (!empty($linkData['selection_id'])) {
                    $linkData['id'] = $linkData['selection_id'];
                }
                $links[] = $this->buildLink($product, $linkData);
            }
            $option->setProductLinks($links);
            $options[] = $option;
        }

        $extension = $product->getExtensionAttributes();
        $extension->setBundleProductOptions($options);
        $product->setExtensionAttributes($extension);
        return;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    protected function processDynamicOptionsData(\Magento\Catalog\Model\Product $product)
    {
        if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            return;
        }

        if ($product->getOptionsReadonly()) {
            return;
        }
        $product->setCanSaveCustomOptions(true);
        $customOptions = $product->getProductOptions();
        if (!$customOptions) {
            return;
        }
        foreach (array_keys($customOptions) as $key) {
            $customOptions[$key]['is_delete'] = 1;
        }
        $newOptions = $product->getOptions();
        foreach ($customOptions as $customOptionData) {
            if ((bool)$customOptionData['is_delete']) {
                continue;
            }
            $customOption = $this->customOptionFactory->create(['data' => $customOptionData]);
            $customOption->setProductSku($product->getSku());
            $newOptions[] = $customOption;
        }
        $product->setOptions($newOptions);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $linkData
     *
     * @return \Magento\Bundle\Api\Data\LinkInterface
     */
    private function buildLink(
        \Magento\Catalog\Model\Product $product,
        array $linkData
    ) {
        $link = $this->linkBundleFactory->create(['data' => $linkData]);

        if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            if (array_key_exists('selection_price_value', $linkData)) {
                $link->setPrice($linkData['selection_price_value']);
            }
            if (array_key_exists('selection_price_type', $linkData)) {
                $link->setPriceType($linkData['selection_price_type']);
            }
        }

        $linkProduct = $this->productRepository->getById($linkData['product_id']);
        $link->setSku($linkProduct->getSku());
        $link->setQty($linkData['selection_qty']);

        if (array_key_exists('selection_can_change_qty', $linkData)) {
            $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
        }

        return $link;
    }
}
