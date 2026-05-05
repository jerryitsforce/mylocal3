<?php
namespace JustBetter\ProductGridExport\Model\Export;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\MarketplaceProduct\Model\Export\SearchResultIterator;
use Branch8\MarketplaceProductImport\Plugin\Webkul\MpMassUpload\Helper\Data as HelperData;
use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Elasticsearch\Endpoints\AsyncSearch\Get;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

/**
 * Class ConvertToXls
 */
class Product
{
    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var ExcelFactory
     */
    protected $excelFactory;

    /**
     * @var SearchResultIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    protected Config $eavConfig;

    /**
     * @var StockRegistryProviderInterface
     */
    protected $_stockRegistryProvider;

    /**
     * @var StockConfigurationInterface
     */
    protected $_stockConfiguration;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    protected ProductCustomOptionRepositoryInterface $customOptionRepository;

    /**
     * @var VariationsFactory
     */
    protected VariationsFactory $variationsFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var Salable
     */
    private $salableHelper;

    /**
     * Filtered columns after removing empty ones
     *
     * @var array
     */
    private array $filteredColumns = [];

    private $cacheMainCategory = [];

    private $cacheFlagStoreCategory = [];

    /**
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ExcelFactory $excelFactory
     * @param SearchResultIteratorFactory $iteratorFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $eavConfig
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param StockConfigurationInterface $stockConfiguration
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCustomOptionRepositoryInterface $customOptionRepository
     * @param VariationsFactory $variationsFactory
     * @param Json|null $json
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ExcelFactory $excelFactory,
        SearchResultIteratorFactory $iteratorFactory,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCustomOptionRepositoryInterface $customOptionRepository,
        VariationsFactory $variationsFactory,
        ProductRepositoryInterface $productRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        Salable $salableHelper,
        Json $json = null
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->excelFactory = $excelFactory;
        $this->iteratorFactory = $iteratorFactory;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
        $this->_stockRegistryProvider = $stockRegistryProvider;
        $this->_stockConfiguration = $stockConfiguration;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->customOptionRepository = $customOptionRepository;
        $this->variationsFactory = $variationsFactory;
        $this->productRepository = $productRepository;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->salableHelper = $salableHelper;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Returns row data
     *
     * @param $document
     * @return array
     */
    public function getRowData($document)
    {
        $row = [];
        $scopeId = $this->_stockConfiguration->getDefaultScopeId();
        $stockItem = $this->_stockRegistryProvider->getStockItem($document->getId(), $scopeId);
        //$catalogProduct = $this->productRepository->getById($document->getId());
        $catalogProduct = $document;
        $mainCategoryId = $catalogProduct->getMainCategory();
        $flagStoreCategoryId = $catalogProduct->getFlagstoreCategory();
        $pointMoneyConfigType = $catalogProduct->getData('point_money_config_type');
        $customOptions = $catalogProduct->getData('has_options') ? $this->customOptionRepository->getProductOptions($catalogProduct) : [];
        try {
            $salableQty = $this->getSalableQuantityDataBySku->execute($catalogProduct->getSku());
            $catalogProductSalableQty = '';
            if(isset($salableQty[0]['qty']) && $salableQty[0]['qty'] !== null) {
                $catalogProductSalableQty = $salableQty[0]['qty'];
            }
        } catch (\Exception $e) {
            $catalogProductSalableQty = '';
        }
        $virtualProductType = $catalogProduct->getData(VirtualProductType::ATTRIBUTE_CODE);
        $mainCategory = '';
        $arrayCategory = [];
        if ($mainCategoryId) {
            if (isset($this->cacheMainCategory[$mainCategoryId])) {
                $mainCategory = $this->cacheMainCategory[$mainCategoryId];
            } else {
                $collection = $this->categoryCollectionFactory->create();
                $collection->addAttributeToSelect('*');
                $collection->addAttributeToFilter('entity_id', ['eq' => $mainCategoryId]);
                foreach ($collection as $category) {
                    $pathIds = explode('/', $category->getPath());
                    $collection = $this->categoryCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->addAttributeToFilter('entity_id', ['in' => $pathIds])
                        ->addAttributeToSort('level', 'ASC');
                    foreach ($collection as $subCategory) {
                        if ($subCategory->getLevel() == 0) {
                            continue;
                        }
                        $arrayCategory[] = $subCategory->getName();
                    }
                    $mainCategory = implode(' >> ', $arrayCategory);
                }
                $this->cacheMainCategory[$mainCategoryId] = $mainCategory;
            }
        }
        $flagStoreCategory = '';
        $arrayFsCategory = [];
        if ($flagStoreCategoryId) {
            if (isset($this->cacheFlagStoreCategory[$flagStoreCategoryId])) {
                $flagStoreCategory = $this->cacheFlagStoreCategory[$flagStoreCategoryId];
            } else {
                $collection = $this->categoryCollectionFactory->create();
                $collection->addAttributeToSelect('*');
                $collection->addAttributeToFilter('entity_id', ['eq' => $flagStoreCategoryId]);
                foreach ($collection as $category) {
                    $pathIds = explode('/', $category->getPath());
                    $collection = $this->categoryCollectionFactory->create();
                    $collection->addAttributeToSelect('*');
                    $collection->addAttributeToFilter('entity_id', ['in' => $pathIds])
                        ->addAttributeToSort('level', 'ASC');
                    foreach ($collection as $subCategory) {
                        if ($subCategory->getLevel() == 0) {
                            continue;
                        }
                        $arrayFsCategory[] = $subCategory->getName();
                    }
                    $flagStoreCategory = implode(' >> ', $arrayFsCategory);
                }
                $this->cacheFlagStoreCategory[$flagStoreCategoryId] = $flagStoreCategory;
            }
        }
        $discountLimit = '';
        $redeemType = '';
        $pointValue = '';
        if ($pointMoneyConfigType && $pointMoneyConfigType == PointMoneyConfigCommon::TYPE_RATIO_LIMIT) {
            $upper = $catalogProduct->getData('point_money_config_free_ratio_upper_redeem_limit_value');
            $upperType = $catalogProduct->getData('point_money_config_free_ratio_upper_redeem_limit_type');
            $lower = $catalogProduct->getData('point_money_config_free_ratio_lower_redeem_limit_value');
            $lowerType = $catalogProduct->getData('point_money_config_free_ratio_lower_redeem_limit_type');
            if ($upper && $upper > 0) {
                $discountLimit = __('Upper Limit');
                $redeemType = ($upperType == 1) ? __('Percent') : __('Fixed');
                $pointValue = $upper;
            } elseif ($lower && $lower > 0) {
                $discountLimit = __('Lower Limit');
                $redeemType = ($lowerType == 1) ? __('Percent') : __('Fixed');
                $pointValue = $lower;
            }
        }

        // Build variation combination from custom options
        $variationCombFromOption = [];
        $optionalphabets = [];
        $variation_comb = [];
        $newCustomerOptions = [];
        foreach($customOptions as $option) {
            if ($option->getType() != 'drop_down') {
                continue;
            }
            $alphabets = [];
            $combs = [];
            if($option->hasValues()) {
                $valueData = [];
                $range = $this->salableHelper->getRangeList();
                $valueIndex = 0;
                foreach ($option->getValues() as $value) {
                    $alphabetIndex = $range[$valueIndex];
                    $alphabets[] = strtolower($alphabetIndex);
                    $combs[] = $value->getTitle();
                    $valueIndex++;
                }
            }
            if(!empty($alphabets)) {
                $optionalphabets[] = $alphabets;
            }
            if(!empty($combs)) {
                $variation_comb[] = $combs;
            }
            $newCustomerOptions[] = $option;
        }
        $optionalphabets = $this->salableHelper->combineArrays($optionalphabets);
        $variation_comb = $this->salableHelper->combineArrays($variation_comb);
        if(count($optionalphabets) && count($variation_comb)) {
            for ($i = 0; $i < count($optionalphabets); $i++) {
                $alphabet = str_replace('_', '-', $this->removeLastCharacter($optionalphabets[$i]));
                $variationCombFromOption[$this->removeLastCharacter($variation_comb[$i])] = $alphabet;
            }
        }
        $variationData = [];
        $variations = $this->variationsFactory->create()
                    ->getCollection()
                    ->addFieldToFilter("product_id", $document->getRowId());
        if($variations->getSize() && count($variationCombFromOption)){
            $variationData = $this->buildVariationData($variations, $variationCombFromOption);
        }

        foreach ($this->fields as $column) {
            if ($column == 'main_category') {
                $row[] = $mainCategory;
            } elseif ($column == 'flagstore_category') {
                $row[] = $flagStoreCategory;
            } elseif ($column == 'is_returnable') {
                $row[] = !$catalogProduct->getData($column) ? __('No') : __('Yes');
            } elseif (!empty($this->attributes[$column]['options'])) {
                $options = $this->attributes[$column]['options'];
                if ($this->attributes[$column]['type'] == 'multiselect') {
                    $values = $catalogProduct->getData($column) ? explode(',', $catalogProduct->getData($column)) : [];
                    $row[] = implode(',', array_map(function ($value) use ($options) {
                        foreach ($options as $option) {
                            if (is_array($option['value']) && $option['value'][0]['value'] == $value) {
                                return $option['label'];
                            } elseif ($option['value'] == $value) {
                                return $option['label'];
                            }
                        }
                    }, $values));
                } else {
                    $process = false;
                    foreach ($options as $option) {
                        if ($option['value'] == $catalogProduct->getData($column)) {
                            $process = true;
                            if ($column == 'tax_class_id') {
                                $row[] = __($option['label']);
                            } else {
                                $row[] = $option['label'];
                            }
                            break;
                        }
                    }
                    if (!$process) {
                        if ($column == 'status' && !$document->getData($column)) {
                            $row[] = __('Disabled');
                        } else {
                            $row[] = '';
                        }
                    }
                }
            } elseif (isset($this->attributes[$column]['type']) && $this->attributes[$column]['type'] == 'date') {
                if ($column == 'limit_purchased_start_time' || $column == 'limit_purchased_end_time') {
                    $flag = $catalogProduct->getData('limit_purchased_enable');
                    if (!$flag) {
                        $row[] = '';
                        continue;
                    }
                } elseif ($column == 'preorder_start_date' || $column == 'preorder_end_date' || $column == 'wk_marketplace_availability') {
                    $flag = $document->getData('wk_marketplace_preorder');
                    if (!$flag || $flag == __('Disable')) {
                        $row[] = '';
                        continue;
                    }
                }
                $row[] = $catalogProduct->getData($column) ? date('m/d/Y', strtotime($catalogProduct->getData($column))) : '';
            } elseif (isset($this->attributes[$column]['type']) && $this->attributes[$column]['type'] == 'datetime') {
                $row[] = $catalogProduct->getData($column) ? date('m/d/Y H:i:s', strtotime($catalogProduct->getData($column))) : '';
            } elseif (isset($this->attributes[$column]['type']) && $this->attributes[$column]['type'] == 'boolean') {
                $row[] = $catalogProduct->getData($column) ? __('Yes') :  __('No');
            } elseif (isset($this->attributes[$column]['type']) && $this->attributes[$column]['type'] == 'media_image') {
                if ($catalogProduct->getData($column) == 'no_selection') {
                    $row[] = '';
                } else {
                    $row[] = $catalogProduct->getData($column);
                }
            } elseif ($column == 'images') {
                $images = $catalogProduct->getMediaGalleryImages();
                $allImages = [];
                foreach ($images as $image) {
                    if (isset($image['file']) && $image['file'] !== '') {
                        $allImages[] = $image['file'];
                    }
                }
                if (!empty($allImages)) {
                    $row[] = implode(',', $allImages);
                } else {
                    $row[] = '';
                }
            } elseif (strpos($column, 'option_title_') === 0) {
                // Get the letter from column name (e.g. 'option_title_A' -> 'A')
                $optionIndex = substr($column, -1);
                $index = ord($optionIndex) - ord('A');

                if (isset($newCustomerOptions[$index])) {
                    $option = $newCustomerOptions[$index];
                    $row[] = $option->getTitle();
                } else {
                    $row[] = '';
                }
            } elseif (strpos($column, 'option_detail_') === 0) {
                $optionIndex = substr($column, -1);  // Get the letter (A, B, C, etc)
                $index = ord($optionIndex) - ord('A');

                if (isset($newCustomerOptions[$index])) {
                    $option = $newCustomerOptions[$index];

                    if ($option->hasValues()) {
                        $details = [];
                        $alphabet = $this->salableHelper->getRangeList();
                        $valueIndex = 0;
                        foreach ($option->getValues() as $value) {
                            // Convert numeric index to alphabet (0=>A, 1=>B, etc)
                            $alphabetIndex = $alphabet[$valueIndex];
                            $details[] = sprintf(
                                '%s|%s|%s|%s',
                                strtolower($alphabetIndex), // Corresponded Alphabet
                                $value->getTitle(), // Option Name
                                $value->getSku(), // SKU
                                $value->getIsVisible() ? 'Enable' : 'Disable' // Is Visible
                            );
                            $valueIndex++;
                        }
                        $row[] = implode(",", $details);
                    } else {
                        $row[] = sprintf(
                            'a|%s|%s|%s',
                            $option->getTitle(),
                            $option->getSku() ?: '',
                            'Enable'
                        );
                    }
                } else {
                    $row[] = '';
                }
            } elseif ($column == 'option_type') {
                if (empty($customOptions)) {
                    $row[] = '';
                    continue;
                }
                // Assuming all options are of the same type
                $row[] = 'drop_down';
            } elseif ($column == 'variation_sku') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_sku'])) {
                    $data = $variationData['variation_sku'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_qty') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_qty'])) {
                    $data = $variationData['variation_qty'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_follow_simple_sku_cost') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_follow_simple_sku_cost'])) {
                    $data = $variationData['variation_follow_simple_sku_cost'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_cost_setting') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_cost_setting'])) {
                    $data = $variationData['variation_cost_setting'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_commission_rate') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_commission_rate'])) {
                    $data = $variationData['variation_commission_rate'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_cost') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_cost'])) {
                    $data = $variationData['variation_cost'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_price') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_price'])) {
                    $data = $variationData['variation_price'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_follow_simple_sku_price') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_follow_simple_sku_price'])) {
                    $data = $variationData['variation_follow_simple_sku_price'];
                }
                $row[] = $data;
            } elseif ($column == 'variation_images') {
                $data = '';
                if (!empty($variationData) && isset($variationData['variation_images'])) {
                    $data = $variationData['variation_images'];
                }
                $row[] = $data;
            } elseif ($column == 'stock') {
                // Get salable quantity for product
                //$row[] = $stockItem->getQty();
                $row[] = $catalogProductSalableQty;
            } elseif ($column == 'is_in_stock') {
                $row[] = $stockItem->getIsInStock() ? __('In Stock') : __('Out of Stock');
            } elseif ($column == 'product_discount_limit') {
                $row[] = $discountLimit;
            } elseif ($column == 'redeem_type') {
                $row[] = $redeemType;
            } elseif ($column == 'point_value') {
                $row[] = $pointValue;
            } elseif ($column == 'related_skus') {
                $relatedData = '';
                $relatedProducts = $catalogProduct->getRelatedProducts();
                if (!empty($relatedProducts)) {
                    $tmpData = [];
                    foreach ($relatedProducts as $relatedProduct) {
                        $tmpData[] = $relatedProduct->getSku();
                    }
                    $relatedData = (!empty($tmpData)) ? implode(',', $tmpData) : '';
                }
                $row[] = $relatedData;
            } else {
                $data = $document->getData($column);
                if ($data && is_numeric(trim($data))) {
                    $row[] = trim($data);
                } else {
                    $row[] = $data;
                }
            }
        }

        return $row;
    }

    /**
     * Returns XML file
     *
     * @param array $productIds
     * @param bool $isAdmin
     * @return array
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function getXlsFile(array $productIds = [], bool $isAdmin = false): array
    {
        list('header' => $header, 'filter' => $columns) = $this->getAttributeMapping($isAdmin);
        $this->getAttributeInfo($columns);

        if (!empty($productIds)) {
            $collection = $this->collectionFactory->create()->addFieldToFilter('entity_id', ['in' => $productIds]);
        } else {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        }
        $collection->addAttributeToSelect(['*']);
        $collection->addMediaGalleryData();

        // Create an array to store column data and mark columns with data
        $columnData = [];
        $hasDataColumns = array_flip($columns); // Use array_flip to optimize key checking

        // only loop to get data, do not need to store all data in memory
        foreach ($collection as $item) {
            $rowData = $this->getRowData($item);
            $columnData[] = $rowData;
            foreach ($rowData as $index => $value) {
                if ($value !== '' && $value !== null) {
                    $hasDataColumns[$columns[$index]] = true;
                }
            }
        }

        // filter header and columns base on hasDataColumns
        $filteredHeader = [];
        $this->filteredColumns = []; // Reset filtered columns
        foreach ($columns as $index => $column) {
            // Keep non-option columns and A-C option columns
            if (!preg_match('/^option_(title|detail)_[D-Z]$/', $column)) {
                $filteredHeader[] = $header[$index];
                $this->filteredColumns[] = $column;
            }
            // Only include D-Z option columns if they have data
            else {
                if (isset($hasDataColumns[$column]) && $hasDataColumns[$column] === true) {
                    $filteredHeader[] = $header[$index];
                    $this->filteredColumns[] = $column;
                }
            }
        }

        // Generate unique filename (non-security usage)
        $name = bin2hex(random_bytes(16));
        $file = 'export/product-update'. $name . '.xls';

        /** @var SearchResultIterator $searchResultIterator */
        $searchResultIterator = $this->iteratorFactory->create(['items' => $columnData]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator' => $searchResultIterator,
            'rowCallback'=> function($row) {
                // just return the columns that are in filteredColumns
                $filteredRow = [];
                foreach ($this->filteredColumns as $column) {
                    // Find the index of the column in the original columns array
                    $originalIndex = array_search($column, $this->fields);
                    if ($originalIndex !== false) {
                        $filteredRow[] = $row[$originalIndex] ?? '';
                    }
                }
                return $filteredRow;
            },
        ]);

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $excel->setDataHeader($filteredHeader);
        $excel->write($stream, 'product-update.xls');

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }

    protected function removeLastCharacter($string)
    {
        $lastChar = substr($string, -1);
        if ($lastChar === '_') {
            $string = substr($string, 0, -1);
        }
        return $string;
    }

    protected function buildVariationData($variations, $variationCombFromOption)
    {
        $data = [];
        $variation_skus = [];
        $variation_images = [];
        $variation_follow_simple_sku_price_flag = false;
        $variation_price = [];
        $variation_cost = [];
        $variation_commission_rate = [];
        $variation_cost_setting = [];
        $variation_follow_simple_sku_cost_flag = false;
        $variation_qty = [];
        foreach ($variations as $item) {
            if (isset($variationCombFromOption[$item->getComb()])) {
                $alphabet = $variationCombFromOption[$item->getComb()];
                // mark flag if any variation follows simple sku cost and price setting
                if ((int)$item->getFollowSimpleSkuCostSetting() === 1) {
                    $variation_follow_simple_sku_cost_flag = true;
                }
                if ((int)$item->getFollowSimpleSkuPriceSetting() === 1) {
                    $variation_follow_simple_sku_price_flag = true;
                }
                $variation_commission_rate[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getCommissionPercent()
                );
                $variation_cost[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getCost()
                );
                $variation_cost_setting[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getCostSetting() ? 'Fixed Commission' : 'Manually Input'
                );
                $variation_price[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getPrice()
                );
                $variation_skus[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getSku()
                );
                if(!empty($item->getImage())) {
                    $variation_images[] = sprintf(
                        '%s|%s',
                        $alphabet,
                        $item->getImage() ? str_replace(',',';', $item->getImage()) : ''
                    );
                }
                $variation_qty[] = sprintf(
                    '%s|%s',
                    $alphabet,
                    $item->getStock()
                );
            }
        }
        $data['variation_sku'] = implode(',', $variation_skus);
        $data['variation_images'] = implode(',', $variation_images);
        $data['variation_follow_simple_sku_price'] = $variation_follow_simple_sku_price_flag ? '1' : '0';
        $data['variation_price'] = implode(',', $variation_price);
        $data['variation_cost'] = implode(',', $variation_cost);
        $data['variation_commission_rate'] = implode(',', $variation_commission_rate);
        $data['variation_cost_setting'] = implode(',', $variation_cost_setting);
        $data['variation_follow_simple_sku_cost'] = $variation_follow_simple_sku_cost_flag ? '1' : '0';
        $data['variation_qty'] = implode(',', $variation_qty);
        return $data;
    }

    /**
     * Retrieve config for attributes mapping.
     *
     * @param bool $isAdmin
     * @return array
     */
    public function getAttributeMapping(bool $isAdmin = false): array
    {
        $items = [];
        $result = ['header' => [], 'filter' => []];
        $optionAttrTitle = $optionAttrData = '';
        $configs = (string)$this->scopeConfig->getValue(HelperData::XML_PATH_MAPPING_ATTRIBUTE);
        if (!empty($configs) && $configs !== '[]') {
            foreach ($this->json->unserialize($configs) as $item) {
                $items[] = $item;
            }
        }
        foreach ($items as $attribute) {
            if ($attribute['attribute'] == 'option_title') {
                $optionAttrTitle = trim($attribute['title']);
                continue;
            }
            if ($attribute['attribute'] == 'option_detail') {
                $optionAttrData = trim($attribute['title']);
                foreach (range('A', 'C') as $letter) {
                    if (!empty($optionAttrTitle) && !empty($optionAttrData)) {
                        if(str_contains($optionAttrTitle, '{x}')) {
                            $result['header'][] = str_replace('{x}', $letter, $optionAttrTitle);
                            $result['filter'][] = 'option_title_'.$letter;
                        }
                        if(str_contains($optionAttrData, '{x}')) {
                            $result['header'][] = str_replace('{x}', $letter, $optionAttrData);
                            $result['filter'][] = 'option_detail_'.$letter;
                        }
                    }
                }
                continue;
            }
            $result['header'][] = trim($attribute['title']);
            $result['filter'][] = $attribute['attribute'];
        }
        foreach (range('D', 'Z') as $letter) {
            if (!empty($optionAttrTitle) && !empty($optionAttrData)) {
                if(str_contains($optionAttrTitle, '{x}')) {
                    $result['header'][] = str_replace('{x}', $letter, $optionAttrTitle);
                    $result['filter'][] = 'option_title_'.$letter;
                }
                if(str_contains($optionAttrData, '{x}')) {
                    $result['header'][] = str_replace('{x}', $letter, $optionAttrData);
                    $result['filter'][] = 'option_detail_'.$letter;
                }
            }
        }
        if ($isAdmin) {
            $result['header'][] = '店家名稱';
            $result['filter'][] = 'seller_shop_name';
        }
        $this->fields = $result['filter'];
        return $result;
    }

    /**
     * Get Attribute Info With Attribute Set Id
     *
     * @param array $attributes
     * @return array
     * @throws LocalizedException
     */
    public function getAttributeInfo($attributes)
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $attributeInfo = $this->eavConfig->getAttribute('catalog_product', $attribute);
            if (!$attributeInfo) {
                continue;
            }
            $result[$attribute]['type'] = $attributeInfo->getFrontendInput();
            if ($attributeInfo->getFrontendInput() == 'select' || $attributeInfo->getFrontendInput() == 'multiselect') {
                $result[$attribute]['options'] = $attributeInfo->getSource()->getAllOptions();
            }
        }
        $this->attributes = $result;
    }
}
