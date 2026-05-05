<?php

namespace Branch8\CatalogCustom\Model\Import;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogImportExport\Model\Import\Product\ImageTypeProcessor;
use Magento\CatalogImportExport\Model\Import\Product\LinkProcessor;
use Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Skip;
use Magento\CatalogImportExport\Model\Import\Product\StatusProcessor;
use Magento\CatalogImportExport\Model\Import\Product\StockProcessor;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\VersionManager;

class Product extends \Magento\CatalogImportExport\Model\Import\Product
{

    private const COL_NAME_FORMAT = '/[\x00-\x1F\x7F]/';

    public const END_TIME = 'end_time';
    public const START_TIME = 'start_time';

    /**
     * @var string
     */
    private $hashAlgorithm = 'crc32c';
    private const DEFAULT_GLOBAL_MULTIPLE_VALUE_SEPARATOR = ',';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @var string
     */
    private $productEntityIdentifierField;

    /**
     * Escaped separator value for regular expression.
     * The value is based on PSEUDO_MULTI_LINE_SEPARATOR constant.
     * @var string
     */
    private $multiLineSeparatorForRegexp;

    /**
     * Container for filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CatalogConfig
     */
    private $catalogConfig;

    /**
     * @var StockItemImporterInterface
     */
    private $stockItemImporter;

    /**
     * @var ImageTypeProcessor
     */
    private $imageTypeProcessor;

    /**
     * Provide ability to process and save images during import.
     *
     * @var MediaGalleryProcessor
     */
    private $mediaProcessor;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StatusProcessor
     */
    private $statusProcessor;
    /**
     * @var StockProcessor
     */
    private $stockProcessor;

    /**
     * @var LinkProcessor
     */
    private $linkProcessor;

    /**
     * @var StockItemProcessorInterface
     */
    private $stockItemProcessor;

