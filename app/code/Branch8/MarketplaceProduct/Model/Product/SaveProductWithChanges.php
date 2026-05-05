<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Model\Config;
use Branch8\MarketplaceProduct\Model\Config as B8MpConfig;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Downloadable\Model\Product\Type;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Magento\Framework\Filesystem;
use Webkul\OptionsWithStockAndImages\Helper\Data as OptionsWithStockAndImagesHelper;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

class SaveProductWithChanges
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::SaveProductWithChanges';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var SaveSourceItem
     */
    private SaveSourceItem $saveSourceItem;

    /**
     * @var BuildProductLinks
     */
    private BuildProductLinks $buildProductLinks;

    /**
     * @var AddImageToMediaGallery
     */
    private AddImageToMediaGallery $addImageToMediaGallery;

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
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var B8MpConfig
     */
    protected B8MpConfig $b8MpConfig;

    /**
     * @var MpProductFactory
     */
    protected $mpProductFactory;

    protected BuildVariation $buildVariation;
    /**
     * @var Salable
     */
    protected Salable $salable;

    /**
     * @var Filesystem\DirectoryList
     */
    public Filesystem\DirectoryList $directoryList;

    /**
     * @var WriteInterface
     */
    public $mediaDirectory;

    /**
     * @var OptionsWithStockAndImagesHelper
     */
    protected OptionsWithStockAndImagesHelper $optionsWithStockAndImagesHelper;

    /**
     * @var VariationsFactory
     */
    protected VariationsFactory $variationFactory;

    /**
     * @var VariationsFactory
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var UpdateLegacyStockStatus
     */
    private UpdateLegacyStockStatus $updateLegacyStockStatus;

    protected CategoryLinkManagementInterface $categoryLinkManagement;

    protected $mimeTypeExtensionMap = [
        'image/jpg' => 'jpg',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
    ];

    /**
     * SaveProductWithChanges constructor.
     *
     * @param LoggerInterface $logger
     * @param Config $config
     * @param DateTime $dateTime
     * @param SerializerInterface $serializer
     * @param SaveSourceItem $saveSourceItem
     * @param BuildProductLinks $buildProductLinks
     * @param AddImageToMediaGallery $addImageToMediaGallery
     * @param BuildDownloadableProductLinks $buildDownloadableProductLinks
     * @param BuildConfigurableProduct $buildConfigurableProduct
     * @param BuildGroupedProductLinks $buildGroupedProductLinks
     * @param BuildBundleProduct $buildBundleProduct
     * @param BuildCustomOptions $buildCustomOptions
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductRepositoryInterface $productRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param B8MpConfig $b8MpConfig
     * @param BuildVariation $buildVariation
     * @param MpProductFactory|null $mpProductFactory
     */
    public function __construct(
        LoggerInterface               $logger,
        Config                        $config,
        DateTime                      $dateTime,
        SerializerInterface           $serializer,
        SaveSourceItem                $saveSourceItem,
        BuildProductLinks             $buildProductLinks,
        AddImageToMediaGallery        $addImageToMediaGallery,
        BuildDownloadableProductLinks $buildDownloadableProductLinks,
        BuildConfigurableProduct      $buildConfigurableProduct,
        BuildGroupedProductLinks      $buildGroupedProductLinks,
        BuildBundleProduct            $buildBundleProduct,
        BuildCustomOptions            $buildCustomOptions,
        MarketplaceProductManagement  $marketplaceProductManagement,
        ProductRepositoryInterface    $productRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        B8MpConfig                    $b8MpConfig,
        BuildVariation                $buildVariation,
        Salable                       $salable,
        Filesystem\DirectoryList      $directoryList,
        Filesystem                    $filesystem,
        OptionsWithStockAndImagesHelper $optionsWithStockAndImagesHelper,
        VariationsFactory             $variationFactory,
        StoreManagerInterface         $storeManager,
        UpdateLegacyStockStatus       $updateLegacyStockStatus,
        CategoryLinkManagementInterface $categoryLinkManagement,
        MpProductFactory              $mpProductFactory = null
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer;
        $this->saveSourceItem = $saveSourceItem;
        $this->buildProductLinks = $buildProductLinks;
        $this->addImageToMediaGallery = $addImageToMediaGallery;
        $this->buildDownloadableProductLinks = $buildDownloadableProductLinks;
        $this->buildConfigurableProduct = $buildConfigurableProduct;
        $this->buildGroupedProductLinks = $buildGroupedProductLinks;
        $this->buildBundleProduct = $buildBundleProduct;
        $this->buildCustomOptions = $buildCustomOptions;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productRepository = $productRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->b8MpConfig = $b8MpConfig;
        $this->buildVariation = $buildVariation;
        $this->salable = $salable;
        $this->directoryList = $directoryList;
        $this->optionsWithStockAndImagesHelper = $optionsWithStockAndImagesHelper;
        $this->variationFactory = $variationFactory;
        $this->storeManager = $storeManager;
        $this->updateLegacyStockStatus = $updateLegacyStockStatus;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mpProductFactory = $mpProductFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpProductFactory::class);
    }

    /**
     * Apply changed data to product.
     *
     * @param ProductInterface $product
     * @param int $sellerId
     * @param array|null $changedData
     * @param bool $savedExcludeAttributes
     *
     * @return DataObject
     *
     * @throws LocalizedException
     */
    public function execute(
        ProductInterface $product,
        int              $sellerId,
        array            $changedData = null,
        bool             $savedExcludeAttributes = false
    ): DataObject {
        try {
            $result = new DataObject();
            $productId = (int)$product->getId();
            $productType = $product->getTypeId();
            $isNew = false;
            if (empty($changedData)) {
                $logEntry = $this->getProductLogEntryByProductId->execute($productId);
                $additionalInfo = (string)$logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                $changedData = $this->serializer->unserialize($additionalInfo);
                if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                    $isNew = true;
                }
            }

            $count = count($changedData);
            $hasChanged = false;
            $hasChangedWeight = false;
            $excludeAttributes = $this->config->getExcludeAttributes();
            $excludeAttributes[] = BuildConfigurableProduct::VARIATIONS_MATRIX;

            $productLinkData = [];
            $productGroupedLinkData = [];
            $configurableData = [
                ProductInterface::WEIGHT => $product->getWeight(),
                ProductInterface::ATTRIBUTE_SET_ID => $product->getAttributeSetId()
            ];
            $bundleData = [];
            $swatches = [];
            $coVariations = [];
            $removedCategories = [];
            $removedMainCategoryId = null;
            foreach ($changedData as $attribute => $changed) {
                if (!isset($changed['after'])) {
                    continue;
                }
                if ($isNew && in_array($attribute, [BuildVariation::KEY_VARIATION, 'options', 'stock_data'])) {
                    continue;
                }
                $value = $changed['after'];

                // START: Collect necessary data to save configurable product
                if ($attribute === ProductInterface::WEIGHT) {
                    $configurableData[ProductInterface::WEIGHT] = $value;
                }
                if ($attribute === ProductInterface::ATTRIBUTE_SET_ID) {
                    $configurableData[ProductInterface::ATTRIBUTE_SET_ID] = $value;
                }
                // END: Collect necessary data to save configurable product

                // Update product weight if it is changed
                if ($attribute === 'weight' && $product->getWeight() != $value) {
                    $product->setWeight($value);
                    $hasChanged = true;
                    $hasChangedWeight = true;
                }

                $shouldSave = (!$savedExcludeAttributes && in_array($attribute, $excludeAttributes)) ||
                    ($savedExcludeAttributes && !in_array($attribute, $excludeAttributes));
                if ($shouldSave) {
                    if (array_key_exists($attribute, $changedData)) {
                        $count--;
                    }
                    if ($attribute === 'quantity_and_stock_status') { // Add changes related to stock data
                        $this->saveSourceItem->execute($product, $value);
                    } elseif ($attribute === 'image_gallery' && !$isNew) { // Add changes related to image gallery
                        $arrayNew = [];
                        foreach ($value as $k => $image) {
                            if (isset($image['removed']) && $image['removed'] == '1' && !trim($image['value_id'])) {
                                unset($value[$k]);
                            }
                            if ((isset($image['removed']) && $image['removed'] == '1') || trim($image['value_id'])) {
                                continue;
                            }
                            $arrayNew[] = $image;
                            unset($value[$k]);
                        }
                        if (!empty($arrayNew)) {
                            $this->addImageToMediaGallery->execute($product, $arrayNew);
                            $imageNews = $product->getMediaGallery('images');
                            foreach ($imageNews as $imageNew) {
                                foreach ($arrayNew as $imageNew1) {
                                    $imageFileNew = $imageNew['file'];
                                    $imageFileNew1 = $imageNew1['file'];
                                    $imageFileNew1 = str_replace(' ', '_', $imageFileNew1);
                                    $array = explode('/', $imageFileNew);
                                    $newFileName = end($array);
                                    $array1 = explode('/', $imageFileNew1);
                                    $newFileName1 = end($array1);
                                    $isNew = false;
                                    if (str_contains($imageFileNew1, $product->getSku())) {
                                        if ($newFileName == $newFileName1) {
                                            $isNew = true;
                                        }
                                    }
                                    if ($newFileName == $newFileName1 || $isNew) {
                                        $imageNew['position'] = $imageNew1['position'];
                                        if (isset($imageNew['content']['data']['name'])) {
                                            $ext = $this->mimeTypeExtensionMap[$imageNew['content']['data']['type']] ?? '';
                                            if ($ext && !str_contains($imageNew['content']['data']['name'], '.' . $ext)) {
                                                $imageNew['content']['data']['name'] .= '.' . $ext;
                                            }
                                            $imageNew['disabled'] = $imageNew1['disabled'];
                                        }
                                        $value[] = $imageNew;
                                    }
                                }
                            }
                        }
                        $product->setData('media_gallery', ['images' => $value]);
                        $hasChanged = true;
                    } elseif ($attribute == BuildCustomOptions::KEY_CUSTOM_OPTIONS && !empty($value)) {
                        // Add changes related to custom options product
                        $this->buildCustomOptions->execute($product, $value);
                        foreach ($product->getOptions() as $customOptionData) {
                            $optType = $customOptionData->getType();
                            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                                $swatches[] = [
                                    'title' => $customOptionData->getTitle(),
                                    'is_swatch' => 1
                                ];
                            }
                            $hasChanged = true;
                        }
                        if (!empty($coVariations) && !empty($swatches)) {
                            foreach ($coVariations as $option) {
                                $optionData = array_map('trim', explode('|', $option));
                                $sku = $optionData[4];
                                $isSync = (int) $optionData[5];
                                $weight = $optionData[1];
                                $images = str_replace(';',',',$optionData[2]);
                                $stock = $optionData[3];
                                // New price and cost
                                $followSimpleSkuCostSetting = isset($optionData[6]) ? (int)$optionData[6] : 0;
                                $costSetting = isset($optionData[7]) ? (string)$optionData[7] : '';
                                $commissionPercent = isset($optionData[8]) ? (float)$optionData[8] : 0;
                                $cost = isset($optionData[9]) ? (float)$optionData[9] : 0;
                                $followSimpleSkuPriceSetting = isset($optionData[10]) ? (int)$optionData[10] : 0;
                                $price = isset($optionData[11]) ? (float)$optionData[11] : 0;
                                if ($followSimpleSkuPriceSetting == 1) {
                                    $price = $product->getSpecialPrice() ? $product->getSpecialPrice() : $product->getPrice();
                                }
                                if ($followSimpleSkuCostSetting == 0) {
                                    if (!empty($costSetting)) {
                                        list($cost, $commissionPercent) = $this->salable->calculateProductCost(
                                            $price,
                                            $costSetting,
                                            $commissionPercent,
                                            $cost
                                        );
                                    }
                                }
                                // End new price and cost
                                if ($isSync) {
                                    $syncProduct = $this->productRepository->get($sku);
                                    if ($syncProduct->getId() && $syncProduct->getTypeId() == 'simple') {
                                        $qty = $this->salable->getQtyBySku($sku);
                                        $images = $syncProduct->getMediaGalleryEntries();
                                        $dataImages = [];
                                        foreach ($images as $image) {
                                            try {
                                                $this->saveFile($image->getFile());
                                                $dataImages[] = $image->getFile();
                                            } catch (\Exception $e) {
                                                continue;
                                            }
                                        }
                                        $weight = $syncProduct->getWeight();
                                        $stock = $qty;
                                        $images = implode(',',$dataImages);
                                    }
                                }
                                $variation[] = [
                                    'comb' => $optionData[0],
                                    'weight' => $weight,
                                    'image' => $images,
                                    'stock' => $stock,
                                    'sku' => $sku,
                                    'is_sync' => $isSync,
                                    'follow_simple_sku_cost_setting' => $followSimpleSkuCostSetting,
                                    'cost_setting' => $costSetting,
                                    'commission_percent' => $commissionPercent,
                                    'cost' => $cost,
                                    'follow_simple_sku_price_setting' => $followSimpleSkuPriceSetting,
                                    'price' => $price
                                ];
                            }
                            if (!empty($variation)) {
                                $product->setData('wk_manage_swatch', $swatches);
                                $product->setData('wk_manage_variation', $variation);
                                $hasChanged = true;
                            }
                        }
                    } elseif ($productType === Type::TYPE_DOWNLOADABLE && in_array($attribute, ['downloadable_link', 'downloadable_sample'])) {
                        // Add changes related to downloadable product
                        $this->buildDownloadableProductLinks->execute($product, $value, $attribute);
                        $hasChanged = true;
                    } elseif ($productType === Configurable::TYPE_CODE && in_array($attribute, BuildConfigurableProduct::getRequiredKeys())) {
                        // Collect changes data of configurable product
                        $configurableData[$attribute] = $value;
                    } elseif ($productType === BundleType::TYPE_CODE && in_array($attribute, BuildBundleProduct::getRequiredKeys())) {
                        // Collect changes data of bundle product
                        $bundleData[$attribute] = $value;
                    } elseif (in_array($attribute, $this->buildProductLinks->getProductLinkTypes())) {
                        // Collect changes data of product link (related, upsell and crosssell product)
                        $productLinkData[$attribute] = $value;
                    } elseif ($productType === Grouped::TYPE_CODE && $attribute == BuildGroupedProductLinks::KEY_GROUPED_TYPE) {
                        // Collect changes data of grouped product link
                        $productGroupedLinkData[$attribute] = $value;
                    } /*elseif ($attribute == 'attribute_selected') {
                        $sellerProductColl = $this->mpProductFactory->create()->getCollection()
                            ->addFieldToFilter('mage_pro_row_id', $product->getRowId())
                            ->addFieldToFilter('mageproduct_id', $productId)
                            ->setPageSize(1)
                            ->setCurPage(1)
                            ->getFirstItem();
                        if ($sellerProductColl->getId() && $value && is_array($value)) {
                            $attributeSelector = $this->b8MpConfig->getAttributeSelector();
                            foreach ($attributeSelector as $attributeSelectedDetail) {
                                if ($product->getData($attributeSelectedDetail) && !in_array($attributeSelectedDetail, $value)) {
                                    $product->setData($attributeSelectedDetail, '');
                                    $hasChanged = true;
                                }
                            }
                            $attributeSelected = implode(',', $value);
                            $sellerProductColl->setData('attribute_selected', $attributeSelected)->save();
                        }
                    }*/ elseif ($attribute === 'branch8_certifications_post') {
                        $product->setData($attribute, $value);
                        $hasChanged = true;
                    } elseif ($attribute == 'product_has_weight') {
                        $product->setData($attribute, $value);
                        $hasChanged = true;
                        $hasChangedWeight = true;
                    } elseif ($attribute == 'co_variation') {
                        $coVariations = array_map('trim', explode(',', $value));
                    } elseif ($attribute == 'main_category' || $attribute == 'flagstore_category') {
                        $before = $changed['before'] ?? null;
                        $removedCategories[] = $before;
                        if($attribute == 'main_category') {
                            $product->setData('removed_main_category_id', $before);
                        }
                        $product->setData($attribute, $value);
                        $hasChanged = true;
                    }
                    else {
                        $productData = $product->getData();
                        $productData[$attribute] = $value;
                        $product->setData($productData);
                        $hasChanged = true;
                    }
                }
            }

            if (count($configurableData) > 2) { // The array always have weight and attribute set id value
                // Add changes related to configurable product
                $builderOutput = $this->buildConfigurableProduct->execute($product, $configurableData);
                $hasChanged = $builderOutput->getData('status');
                $childProductIds = $builderOutput->getData('ids');
                if (!$hasChanged && !empty($childProductIds)) {
                    $this->saveMarketplaceProducts($childProductIds, $sellerId);
                }
                $result->setData('additional_data', [BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS => ['after' => $childProductIds]]);
            }

            if (!empty($bundleData)) {
                // Add changes related to bundle product
                $this->buildBundleProduct->execute($product, $bundleData);
                $hasChanged = true;
            }

            if (!empty($productLinkData)) {
                // Add changes related to product link (related, upsell and crosssell product)
                $this->buildProductLinks->execute($product, $productLinkData);
                $hasChanged = true;
            }

            if (!empty($productGroupedLinkData)) {
                // Add changes related to product grouped link
                $this->buildGroupedProductLinks->execute($product, $productGroupedLinkData);
                $hasChanged = true;
            }

            if ($hasChanged) {
                if ($product->getWeight()) {
                    $product->setProductHasWeight(true);
                }
                if ($hasChangedWeight) {
                    if ($product->getProductHasWeight() && $product->getTypeId() == 'virtual') {
                        $product->setTypeId('simple');
                    } elseif (!$product->getProductHasWeight() && $product->getTypeId() == 'simple') {
                        $product->setTypeId('virtual');
                    }
                }
                $product->setStoreId(Store::DEFAULT_STORE_ID);
                $product->setData('salable_qty', null);
                $this->productRepository->save($product);

                $dataStockStatus[$product->getSku()] = true;
                $this->updateLegacyStockStatus->execute($dataStockStatus);
            }
            if (array_key_exists(BuildVariation::KEY_VARIATION, $changedData) && $savedExcludeAttributes) {
                if (!empty($changedData[BuildVariation::KEY_VARIATION]['after'])) {
                    $productRowId = $product->getRowId();
                    $editVariation = $this->buildVariation->buildVariation($changedData[BuildVariation::KEY_VARIATION]['after']);
                    $this->buildVariation->saveWeight($editVariation, $productRowId);
                }
            }

            $result->setData('status', !$count);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Can not save the product changes for product ". $product->getSku() ." Error: " . $e->getMessage());
            $this->logger->critical($e);
            throw new LocalizedException(__('The product changes cannot be saved due to the following reasons: ' . $e->getMessage()));
        }
    }

    /**
     * Move file from tmp to wkosi directory
     *
     * @param string $fileName
     * @return void
     * @throws FileSystemException
     */
    public function saveFile($fileName)
    {
        $dirList = $this->directoryList->getPath('media');
        $baseTmpImagePath = $this->getFilePath($dirList.'/catalog/product', $fileName);
        $baseImagePath = $this->getFilePath($dirList.'/wkosi/products', $fileName);
        if(!$this->mediaDirectory->isExist($baseImagePath)) {
            $this->mediaDirectory->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        }
    }

    /**
     * Get Image Path
     *
     * @param string $path
     * @param string $imageName
     * @return string
     */
    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Save marketplace products.
     *
     * @param array $productIds
     * @param int $sellerId
     *
     * @return void
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    private function saveMarketplaceProducts(array $productIds, int $sellerId): void
    {
        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId, true, Store::DEFAULT_STORE_ID);
            $currentTime = $this->dateTime->gmtDate();
            try {
                $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            } catch (NoSuchEntityException $e) {
                $sellerProduct = $this->marketplaceProductManagement->create();
                $sellerProduct->setData('mageproduct_id', $productId);
                $sellerProduct->setData('seller_id', $sellerId);
                $sellerProduct->setData('store_id', Store::DEFAULT_STORE_ID);
                $sellerProduct->setData('mage_pro_row_id', $product->getRowId());
                $sellerProduct->setData('created_at', $currentTime);
            }
            $sellerProduct->setData('status', Product::STATUS_PENDING);
            $sellerProduct->setData('is_approved', 0);
            $sellerProduct->setData('updated_at', $currentTime);
            $this->marketplaceProductManagement->save($sellerProduct);
        }
    }
    /**
     * Save Custom Option Data
     *
     * @param array $wholeData
     *
     * @return array $result
     */
    public function saveCustomOptionData($wholeData)
    {
        $result = ['msg'=>'','error'=>false];
        $productId = isset($wholeData['product_id']) ? $wholeData['product_id'] : 0;
        $customOpt = $wholeData['custom_option'];
        if ($productId > 0 && isset($customOpt['swatch'])
            && isset($customOpt['variation'])
        ) {
            $swatches = $customOpt['swatch'];
            $variation = $customOpt['variation'];
            $customOption =  $this->customOptionsave($productId);
            $customOptionResult = $customOption['result'];
            $customOptionIdsArr = $customOption['optionIdsArr'];
            if (!$customOptionResult['error']) {
                $saveSwatch = $this->optionsWithStockAndImagesHelper->saveSwatches($swatches, $productId, $customOptionIdsArr);
                if (!$saveSwatch['error']) {
                    $saveVariation = $this->saveVariation($variation, $productId);
                    if ($saveVariation['error']) {
                        $result['error'] = true;
                        $result['msg'] =  $saveVariation['msg'];
                    }
                } else {
                    $result['error'] = true;
                    $result['msg'] =  $saveSwatch['msg'];
                }
            } else {
                $result['error'] = true;
                $result['msg'] =  $customOptionResult['msg'];
            }
        } else {
            $result = ['msg'=>__('There is some problem in data format.Please check once your data'),'error'=>true];
        }
        return $result;
    }

    /**
     * Custom Option Save
     *
     * @param array $customOptionData
     * @param int $productId
     * @return array
     */
    public function customOptionsave($productId)
    {
        $result = ['error'=>false,'msg'=>''];
        $optionIdsArr = [];
        try {
            $product = $this->productRepository->getById($productId, false, 0);
            if (!empty($product->getId())) {
                foreach ($product->getOptions() as $option) {
                    $otpType = $option->getType();
                    if ($otpType=="drop-down" || $otpType=="drop_down" || $otpType=="radio") {
                        $optionIdsArr[] = $option->getOptionId();
                    }
                }
            } else {
                $result['error'] = true;
                $result['msg'] = __('Product is not available with Product id %1', $productId);
            }
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['msg'] = $e->getMessage();
        }
        return [
            'result' => $result,
            'optionIdsArr' => $optionIdsArr
        ];
    }

    /**
     * Save variation
     *
     * @param array $variations
     * @param int $productId
     * @return array
     */
    public function saveVariation($variations, $productId)
    {
        $result = ['error' => false,'msg'=>''];
        $failedImages = [];
        try {
            $variationFactory = $this->variationFactory->create()->getCollection()
                ->addFieldToFilter('product_id', $productId);
            foreach ($variationFactory as $val) {
                $val->delete();
            }
            foreach ($variations as $variation) {
                $variationFactory = $this->variationFactory->create();
                $variation['product_id'] = $productId;
                $variationFactory->addData($variation);
                $variationFactory->save();
            }

            if (!empty($failedImages)) {
                $result ['msg'] = __('Some Images could not be imported, please check the file path');
                $result ['error'] = true;
            }

        } catch (\Exception $e) {
            $result ['msg'] = $e->getMessage();
            $result ['error'] = true;
        }
        return $result;
    }
}