    protected $update;
    protected $updateRepository;
    protected $versionManager;
    protected $productStaging;

    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        \Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Staging\Api\Data\UpdateInterface $update,
        \Magento\CatalogStaging\Api\ProductStagingInterface $productStaging,
        array $data = [],
        array $dateAttrCodes = [],
        CatalogConfig $catalogConfig = null,
        ImageTypeProcessor $imageTypeProcessor = null,
        MediaGalleryProcessor $mediaProcessor = null,
        StockItemImporterInterface $stockItemImporter = null,
        DateTimeFactory $dateTimeFactory = null,
        ProductRepositoryInterface $productRepository = null,
        StatusProcessor $statusProcessor = null,
        StockProcessor $stockProcessor = null,
        LinkProcessor $linkProcessor = null,
        ?File $fileDriver = null,
        ?StockItemProcessorInterface $stockItemProcessor = null
    ) {
        $this->update = $update;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->productStaging = $productStaging;
        $this->_logger = $logger;
        $this->filesystem = $filesystem;
        $this->catalogConfig = $catalogConfig ?: ObjectManager::getInstance()->get(CatalogConfig::class);
        $this->stockItemImporter = $stockItemImporter ?: ObjectManager::getInstance()
            ->get(StockItemImporterInterface::class);
        $this->imageTypeProcessor = $imageTypeProcessor ?: ObjectManager::getInstance()->get(ImageTypeProcessor::class);
        $this->mediaProcessor = $mediaProcessor ?: ObjectManager::getInstance()->get(MediaGalleryProcessor::class);
        $this->dateTimeFactory = $dateTimeFactory ?? ObjectManager::getInstance()->get(DateTimeFactory::class);
        $this->productRepository = $productRepository ?? ObjectManager::getInstance()
            ->get(ProductRepositoryInterface::class);
        $this->statusProcessor = $statusProcessor ?: ObjectManager::getInstance()
            ->get(StatusProcessor::class);
        $this->stockProcessor = $stockProcessor ?: ObjectManager::getInstance()
            ->get(StockProcessor::class);
        $this->linkProcessor = $linkProcessor ?? ObjectManager::getInstance()
            ->get(LinkProcessor::class);
        $this->stockItemProcessor = $stockItemProcessor ?? ObjectManager::getInstance()
            ->get(StockItemProcessorInterface::class);
        \Magento\CatalogImportExport\Model\Import\Product::__construct($jsonHelper, $importExportData, $importData, $config, $resource, $resourceHelper, $string,
            $errorAggregator, $eventManager, $stockRegistry, $stockConfiguration, $stockStateProvider, $catalogData,
            $importConfig, $resourceFactory, $optionFactory, $setColFactory, $productTypeFactory, $linkFactory,
            $proxyProdFactory, $uploaderFactory, $filesystem, $stockResItemFac, $localeDate, $dateTime, $logger,
            $indexerRegistry, $storeResolver, $skuProcessor, $categoryProcessor, $validator, $objectRelationProcessor,
            $transactionManager, $taxClassProcessor, $scopeConfig, $productUrl, $data, $dateAttrCodes, $catalogConfig,
            $imageTypeProcessor, $mediaProcessor, $stockItemImporter, $dateTimeFactory, $productRepository,
            $statusProcessor, $stockProcessor, $linkProcessor, $fileDriver, $stockItemProcessor);
    }

    /**
     * Gather and save information about product entities.
     *
     * FIXME: Reduce nesting level
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @throws LocalizedException
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    protected function _saveProducts()
    {
        $priceIsGlobal = $this->_catalogData->isPriceGlobal();
        $previousType = null;
        $prevAttributeSet = null;
        $productMediaPath = $this->getProductMediaPath();
        while ($bunch = $this->_dataSourceModel->getNextUniqueBunch($this->getIds())) {
            $entityRowsIn = [];
            $entityRowsUp = [];
            $this->websitesCache = [];
            $this->categoriesCache = [];
            $tierPrices = [];
            $mediaGallery = [];
            $labelsForUpdate = [];
            $imagesForChangeVisibility = [];
            $uploadedImages = [];
            $existingImages = $this->getExistingImages($bunch);
            $attributes = [];
            foreach ($bunch as $rowNum => $rowData) {
                try {
                    // reset category processor's failed categories array
                    $this->categoryProcessor->clearFailedCategories();
                    if (!$this->validateRow($rowData, $rowNum)) {
                        continue;
                    }
                    if ($this->getErrorAggregator()->hasToBeTerminated()) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    $rowScope = $this->getRowScope($rowData);
                    $urlKey = $this->getUrlKey($rowData);
                    if (!empty($rowData[self::URL_KEY])) {
                        // If url_key column and its value were in the CSV file
                        $rowData[self::URL_KEY] = $urlKey;
                    } elseif ($this->isNeedToChangeUrlKey($rowData)) {
                        // If url_key column was empty or even not declared in the CSV file but by the rules it needs
                        // to be settled. In case when url_key is generating from name column we have to ensure that
                        // the bunch of products will pass for the event with url_key column.
                        $bunch[$rowNum][self::URL_KEY] = $rowData[self::URL_KEY] = $urlKey;
                    }

                    if (!empty($rowData[self::COL_NAME])) {
                        // remove null byte character
                        $rowData[self::COL_NAME] = preg_replace(self::COL_NAME_FORMAT, '', $rowData[self::COL_NAME]);
                    }

                    $rowSku = $rowData[self::COL_SKU];
                    if (null === $rowSku) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    $storeId = !empty($rowData[self::COL_STORE])
                        ? $this->getStoreIdByCode($rowData[self::COL_STORE])
                        : Store::DEFAULT_STORE_ID;
                    if (self::SCOPE_STORE == $rowScope) {
                        // set necessary data from SCOPE_DEFAULT row
                        $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                        $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                        $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
                    }
                    if(isset($rowData[self::START_TIME]) && isset($rowData[self::END_TIME])){
                        try {
                            $this->validateStartTimeNotPast($rowData[self::START_TIME]);
                            $this->validateEndTime($rowData[self::START_TIME], $rowData[self::END_TIME]);
                            $productUpdate = $this->productRepository->get($rowSku);
                            $isUpdate = $this->updateProductStaging($rowData, $productUpdate, $rowData[self::START_TIME], $rowData[self::END_TIME]);
                            if($isUpdate){
                                continue;
                            }
                        }catch (\Exception $exception){
                           // Product is skipped.  Go on to the next one.
                           if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                                $this->_logger->critical($exception);
                           }
                        }
                    }
                    $this->saveProductEntityPhase($rowData, $entityRowsUp, $entityRowsIn);
                    $this->saveProductToWebsitePhase($rowData);
                    $this->saveProductCategoriesPhase($rowNum, $rowData);
                    $this->saveProductTierPricesPhase($rowData, $priceIsGlobal, $tierPrices);
                    $this->saveProductMediaGalleryPhase(
                        $rowNum,
                        $rowData,
                        $storeId,
                        $existingImages,
                        $productMediaPath,
                        $uploadedImages,
                        $imagesForChangeVisibility,
                        $labelsForUpdate,
                        $mediaGallery
                    );
                    $this->saveProductAttributesPhase(
                        $rowData,
                        $rowScope,
                        $previousType,
                        $prevAttributeSet,
                        $attributes
                    );
                    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                } catch (Skip $skip) {
                    // Product is skipped.  Go on to the next one.
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                        $this->_logger->critical($skip);
                    }
                }
            }
            foreach ($bunch as $rowNum => $rowData) {
                if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    unset($bunch[$rowNum]);
                }
            }
            $this->saveProductEntity($entityRowsIn, $entityRowsUp);
            $this->_saveProductWebsites($this->websitesCache);
            $this->_saveProductCategories($this->categoriesCache);
            $this->_saveProductTierPrices($tierPrices);
            $this->_saveMediaGallery($mediaGallery);
            $this->updateMediaGalleryVisibility($imagesForChangeVisibility);
            $this->updateMediaGalleryLabels($labelsForUpdate);
            $this->_saveProductAttributes($attributes);
            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }


    /**
     * In _saveProducts loop, save product's media gallery
     *
     * @param int $rowNum
     * @param array $rowData
     * @param int $storeId
     * @param array $existingImages
     * @param string $productMediaPath
     * @param array $uploadedImages
     * @param array $imagesForChangeVisibility
     * @param array $labelsForUpdate
     * @param array $mediaGallery
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return void
     */
    private function saveProductMediaGalleryPhase(
        int $rowNum,
        array &$rowData,
        int $storeId,
        array $existingImages,
        string $productMediaPath,
        array &$uploadedImages,
        array &$imagesForChangeVisibility,
        array &$labelsForUpdate,
        array &$mediaGallery
    ) : void {
        $rowSku = $rowData[self::COL_SKU];
        $rowSkuNormalized = mb_strtolower($rowSku);
        $rowExistingImages = $existingImages[$storeId][$rowSkuNormalized] ?? [];
        $rowStoreMediaGalleryValues = $rowExistingImages;
        $rowExistingImages += $existingImages[Store::DEFAULT_STORE_ID][$rowSkuNormalized] ?? [];
        list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);
        $imageHiddenStates = $this->getImagesHiddenStates($rowData);
        foreach (array_keys($imageHiddenStates) as $image) {
            //Mark image as uploaded if it exists
            if (array_key_exists($image, $rowExistingImages)) {
                $uploadedImages[$image] = $image;
            }
            //Add image to hide to images list if it does not exist
            if (empty($rowImages[self::COL_MEDIA_IMAGE])
                || !in_array($image, $rowImages[self::COL_MEDIA_IMAGE])
            ) {
                $rowImages[self::COL_MEDIA_IMAGE][] = $image;
            }
        }
        $rowData[self::COL_MEDIA_IMAGE] = [];
        list($rowImages, $rowData) = $this->clearNoSelectionImages($rowImages, $rowData);
        /*
         * Note: to avoid problems with undefined sorting, the value of media gallery items positions
         * must be unique in scope of one product.
         */
        $position = 0;
        $imagesByHash = [];
        foreach ($rowImages as $column => $columnImages) {
            foreach ($columnImages as $columnImageKey => $columnImage) {
                $uploadedFile = $this->findImageByColumnImage(
                    $productMediaPath,
                    $rowExistingImages,
                    $columnImage,
                    $imagesByHash
                );
                if (!$uploadedFile && !isset($uploadedImages[$columnImage])) {
                    $uploadedFile = $this->uploadMediaFiles($columnImage);
                    $uploadedFile = $uploadedFile ?: $this->getSystemFile($columnImage);
                    if ($uploadedFile) {
                        $uploadedImages[$columnImage] = $uploadedFile;
                    } else {
                        unset($rowData[$column]);
                        $this->addRowError(
                            ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                            $rowNum,
                            null,
                            null,
                            ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                        );
                    }
                } elseif (isset($uploadedImages[$columnImage])) {
                    $uploadedFile = $uploadedImages[$columnImage];
                }
                if ($uploadedFile && $column !== self::COL_MEDIA_IMAGE) {
                    $rowData[$column] = $uploadedFile;
                }
                if (!$uploadedFile || isset($mediaGallery[$storeId][$rowSku][$uploadedFile])) {
                    continue;
                }
                $uploadedFileNormalized = ltrim($uploadedFile, '/\\');
                if (isset($rowExistingImages[$uploadedFileNormalized])) {
                    $currentFileData = $rowExistingImages[$uploadedFileNormalized];
                    $currentFileData['store_id'] = $storeId;
                    $storeMediaGalleryValueExists = isset($rowStoreMediaGalleryValues[$uploadedFileNormalized]);
                    if (array_key_exists($uploadedFile, $imageHiddenStates)
                        && $currentFileData['disabled'] != $imageHiddenStates[$uploadedFile]
                    ) {
                        $imagesForChangeVisibility[] = [
                            'disabled' => $imageHiddenStates[$uploadedFile],
                            'imageData' => $currentFileData,
                            'exists' => $storeMediaGalleryValueExists
                        ];
                        $storeMediaGalleryValueExists = true;
                    }
                    if (isset($rowLabels[$column][$columnImageKey])
                        && $rowLabels[$column][$columnImageKey] !== $currentFileData['label']
                    ) {
                        $labelsForUpdate[] = [
                            'label' => $rowLabels[$column][$columnImageKey],
                            'imageData' => $currentFileData,
                            'exists' => $storeMediaGalleryValueExists
                        ];
                    }
                } else {
                    if ($column === self::COL_MEDIA_IMAGE) {
                        $rowData[$column][] = $uploadedFile;
                    }
                    $mediaGalleryStoreData = [
                        'attribute_id' => $this->getMediaGalleryAttributeId(),
                        'label' => isset($rowLabels[$column][$columnImageKey])
                            ? $rowLabels[$column][$columnImageKey]
                            : '',
                        'position' => ++$position,
                        'disabled' => isset($imageHiddenStates[$columnImage])
                            ? $imageHiddenStates[$columnImage] : '0',
                        'value' => $uploadedFile,
                    ];
                    $mediaGallery[$storeId][$rowSku][$uploadedFile] = $mediaGalleryStoreData;
                    // Add record for default scope if it does not exist
                    if (!($mediaGallery[Store::DEFAULT_STORE_ID][$rowSku][$uploadedFile] ?? [])) {
                        //Set label and disabled values to their default values
                        $mediaGalleryStoreData['label'] = null;
                        $mediaGalleryStoreData['disabled'] = 0;
                        $mediaGallery[Store::DEFAULT_STORE_ID][$rowSku][$uploadedFile] = $mediaGalleryStoreData;
                    }
                }
            }
        }
    }


    /**
     * Try to find file by it's path.
     *
     * @param string $fileName
     * @return string
     */
    private function getSystemFile($fileName)
    {
        $filePath = $this->joinFilePaths($this->getProductMediaPath(), $fileName);

        return $this->_mediaDirectory->isFile($filePath) && $this->_mediaDirectory->isReadable($filePath)
            ? $fileName
            : '';
    }


    /**
     * Returns image that matches the provided image content
     *
     * @param string $productMediaPath
     * @param array $images
     * @param string $columnImage
     * @param array $imagesByHash
     * @return string
     */
    private function findImageByColumnImage(
        string $productMediaPath,
        array &$images,
        string $columnImage,
        array &$imagesByHash
    ): string {
        $content = filter_var($columnImage, FILTER_VALIDATE_URL)
            ? $this->getRemoteFileContent($columnImage)
            : $this->getFileContent($this->joinFilePaths($this->getUploader()->getTmpDir(), $columnImage));
        if (!$content) {
            return '';
        }
        return $this->findImageByColumnImageUsingHash($productMediaPath, $images, $content, $imagesByHash);
    }

    /**
     * Returns image that matches the provided image content using hash
     *
     * @param string $productMediaPath
     * @param array $images
     * @param string $content
     * @param array $imagesByHash
     * @return string
     */
    private function findImageByColumnImageUsingHash(
        string $productMediaPath,
        array &$images,
        string $content,
        array &$imagesByHash
    ): string {
        $hash = hash($this->hashAlgorithm, $content);
        if (!empty($imagesByHash[$hash])) {
            return $imagesByHash[$hash];
        }
        foreach ($images as &$image) {
            if (!isset($image['hash'])) {
                $imageContent = $this->getFileContent($this->joinFilePaths($productMediaPath, $image['value']));
                if (!$imageContent) {
                    $image['hash'] = '';
                    continue;
                }
                $image['hash'] = hash($this->hashAlgorithm, $imageContent);
                $imagesByHash[$image['hash']] = $image['value'];
            }
            if (!empty($image['hash']) && $image['hash'] === $hash) {
                return $image['value'];
            }
        }
        return '';
    }


    /**
     * Returns image content by path
     *
     * @param string $path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getFileContent(string $path): string
    {
        if ($this->_mediaDirectory->isFile($path)
            && $this->_mediaDirectory->isReadable($path)
        ) {
            return $this->_mediaDirectory->readFile($path);
        }
        return '';
    }

    /**
     * Returns content for remote file
     *
     * @param string $filename
     * @return string
     */
    private function getRemoteFileContent(string $filename): string
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $content = file_get_contents($filename);
        // phpcs:enable Magento2.Functions.DiscouragedFunction
        return $content !== false ? $content : '';
    }


    /**
     * Clears entries from Image Set and Row Data marked as no_selection
     *
     * @param array $rowImages
     * @param array $rowData
     * @return array
     */
    private function clearNoSelectionImages($rowImages, $rowData)
    {
        foreach ($rowImages as $column => $columnImages) {
            foreach ($columnImages as $key => $image) {
                if ($image === 'no_selection') {
                    unset($rowImages[$column][$key], $rowData[$column]);
                }
            }
        }

        return [$rowImages, $rowData];
    }

    /**
     * Prepare array with image states (visible or hidden from product page)
     *
     * @param array $rowData
     * @return array
     */
    private function getImagesHiddenStates($rowData)
    {
        $statesArray = [];
        $mappingArray = [
            '_media_is_disabled' => '1'
        ];

        foreach ($mappingArray as $key => $value) {
            if (isset($rowData[$key]) && strlen(trim($rowData[$key]))) {
                $items = explode($this->getMultipleValueSeparator(), $rowData[$key]);

                foreach ($items as $item) {
                    $statesArray[$item] = $value;
                }
            }
        }

        return $statesArray;
    }


    /**
     * In _saveProducts loop, save product's tier prices
     *
     * @param array $rowData
     * @param bool $priceIsGlobal
     * @param array $tierPrices
     * @return void
     */
    private function saveProductTierPricesPhase(array $rowData, bool $priceIsGlobal, array &$tierPrices) : void
    {
        $rowSku = $rowData[self::COL_SKU];
        if (!empty($rowData['_tier_price_website'])) {
            $tierPrices[$rowSku][] = [
                'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                'qty' => $rowData['_tier_price_qty'],
                'value' => $rowData['_tier_price_price'],
                'website_id' => self::VALUE_ALL == $rowData['_tier_price_website'] ||
                $priceIsGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
            ];
        }
    }


    /**
     * In _saveProducts loop, save product's attributes
     *
     * @param array $rowData
     * @param int $rowScope
     * @param mixed $previousType
     * @param mixed $prevAttributeSet
     * @param array $attributes
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return void
     */
    private function saveProductAttributesPhase(
        array $rowData,
        int $rowScope,
        &$previousType,
        &$prevAttributeSet,
        array &$attributes
    ) : void {
        $rowSku = $rowData[self::COL_SKU];
        $rowStore = (self::SCOPE_STORE == $rowScope)
            ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
            : 0;
        $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
        if ($productType !== null) {
            $previousType = $productType;
        }
        if (isset($rowData[self::COL_ATTR_SET])) {
            $prevAttributeSet = $rowData[self::COL_ATTR_SET];
        }
        if (self::SCOPE_NULL == $rowScope) {
            // for multiselect attributes only
            if ($prevAttributeSet !== null) {
                $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
            }
            if ($productType === null && $previousType !== null) {
                $productType = $previousType;
            }
            if ($productType === null) {
                throw new Skip(__('Unknown Product Type'));
            }
        }
        $productTypeModel = $this->_productTypeModels[$productType];
        if (isset($rowData['tax_class_name']) && strlen($rowData['tax_class_name'])) {
            $rowData['tax_class_id'] =
                $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
        }
        if ($this->getBehavior() == Import::BEHAVIOR_APPEND ||
            empty($rowData[self::COL_SKU])
        ) {
            $rowData = $productTypeModel->clearEmptyData($rowData);
        }
        $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
            $rowData,
            !$this->isSkuExist($rowSku)
        );
        $product = $this->_proxyProdFactory->create(['data' => $rowData]);
        foreach ($rowData as $attrCode => $attrValue) {
            $attribute = $this->retrieveAttributeByCode($attrCode);
            if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                // skip attribute processing for SCOPE_NULL rows
                continue;
            }
            $attrId = $attribute->getId();
            $backModel = $attribute->getBackendModel();
            $attrTable = $attribute->getBackend()->getTable();
            $storeIds = [0];
            if ('datetime' == $attribute->getBackendType()
                && (
                    in_array($attribute->getAttributeCode(), $this->dateAttrCodes)
                    || $attribute->getIsUserDefined()
                )
            ) {
                $attrValue = $this->dateTime->formatDate($attrValue, false);
            } elseif ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                $attrValue = gmdate(
                    'Y-m-d H:i:s',
                    $this->_localeDate->date($attrValue)->getTimestamp()
                );
            } elseif ($backModel) {
                $attribute->getBackend()->beforeSave($product);
                $attrValue = $product->getData($attribute->getAttributeCode());
            }
            if (self::SCOPE_STORE == $rowScope) {
                if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                    // check website defaults already set
                    if (!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                        $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                    }
                } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                    $storeIds = [$rowStore];
                }
                if (!$this->isSkuExist($rowSku)) {
                    $storeIds[] = 0;
                }
            }
            foreach ($storeIds as $storeId) {
                if (!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                    $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                }
            }
            // restore 'backend_model' to avoid 'default' setting
            $attribute->setBackendModel($backModel);
        }
    }

    /**
     * Update media gallery labels
     *
     * @param array $labels
     * @return void
     */
    private function updateMediaGalleryLabels(array $labels)
    {
        if (!empty($labels)) {
            $this->mediaProcessor->updateMediaGalleryLabels($labels);
        }
    }

    /**
     * Update 'disabled' field for media gallery entity
     *
     * @param array $images
     * @return $this
     */
    private function updateMediaGalleryVisibility(array $images)
    {
        if (!empty($images)) {
            $this->mediaProcessor->updateMediaGalleryVisibility($images);
        }

        return $this;
    }

    /**
     * In _saveProducts loop, save product's categories
     *
     * @param int $rowNum
     * @param array $rowData
     * @return void
     */
    private function saveProductCategoriesPhase(int $rowNum, array $rowData) : void
    {
        $rowSku = $rowData[self::COL_SKU];
        if (!array_key_exists($rowSku, $this->categoriesCache)) {
            $this->categoriesCache[$rowSku] = [];
        }
        $rowData['rowNum'] = $rowNum;
        $categoryIds = $this->processRowCategories($rowData);
        foreach ($categoryIds as $id) {
            $this->categoriesCache[$rowSku][$id] = true;
        }
    }

    /**
     * In _saveProducts loop, save product to website
     *
     * @param array $rowData
     * @return void
     */
    private function saveProductToWebsitePhase(array $rowData) : void
    {
        $rowSku = $rowData[self::COL_SKU];
        if (!array_key_exists($rowSku, $this->websitesCache)) {
            $this->websitesCache[$rowSku] = [];
        }
        if (!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
            $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
            foreach ($websiteCodes as $websiteCode) {
                $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                $this->websitesCache[$rowSku][$websiteId] = true;
            }
        } else {
            $product = $this->retrieveProductBySku($rowSku);
            if ($product) {
                $websiteIds = $product->getWebsiteIds();
                foreach ($websiteIds as $websiteId) {
                    $this->websitesCache[$rowSku][$websiteId] = true;
                }
            }
        }
    }

    /**
     * Retrieve product by sku.
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    private function retrieveProductBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $product;
    }

    /**
     * In _saveProducts loop, save product entity
     *
     * @param array $rowData
     * @param array $entityRowsUp
     * @param array $entityRowsIn
     * @return void
     * @throws LocalizedException
     */
    private function saveProductEntityPhase(array $rowData, array &$entityRowsUp, array &$entityRowsIn) : void
    {
        $rowSku = $rowData[self::COL_SKU];
        if ($this->isSkuExist($rowSku)) {
            // existing row
            if (isset($rowData['attribute_set_code'])) {
                $attributeSetId = $this->catalogConfig->getAttributeSetId(
                    $this->getEntityTypeId(),
                    $rowData['attribute_set_code']
                );
                // wrong attribute_set_code was received
                if (!$attributeSetId) {
                    throw new LocalizedException(
                        __(
                            'Wrong attribute set code "%1", please correct it and try again.',
                            $rowData['attribute_set_code']
                        )
                    );
                }
            } else {
                $attributeSetId = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
            }
            $entityLinkField = $this->getProductEntityLinkField();
            $entityRowsUp[] = [
                'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                'attribute_set_id' => $attributeSetId,
                $entityLinkField => $this->getExistingSku($rowSku)[$entityLinkField]
            ];
        } else {
            $entityRowsIn[strtolower($rowSku)] = [
                'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                'type_id' => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                'sku' => $rowSku,
                'has_options' => isset($rowData['has_options']) ? $rowData['has_options'] : 0,
                'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
            ];
        }
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     * @return array
     */
    private function getExistingSku($sku)
    {
        return $this->_oldSku[strtolower($sku)];
    }

    /**
     * Get product entity link field
     *
     * @return string
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    /**
     * Whether a url key needs to change.
     *
     * @param array $rowData
     * @return bool
     */
    private function isNeedToChangeUrlKey(array $rowData): bool
    {
        $urlKey = $this->getUrlKey($rowData);
        $productExists = $this->isSkuExist($rowData[self::COL_SKU]);
        $markedToEraseUrlKey = isset($rowData[self::URL_KEY]);
        // The product isn't new and the url key index wasn't marked for change.
        if (!$urlKey && $productExists && !$markedToEraseUrlKey) {
            // Seems there is no need to change the url key
            return false;
        }

        return true;
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     * @return bool
     */
    private function isSkuExist($sku)
    {
        if ($sku !== null) {
            $sku = strtolower($sku);
            return isset($this->_oldSku[$sku]);
        }
        return false;
    }

    /**
     * Returns product media
     *
     * @return string relative path to root folder
     */
    private function getProductMediaPath(): string
    {
        return $this->joinFilePaths($this->getMediaBasePath(), 'catalog', 'product');
    }

    /**
     * Returns media base path
     *
     * @return string relative path to root folder
     */
    private function getMediaBasePath(): string
    {
        $mediaDir = !is_a($this->_mediaDirectory->getDriver(), File::class)
            // make media folder a primary folder for media in external storages
            ? $this->filesystem->getDirectoryReadByPath(DirectoryList::MEDIA)
            : $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $this->_mediaDirectory->getRelativePath($mediaDir->getAbsolutePath());
    }

    /**
     * Joins two paths and remove redundant directory separator
     *
     * @param array $paths
     * @return string
     */
    private function joinFilePaths(...$paths): string
    {
        $result = '';
        if ($paths) {
            $firstPath = array_shift($paths);
            $result = $firstPath !== null ? rtrim($firstPath, DIRECTORY_SEPARATOR) : '';
            foreach ($paths as $path) {
                $result .= DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
            }
        }
        return $result;
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return void
     * @throws ValidatorException
     */
    private function validateMaxTime($startTime, $endTime)
    {
        $currentDateTime = new \DateTime();
        $diff = abs(VersionManager::MAX_VERSION - $currentDateTime->getTimestamp());
        $years = ceil($diff / (365*60*60*24));
        if (strtotime($startTime) > VersionManager::MAX_VERSION) {
            throw new ValidatorException(
                __(
                    "The Future Update Start Time is invalid. It can't be later than current time + %1 years.",
                    $years
                )
            );
        }

        if ($endTime && strtotime($endTime) > VersionManager::MAX_VERSION) {
            throw new ValidatorException(
                __(
                    "The Future Update End Time is invalid. It can't be later than current time + %1 years.",
                    $years
                )
            );
        }
    }

    /**
     * @param $startTime
     * @return void
     * @throws ValidatorException
     */
    protected function validateStartTimeNotPast($startTime)
    {
        $currentDateTime = new \DateTime();
        if (strtotime($startTime) < $currentDateTime->getTimestamp()) {
            throw new ValidatorException(
                __("The Future Update Start Time is invalid. It can't be earlier than the current time.")
            );
        }
    }

    /**
     * @param $startTime
     * @param $endTime
     * @return void
     * @throws ValidatorException
     */
    protected function validateEndTime($startTime, $endTime)
    {
        $currentDateTime = new \DateTime();
        $startTime = $startTime ?? 'now';
        $endTime = $endTime ?? 'now';
        $startTimeGreaterEndTime = strtotime($startTime) >= strtotime($endTime);
        if ($endTime && $startTimeGreaterEndTime) {
            throw new ValidatorException(
                __("The Future Update End Time is invalid. It can't be the same time or earlier than the current time.")
            );
        }

        $endTimeLessCurrentTime = strtotime($endTime) <= $currentDateTime->getTimestamp();
        if ($endTime && $endTimeLessCurrentTime) {
            throw new ValidatorException(
                __("The Future Update End Time is invalid. It can't be earlier than the current time.")
            );
        }
        $this->validateMaxTime($startTime, $endTime);
    }

    /**
     * @param $data
     * @param $product
     * @param $starTime
     * @param $endTime
     * @return bool
     */
    public function updateProductStaging($data, $product, $starTime, $endTime){
        try {
            $schedule = $this->update;
            $schedule->setName("Import Update #");
            $schedule->setStartTime($starTime);
            $schedule->setEndTime($endTime);
            $stagingRepo = $this->updateRepository->save($schedule);
            $this->versionManager->setCurrentVersionId($stagingRepo->getId());
            if(is_array($data) && !empty($data)){
                $skipRow = ['sku', '_attribute_set', 'end_time', 'start_time'];
                foreach ($data as $key => $value){
                    if(in_array($key, $skipRow)){
                        continue;
                    }
                    if(@strtolower($value) == 'yes'){
                        $value = 1;
                    }else if(@strtolower($value) == 'no'){
                        $value = 0;
                    }
                    $product->setData($key, $value);
                }
            }
            $this->productStaging->schedule($product, $stagingRepo->getId());
        }catch (\Exception $exception){
            return false;
        }
       return true;
    }
}
