<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProductImport\Plugin\Webkul\MpMassUpload\Helper;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\StoreChangedData;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProductImport\Model\FormatGroupedProduct;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Model\ProductOptions\Config;
use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as ReportHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Bundle\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory as OptionFactory;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\OptionFactory as ProductOptionFactory;
use Magento\Bundle\Model\Option;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Branch8\MarketplaceProduct\Rewrite\SaveProduct;
use Branch8\MarketplaceProductImport\Model\Entity\Update\Save as StagingUpdateSave;
use Magento\Framework\Xml\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Webkul\MpMassUpload\Api\AttributeMappingRepositoryInterface;
use Webkul\MpMassUpload\Api\ProfileRepositoryInterface;
use Webkul\MpMassUpload\Helper\Data as MpMassUploadHelper;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollection;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Webkul\Marketplace\Model\ProductFactory as MpProduct;
use Magento\Framework\App\State;
use Branch8\MarketplaceProductImport\Model\FormatBundleProduct;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Magento\Staging\Model\Entity\Upcoming\SearchResultFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Branch8\MarketplaceProduct\Model\Config as B8MpConfig;
use Webkul\OptionsWithStockAndImages\Helper\Data as OptionsWithStockAndImagesHelper;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\Framework\App\ResourceConnection;
use Webkul\SellerSubAccount\Helper\Data as SubAccountHelper;
use Akeneo\Component\SpreadsheetParser\SpreadsheetParser;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Data
{
    const XML_PATH_MAPPING_ATTRIBUTE = 'marketplace/massupload_customAttribute/mapping';

    protected $excludedAttributes = ['meta_keyword','meta_title', 'meta_description', 'special_from_date', 'special_to_date', 'qware_guid'];

    protected $hardRequiredAttributes = ['sku', 'short_description', 'flagstore_category', 'cost', 'cost_setting', 'commission_percent', 'weight', 'point_money_config_type'];
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var AttributeMappingRepositoryInterface
     */
    protected $attributeMappingRepository;

    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var UploaderFactory
     */
    protected $fileUploader;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var AttributeCollection
     */
    protected AttributeCollection $attributeCollection;

    /**
     * @var EavConfig
     */
    protected EavConfig $eavConfig;

    /**
     * @var ProfileRepositoryInterface
     */
    protected ProfileRepositoryInterface $_profileRepository;

    /**
     * @var AttributeSetCollection
     */
    protected AttributeSetCollection $_attributeSetCollection;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $_customerSession;

    /**
     * @var SaveProduct
     */
    protected SaveProduct $_saveProduct;

    /**
     * @var StoreChangedData
     */
    protected StoreChangedData $storeChangedData;

    /**
     * @var MpProduct
     */
    protected MpProduct $_mpProduct;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var FormatBundleProduct
     */
    protected FormatBundleProduct $formatBundleProduct;

    /**
     * @var FormatGroupedProduct
     */
    protected FormatGroupedProduct $formatGroupedProduct;

    /**
     * @var Option
     */
    protected Option $_optionMod;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $_productRepository;

    /**
     * @var OptionFactory
     */
    protected OptionFactory $_optionFactory;

    /**
     * @var LinkFactory
     */
    protected LinkFactory $_linkFactory;

    /**
     * @var MpProductFactory
     */
    private MpProductFactory $mpProductFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $_storeManager;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezone;

    /**
     * @var StagingUpdateSave
     */
    protected StagingUpdateSave $stagingUpdateSave;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var SearchResultFactory
     */
    protected SearchResultFactory $searchResultFactory;

    /**
     * @var AdminSession
     */
    protected AdminSession $adminSession;

    /**
     * @var B8MpConfig
     */
    protected B8MpConfig $b8MpConfig;

    /**
     * @var MarketplaceHelperData
     */
    protected MarketplaceHelperData $marketplaceHelperData;

    /**
     * @var Salable
     */
    protected Salable $salable;

    /**
     * @var Filesystem\DirectoryList
     */
    public Filesystem\DirectoryList $directoryList;

    /**
     * @var OptionsWithStockAndImagesHelper
     */
    protected OptionsWithStockAndImagesHelper $optionsWithStockAndImagesHelper;

    /**
     * @var ProductOptionFactory
     */
    protected ProductOptionFactory $productOptionFactory;

    /**
     * @var VariationsFactory
     */
    protected VariationsFactory $variationFactory;

    /**
     * @var SwatchFactory
     */
    protected SwatchFactory $swatchFactory;

    /**
     * @var ProductFactory
     */
    protected ProductFactory $_product;

    /**
     * @var WriteInterface
     */
    public $mediaDirectory;

    /**
     * @var AttributeSetRepositoryInterface
     */
    private AttributeSetRepositoryInterface $attributeSetRepository;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    private ProductCustomOptionRepositoryInterface $productOptionRepository;

    protected ?string $productType = null;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var SubAccountHelper
     */
    private SubAccountHelper $subAccountHelper;

    /**
     * @var ReportHelper
     */
    private ReportHelper $reportHelper;

    /**
     * @var ProductChangeLogInterfaceFactory
     */
    private ProductChangeLogInterfaceFactory $productChangeLogFactory;

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var ProductVersionRepositoryInterface
     */
    protected ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    protected GetProductLogEntryByProductId $getProductLogEntryByProductId;
    /**
     * @var MarketplaceStagingHelper
     */
    protected $marketplaceStagingHelper;

    private array $requiredImportAttributeCache = [];

    /**
     * Data constructor.
     *
     * @param Filesystem $filesystem
     * @param Parser $parser
     * @param AttributeMappingRepositoryInterface $attributeMappingRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeCollection $attributeCollection
     * @param EavConfig $eavConfig
     * @param ProfileRepositoryInterface $profileRepository
     * @param AttributeSetCollection $attributeSetCollectionFactory
     * @param CustomerSession $customerSession
     * @param SaveProduct $saveProduct
     * @param StoreChangedData $storeChangedData
     * @param MpProduct $mpProduct
     * @param State $state
     * @param FormatBundleProduct $formatBundleProduct
     * @param FormatGroupedProduct $formatGroupedProduct
     * @param Option $optionMod
     * @param ProductRepository $productRepository
     * @param OptionFactory $optionFactory
     * @param LinkFactory $linkFactory
     * @param MpProductFactory $mpProductFactory
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param StagingUpdateSave $stagingUpdateSave
     * @param RequestInterface $request
     * @param SearchResultFactory $searchResultFactory
     * @param AdminSession $adminSession
     * @param B8MpConfig $b8MpConfig
     * @param MarketplaceHelperData $marketplaceHelperData
     * @param Salable $salable
     * @param Filesystem\DirectoryList $directoryList
     * @param OptionsWithStockAndImagesHelper $optionsWithStockAndImagesHelper
     * @param ProductOptionFactory $productOptionFactory
     * @param VariationsFactory $variationFactory
     * @param SwatchFactory $swatchFactory
     * @param ProductFactory $productFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param ProductCustomOptionRepositoryInterface $productOptionRepository
     * @param ResourceConnection $resourceConnection
     * @param SubAccountHelper $subAccountHelper
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param ReportHelper $reportHelper
     * @param MarketplaceStagingHelper $marketplaceStagingHelper
     * @param Json|null $json
     * @param UploaderFactory|null $fileUploader
     * @throws FileSystemException
     */
    public function __construct (
        Filesystem $filesystem,
        Parser $parser,
        AttributeMappingRepositoryInterface $attributeMappingRepository,
        ScopeConfigInterface $scopeConfig,
        AttributeCollection $attributeCollection,
        EavConfig                  $eavConfig,
        ProfileRepositoryInterface $profileRepository,
        AttributeSetCollection     $attributeSetCollectionFactory,
        CustomerSession            $customerSession,
        SaveProduct                $saveProduct,
        StoreChangedData           $storeChangedData,
        MpProduct                  $mpProduct,
        State                      $state,
        FormatBundleProduct        $formatBundleProduct,
        FormatGroupedProduct       $formatGroupedProduct,
        Option                     $optionMod,
        ProductRepository          $productRepository,
        OptionFactory              $optionFactory,
        LinkFactory                $linkFactory,
        MpProductFactory           $mpProductFactory,
        StoreManagerInterface      $storeManager,
        DateTime                   $dateTime,
        TimezoneInterface          $timezone,
        StagingUpdateSave          $stagingUpdateSave,
        RequestInterface           $request,
        SearchResultFactory        $searchResultFactory,
        AdminSession $adminSession,
        B8MpConfig $b8MpConfig,
        MarketplaceHelperData $marketplaceHelperData,
        Salable $salable,
        Filesystem\DirectoryList $directoryList,
        OptionsWithStockAndImagesHelper $optionsWithStockAndImagesHelper,
        ProductOptionFactory $productOptionFactory,
        VariationsFactory $variationFactory,
        SwatchFactory $swatchFactory,
        ProductFactory $productFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        ProductCustomOptionRepositoryInterface $productOptionRepository,
        ResourceConnection $resourceConnection,
        SubAccountHelper $subAccountHelper,
        ProductChangeLogInterfaceFactory $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository,
        MarketplaceProductManagement $marketplaceProductManagement,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        ReportHelper $reportHelper,
        MarketplaceStagingHelper $marketplaceStagingHelper,
        Json $json = null,
        UploaderFactory $fileUploader = null
    ){
        $this->filesystem = $filesystem;
        $this->parser = $parser;
        $this->attributeMappingRepository = $attributeMappingRepository;
        $this->scopeConfig = $scopeConfig;
        $this->attributeCollection = $attributeCollection;
        $this->eavConfig = $eavConfig;
        $this->_profileRepository = $profileRepository;
        $this->_attributeSetCollection = $attributeSetCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_saveProduct = $saveProduct;
        $this->storeChangedData = $storeChangedData;
        $this->_mpProduct = $mpProduct;
        $this->state = $state;
        $this->formatBundleProduct = $formatBundleProduct;
        $this->formatGroupedProduct = $formatGroupedProduct;
        $this->_optionMod = $optionMod;
        $this->_productRepository = $productRepository;
        $this->_optionFactory = $optionFactory;
        $this->_linkFactory = $linkFactory;
        $this->mpProductFactory = $mpProductFactory;
        $this->_storeManager = $storeManager;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->stagingUpdateSave = $stagingUpdateSave;
        $this->request = $request;
        $this->searchResultFactory = $searchResultFactory;
        $this->adminSession = $adminSession;
        $this->b8MpConfig = $b8MpConfig;
        $this->marketplaceHelperData = $marketplaceHelperData;
        $this->salable = $salable;
        $this->directoryList = $directoryList;
        $this->optionsWithStockAndImagesHelper = $optionsWithStockAndImagesHelper;
        $this->productOptionFactory = $productOptionFactory;
        $this->variationFactory = $variationFactory;
        $this->swatchFactory = $swatchFactory;
        $this->_product = $productFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->attributeSetRepository = $attributeSetRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->resourceConnection = $resourceConnection;
        $this->subAccountHelper = $subAccountHelper;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->reportHelper = $reportHelper;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->fileUploader = $fileUploader ?: ObjectManager::getInstance()->get(UploaderFactory::class);
    }

    /**
     * Get Csv Product Type
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param mixed $uploadedFileRowData
     *
     * @return string
     */
    public function aroundGetProductType(
        MpMassUploadHelper $subject,
        callable $proceed,
        mixed $uploadedFileRowData
    ) {
        if ($subject->getCount($uploadedFileRowData) > 0) {
            if (in_array('weight', $uploadedFileRowData[0])) {
                if (in_array('_super_attribute_code', $uploadedFileRowData[0])) {
                    return 'configurable';
                } elseif (in_array('bundle_values', $uploadedFileRowData[0])) {
                    return 'bundle';
                } elseif (in_array('associated_skus', $uploadedFileRowData[0])) {
                    return 'grouped';
                }
                $key = array_search('weight', $uploadedFileRowData[0]);
                if (isset($uploadedFileRowData[1][$key]) && $uploadedFileRowData[1][$key] < 0.0000001) {
                    $keySku = array_search('sku', $uploadedFileRowData[0]);
                    if (!empty($uploadedFileRowData[1][$keySku]) && str_contains($uploadedFileRowData[1][$keySku], 'HOTAI')) {
                        try {
                            $product = $this->_productRepository->get($uploadedFileRowData[1][$keySku]);
                            if ($product->getTypeId() == 'simple') {
                                return 'simple';
                            }
                        } catch (NoSuchEntityException $e) {
                        }
                    }
                    return 'virtual';
                }
                return 'simple';
            } else {
                if (in_array('downloadable_link_file', $uploadedFileRowData[0])) {
                    return 'downloadable';
                } elseif (in_array('_super_attribute_code', $uploadedFileRowData[0])) {
                    return 'configurable';
                } elseif (in_array('bundle_values', $uploadedFileRowData[0])) {
                    return 'bundle';
                } elseif (in_array('associated_skus', $uploadedFileRowData[0])) {
                    return 'grouped';
                } else {
                    return 'virtual';
                }
            }
        }
        return '';
    }

    /**
     * Get Custom Attribute List
     *
     * @return array
     */
    public function aroundGetCustomAttributeList(
        MpMassUploadHelper $subject,
        callable $proceed
    ) {
        $attributeIds = [];
        if ($subject->canSaveCustomAttribute()) {
            try {
                $attributes = $this->attributeCollection->create();
                foreach ($attributes as $attribute) {
                    if ($attribute->getAttributeCode() == 'tier_price' || $attribute->getAttributeCode() == 'tax_class_id') {
                        $attributeIds[] = $attribute->getAttributeId();
                        continue;
                    }
                    if (!in_array($attribute->getAttributeCode(), MarketplaceStagingHelper::ALLOW_ATTRIBUTE_LIST)
                        && ($attribute->getIsUserDefined() == 0
                            || $attribute->getAttributeCode() == 'product_type')
                    ) {
                        continue;
                    }
                    $applyTo = $attribute->getApplyTo();
                    if (!empty($applyTo) && $this->productType && !in_array($this->productType, $applyTo)) continue;
                    $attributeIds[] = $attribute->getAttributeId();
                }
            } catch (\Exception $e) {
                $attributeIds = [];
            }
        }
        return $attributeIds;
    }

    /**
     * Check Whether you Can Save Custom Attribute or Not
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @return bool
     */
    public function aroundCanSaveCustomAttribute(
        MpMassUploadHelper $subject,
        callable $proceed
    ): bool {
        $validateCustomAttribute = $this->scopeConfig->getValue(
            'marketplace/massupload_customAttribute/validate_custom_attribute',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return (bool)$validateCustomAttribute;
    }

    /**
     * Check Whether Can Save Custom Options or Not
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @return bool
     */
    public function aroundCanSaveCustomOption (
        MpMassUploadHelper $subject,
        callable $proceed
    ): bool
    {
        return true;
    }

    public function aroundProcessSpecialPriceData(
        MpMassUploadHelper $subject,
        callable $proceed,
        $wholeData, $data, $flag = 0
    )
    {
        return $wholeData;

    }


    public function aroundPrepareProductDataIfNotSet(
        MpMassUploadHelper $subject,
        callable $proceed,
        $data, $profileType
    )
    {
        return $data;

    }





    /**
     * Get Option id by Option Label
     *
     * @param string $attributeCode
     * @param array $optionLabelArray
     * @return array
     */
    public function aroundGetOptionIdByLabel(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $attributeCode, $optionLabelArray
    ){
        $optionIdArray = [];
        $index = 0;
        if (!is_array($optionLabelArray)) {
            $_product = $this->_product->create();
            $isAttributeExist = $_product->getResource()->getAttribute($attributeCode);
            $optionId = '';
            if ($isAttributeExist && $isAttributeExist->usesSource()) {
                $optionId = $isAttributeExist->getSource()->getOptionId($optionLabelArray);
            }
            return $optionId[0]['value'] ?? $optionId;
        }
        $_product = $this->_product->create();
        $isAttributeExist = $_product->getResource()->getAttribute($attributeCode);
        foreach ($optionLabelArray as $optionLabel) {
            if ($isAttributeExist && $isAttributeExist->usesSource()) {
                $optionId = $isAttributeExist->getSource()->getOptionId($optionLabel);
                $optionIdArray[$index] = $optionId[0]['value'] ?? $optionId;
                $index++;
            }
        }
        return $optionIdArray;
    }

    /**
     * Get Option id by Option Label
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param string $attributeCode
     * @param array $optionLabelArray
     * @return string|int
     * @throws LocalizedException
     */
    public function aroundGetOptionIdByLabelSelect (
        MpMassUploadHelper $subject,
        callable           $proceed,
        string             $attributeCode,
        array              $optionLabelArray
    ): string|int {
        $optionIdArray = '';
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        if (!is_array($optionLabelArray)) {
            $optionId = '';
            if ($attribute && $attribute->usesSource()) {
                $optionId = $attribute->getSource()->getOptionId(trim($optionLabelArray));
            }
            return $optionId;
        }
        foreach ($optionLabelArray as $optionLabel) {
            if ($attribute && $attribute->usesSource()) {
                foreach ($attribute->getSource()->getAllOptions() as $option) {
                    if ($option['label'] == trim($optionLabel)) {
                        $optionIdArray = $option['value'];
                        break;
                    } elseif (is_object($option['label']) && $option['label']->__toString() == trim($optionLabel)) {
                        if (is_object($option['value'])) {
                            $optionIdArray = $option['value']->getValue();
                        } else {
                            $optionIdArray = $option['value'];
                        }
                        break;
                    }
                }
            }
        }
        return $optionIdArray;
    }

    public function getReservedQuantityBySku(string $sku): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');

        $reservedQty = $connection->fetchOne($select);

        return $reservedQty !== false ? (int)$reservedQty : 0;
    }

    /**
     * Calculate Product Row Data
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param int $sellerId
     * @param int $profileId
     * @param int $row
     * @param string $profileType
     *
     * @return array
     */
    public function aroundCalculateProductRowData(
        MpMassUploadHelper $subject,
        callable           $proceed,
        int                $sellerId,
        int                $profileId,
        int                $row,
        string             $profileType
    ) {
        $this->productType = $profileType;
        $uploadedFileRowData = $subject->getUploadedFileRowData($profileId);
        $mainRow = $row;
        $isConfigurableAllowed = $subject->isProductTypeAllowed('configurable');
        $isBundleAllowed = $subject->isProductTypeAllowed('bundle');
        $isGroupedAllowed = $subject->isProductTypeAllowed('grouped');
        if (($profileType == 'configurable' && $isConfigurableAllowed)) {
            $rowIndexArr = $subject->getConfigurableFormatCsv($uploadedFileRowData, 1);
            if (!empty($rowIndexArr[$row])) {
                $row = $rowIndexArr[$row];
            }
            $childRowIndexArr = $subject->getConfigurableFormatCsv($uploadedFileRowData, 0);
            if (!empty($childRowIndexArr[$mainRow])) {
                $childRowArr = $childRowIndexArr[$mainRow];
            } else {
                $childRowArr = [];
            }
        }
        if (!array_key_exists($row, $uploadedFileRowData)) {
            $wholeData['error'] = 1;
            $wholeData['msg'] = __('Product data for row %1 does not exist', $mainRow);
        }
        // Prepare product row data
        $i=0;
        $j=0;
        $data = [];
        if (!empty($uploadedFileRowData[$row])) {
            $data = $uploadedFileRowData[$row];
        }
        $customData = [];
        $customData['product'] = [];
        $csvAttributeList = [];
        foreach ($uploadedFileRowData[0] as $value) {
            $csvAttributeList[$value] = $value;
            if ($value === 'stock' && is_numeric($data[$i])) {
                $customData['product'][$value] = is_float($data[$i]) ? (float)$data[$i] : (int)$data[$i];
            } elseif (!empty($data[$i])) {
                $customData['product'][$value] = $data[$i];
            } else {
                $customData['product'][$value] = '';
            }
            $i++;
        }
        $data = $customData;
        if (!isset($data['product']['weight'])) {
            $data['product']['weight'] = '';
        }
        if (!isset($data['product']['images']) || $data['product']['images'] === '') {
            $data['product']['images'] = '';
        }
        $validate = $subject->validateFields(
            $data,
            $profileType,
            $mainRow
        );
        $wholeData['type'] = $profileType;
        if ($validate['error']) {
            $wholeData['error'] = $validate['error'];
            $wholeData['msg'] = $validate['msg'];
            return $wholeData;
        }
        $data = $validate['data'];
        /*Calculate product weight*/
        $hasWeight = 1;
        $isDownloadableAllowed = $subject->isProductTypeAllowed('downloadable');
        $isVirtualAllowed = $subject->isProductTypeAllowed('virtual');
        if (($profileType == 'virtual' && $isVirtualAllowed) ||
            ($profileType == 'downloadable' && $isDownloadableAllowed) ||
            !$data['product']['weight']) {
            $weight = 0;
            $hasWeight = 0;
        } else {
            $weight = $data['product']['weight'];
        }
        if (isset($data['product']['point_money_config_type']) && $data['product']['point_money_config_type']) {
            if ($data['product']['point_money_config_type'] == 'Free ratio without limit'
                || $data['product']['point_money_config_type'] == '自由比例無限制'
                || $data['product']['point_money_config_type'] == __('Free ratio without limit')->__toString()
                || strtolower($data['product']['point_money_config_type']) == 'free ratio without limit') {
                $data['product']['point_money_config_type'] = __('Free ratio without limit')->render();
            } elseif ($data['product']['point_money_config_type'] == 'Only money'
                || $data['product']['point_money_config_type'] == '僅用現金'
                || $data['product']['point_money_config_type'] == __('Only money')->__toString()
                || strtolower($data['product']['point_money_config_type']) == 'only money') {
                $data['product']['point_money_config_type'] = __('Only money')->render();
            } elseif ($data['product']['point_money_config_type'] == 'Only point'
                || $data['product']['point_money_config_type'] == '僅用點數'
                || $data['product']['point_money_config_type'] == __('Only point')->__toString()
                || strtolower($data['product']['point_money_config_type']) == 'only point') {
                $data['product']['point_money_config_type'] = __('Only point')->render();
            } elseif ($data['product']['point_money_config_type'] == '點金設定-上下限設定'
                || $data['product']['point_money_config_type'] == '點金自由'
                || $data['product']['point_money_config_type'] == __('Free ratio')->__toString()
                || strtolower($data['product']['point_money_config_type']) == 'free ratio') {
                $data['product']['point_money_config_type'] = __('Free ratio')->render();
                if (isset($data['product']['product_discount_limit']) && $data['product']['product_discount_limit']) {
                    $redeemType = __('Redeem limit type: point')->render();
                    if (trim($data['product']['redeem_type']) == '百分比' || trim(strtolower($data['product']['redeem_type'])) == 'percentage' || trim(strtolower($data['product']['redeem_type'])) == 'percent') {
                        $redeemType = __('Redeem limit type: percentage')->render();
                    }
                    if (trim($data['product']['product_discount_limit']) == '下限' || trim(strtolower($data['product']['product_discount_limit'])) == 'lower limit') {
                        $data['product']['point_money_config_free_ratio_lower_redeem_limit_type'] = $redeemType;
                        $data['product']['point_money_config_free_ratio_lower_redeem_limit_value'] = $data['product']['point_value'] ?? 0;
                        $data['product']['point_money_config_free_ratio_upper_redeem_limit_type'] = $redeemType;
                        $data['product']['point_money_config_free_ratio_upper_redeem_limit_value'] = 0;
                    } elseif (trim($data['product']['product_discount_limit']) == '上限' || trim(strtolower($data['product']['product_discount_limit'])) == 'upper limit') {
                        $data['product']['point_money_config_free_ratio_lower_redeem_limit_type'] = $redeemType;
                        $data['product']['point_money_config_free_ratio_lower_redeem_limit_value'] = 0;
                        $data['product']['point_money_config_free_ratio_upper_redeem_limit_type'] = $redeemType;
                        $data['product']['point_money_config_free_ratio_upper_redeem_limit_value'] = $data['product']['point_value'] ?? 0;
                    }
                }
            }
        }
        if (isset($data['product']['cost_setting']) && $data['product']['cost_setting']) {
            if ($data['product']['cost_setting'] == '固定毛利'
                || $data['product']['cost_setting'] == '固定抽成'
                || strtolower($data['product']['cost_setting']) == 'fixed commission') {
                $data['product']['cost_setting'] = __('Fixed commission')->render();
                if (isset($data['product']['commission_percent']) && isset($data['product']['special_price']) && $data['product']['special_price'] > 0) {
                    $commissionPercent = ($data['product']['commission_percent'] && is_numeric(trim($data['product']['commission_percent']))) ? (float) trim($data['product']['commission_percent']) : 0;
                    $data['product']['cost'] = (100 - $commissionPercent) / 100 * $data['product']['special_price'];
                }
            } elseif ($data['product']['cost_setting'] == '手動輸入'
                || strtolower($data['product']['cost_setting']) == 'manually input') {
                $data['product']['cost_setting'] = __('Manually input')->render();
                if (isset($data['product']['cost']) && isset($data['product']['special_price']) && $data['product']['special_price'] > 0) {
                    $cost = ($data['product']['cost'] && is_numeric(trim($data['product']['cost']))) ? (float) trim($data['product']['cost']) : 0;
                    $commissionPercent = ($data['product']['special_price'] - $cost) / $data['product']['special_price'] * 100;
                    if ($commissionPercent > 0)
                        $commissionPercent = round($commissionPercent);
                    $data['product']['commission_percent'] = $commissionPercent;
                }
            }
        }
        /*Get Category ids by category name (set by comma seperated)*/
        $categoryIds = (isset($data['product']['main_category']) && $data['product']['main_category']) ? $subject->getCategoryIds($data['product']['main_category']) : [];
        if (!empty($categoryIds)) {
            $data['product']['main_category'] = end($categoryIds);
            $categoryIds = [$data['product']['main_category']];
        }
        $categoryFsIds = (isset($data['product']['flagstore_category']) && $data['product']['flagstore_category']) ? $subject->getCategoryIds($data['product']['flagstore_category']) : [];
        if (!empty($categoryFsIds)) {
            $data['product']['flagstore_category'] = end($categoryFsIds);
            $categoryIds = !empty($categoryIds) ? array_merge($categoryIds, [$data['product']['flagstore_category']]) : [$data['product']['flagstore_category']];
        }
        if (isset($data['product']['category_ids']) && trim($data['product']['category_ids'])) {
            $categoryIds = !empty($categoryIds) ? array_merge($categoryIds, $subject->getCategoryIds($data['product']['category_ids'])) : $subject->getCategoryIds($data['product']['category_ids']);
        }
        /*Get $taxClassId by tax*/
        if (__('Taxable Goods')->render() == trim($data['product']['tax_class_id'])
            || '含稅商品' == trim($data['product']['tax_class_id']) || '含稅' == trim($data['product']['tax_class_id'])) {
            $data['product']['tax_class_id'] = __('Taxable Goods')->render();
        }
        if (__('None')->render() == trim($data['product']['tax_class_id'])
            || __('Tax None')->render() == trim($data['product']['tax_class_id'])
            || '未稅' == trim($data['product']['tax_class_id']) || '無' == trim($data['product']['tax_class_id'])){
            $data['product']['tax_class_id'] = __('None')->render();
        }
        $taxClassId = $subject->getAttributeOptionIdbyOptionText(
            "tax_class_id",
            trim($data['product']['tax_class_id'] ?? __('Taxable Goods')->render())
        );
        $attributeSetId = $subject->getAttributeSetId($profileId);
        if (!isset($data['product_id'])) {
            $attributeTicketSets = $this->getTicketAttributeSet();
            if (in_array($attributeSetId, $attributeTicketSets)) {
                $data['product']['stock'] = 0;
            }
        }

        // Fix re-import product for variation product type or product ticket type with stock empty
        if(!isset($data['product']['stock']) || $data['product']['stock'] === '') {
            $data['product']['stock'] = '';
        }

        $isInStock = 1;
        if (!empty($data['product']['stock']) && !(int)$data['product']['stock']) {
            $isInStock = 0;
        } elseif (isset($data['product']['stock']) && $data['product']['stock']==='') {
            $data['product']['stock'] = '';
        }
        if (empty($data['product']['is_in_stock'])
            || $data['product']['is_in_stock'] == __('Out of Stock')->render()
            || (is_numeric($data['product']['stock']) && $data['product']['stock'] === 0)
        ) {
            $isInStock = 0;
        }
        $wholeData['form_key'] = $subject->getFormKey();
        $wholeData['set'] = $attributeSetId;
        $wholeData['block'] = !empty($data['block']) ? $data['block'] : '';
        if (!empty($data['id'])) {
            $wholeData['id'] = $data['id'];
            $wholeData['product_id'] = $data['product_id'];
            $wholeData['row_id'] = $data['row_id'];
            $wholeData['product']['website_ids'] = $data['product']['website_ids'];
            $wholeData['product']['url_key'] = $data['product']['url_key'];
            if (!empty($data['product']['weight']) && $data['product']['weight'] != 0) {
                $weight = $data['product']['weight'];
                $hasWeight = 1;
            }
        }
        if ($profileType == 'virtual' && $isVirtualAllowed && $attributeSetId > 1) {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
            $setName = $attributeSet->getAttributeSetName();
            if (!empty(\Branch8\MarketplaceStaging\Helper\Data::VIRTUAL_SET_MAP[$setName])) {
                $wholeData['product']['virtual_product_type'] = \Branch8\MarketplaceStaging\Helper\Data::VIRTUAL_SET_MAP[$setName];
            }
            $csvAttributeList['virtual_product_type'] = 'virtual_product_type';
            if (isset($data['product']['shipping_method']) && !trim($data['product']['shipping_method'])) {
                $data['product']['shipping_method'] = __('Electronic tickets')->render();
            }
            if (!empty($wholeData['product']['virtual_product_type']) && in_array($wholeData['product']['virtual_product_type'], VirtualProductType::TYPES_BATCH_IMPORT_TICKET)) {
                unset($data['product']['custom_option']);
                unset($data['product']['co_variation']);
                unset($wholeData['product']['custom_option']);
                unset($wholeData['product']['co_variation']);
            }
        }
        $wholeData['product']['category_ids'] = $categoryIds;
        $wholeData['product']['name'] = $data['product']['name'];
        $wholeData['product']['short_description'] = $data['product']['short_description'];
        $wholeData['product']['description'] = $data['product']['description'];
        if ($skuData = $data['product']['sku']){
            if (str_contains($data['product']['sku'], 'HOTAI')) {
                $check = explode('-', $data['product']['sku']);
                if (strlen($check[0]) < 6) {
                    $skuData = 'HOTAI' . time() . '-' . $data['product']['sku'];
                }
            } else {
                $skuData = 'HOTAI' . time() . '-' . $data['product']['sku'];
            }
        }
        if (!empty($data['product']['visibility'])) {
            if (__('Not Visible Individually')->render() == trim($data['product']['visibility'])) {
                $data['product']['visibility'] = 1;
            } elseif (__('Catalog')->render() == trim($data['product']['visibility'])) {
                $data['product']['visibility'] = 2;
            } elseif (__('Search')->render() == trim($data['product']['visibility'])) {
                $data['product']['visibility'] = 3;
            } else {
                $data['product']['visibility'] = 4;
            }
        }

        // Change stock product to import salable qty : current stock import - reservedQty
        // Ex : 30 - (-6) = 36 -> Total qty = 36 -> after import -> salable qty : 30
        if(isset($data['product']['stock']) || $data['product']['stock'] !== '') {
            $reservedQty = $this->getReservedQuantityBySku($data['product']['sku']);
            $data['product']['stock'] = (int)$data['product']['stock'] - $reservedQty;
        }

        $wholeData['product']['sku'] = $skuData;
        $wholeData['product']['price'] = $data['product']['price'];
        $wholeData['product']['special_price'] = $data['product']['special_price'];
        $wholeData['product']['visibility'] = $data['product']['visibility'] ?? 4;
        $wholeData['product']['tax_class_id'] = $taxClassId;
        $wholeData['product']['product_has_weight'] = $hasWeight;
        $wholeData['product']['weight'] = $weight;
        $wholeData['product']['stock_data']['manage_stock'] = 1;
        $wholeData['product']['stock_data']['use_config_manage_stock'] = 1;
        if (isset($data['product']['stock'])) {
            $wholeData['product']['quantity_and_stock_status']['qty'] = $data['product']['stock'];
        }
        $wholeData['product']['quantity_and_stock_status']['is_in_stock'] = $isInStock;
        if (!empty($data['product']['url_key'])) {
            $wholeData['product']['url_key'] = $data['product']['url_key'];
        }
        /*START :: Set Special Price Info*/
        $wholeData = $subject->processSpecialPriceData($wholeData, $data);
        /*Set Image Info*/
        $wholeData = $this->processVariationImageData($wholeData, $data, $profileId);
        $wholeData = $subject->processImageData($wholeData, $data, $profileId);
        if (!empty($wholeData['product']['media_gallery']['images']) && !empty($data['product']['dpa_image'])) {
            foreach ($wholeData['product']['media_gallery']['images'] as $key => $image) {
                if (isset($image['file']) && strpos($image['file'], $data['product']['dpa_image']) !== false) {
                    $data['product']['dpa_image'] = $image['file'];
                    break;
                }
            }
        } elseif (!empty($data['product']['dpa_image'])) {
            unset($data['product']['dpa_image']);
        }
        if (empty($wholeData['product']['swatch_image']) && !empty($wholeData['product']['thumbnail'])) {
            $wholeData['product']['swatch_image'] = $wholeData['product']['thumbnail'];
        }
        /*Set Downloadable Data*/
        if ($profileType == 'downloadable' && $isDownloadableAllowed) {
            $wholeData = $subject->processDownloadableData($wholeData, $data, $profileId);
        }
        /*Set Configurable Data*/
        if ($profileType == 'configurable' && $isConfigurableAllowed) {
            $wholeData = $subject->processConfigurableData(
                $wholeData,
                $data,
                $mainRow,
                $childRowArr,
                $uploadedFileRowData,
                $profileId
            );
        }
        /*Set Bundle Data*/
        if ($profileType == 'bundle' && $isBundleAllowed) {
            $wholeData = $this->formatBundleProduct->execute(
                $wholeData,
                $data['product']
            );
        }
        /*Set Grouped Data*/
        if ($profileType == 'grouped' && $isGroupedAllowed) {
            $wholeData = $this->formatGroupedProduct->execute(
                $wholeData,
                $data['product']
            );
        }
        /*Set Staging Data*/
        if (isset($data['product']['schedule_title']) && $data['product']['schedule_title']) {
            $wholeData['staging']['mode'] = 'save';
            $wholeData['staging']['name'] = $data['product']['schedule_title'];
            $wholeData['staging']['description'] = null;
            $wholeData['staging']['start_time'] = $this->timezone->convertConfigTimeToUtc(date('Y-m-d H:i:s', strtotime($data['product']['schedule_start_time'])));
            $wholeData['staging']['end_time'] = $data['product']['schedule_end_time'] ? $this->timezone->convertConfigTimeToUtc(date('Y-m-d H:i:s', strtotime($data['product']['schedule_end_time']))) : null;
        }
        /*Set Custom Attributes Values*/
        if ($subject->canSaveCustomAttribute()) {
            // $wholeData = $this->validateCsvForRequiredCustomAttributes($csvAttributeList, $wholeData);
            $wholeData = $subject->validateCsvForRequiredCustomAttributes($csvAttributeList, $wholeData, $attributeSetId);
            if (!isset($wholeData['error'])) {
                $wholeData = $subject->processCustomAttributeData($wholeData, $data, $mainRow);
            } else {
                return $wholeData;
            }
        }

        if (isset($data['product']['custom_option']) && $data['product']['custom_option']) {
            $wholeData['product']['custom_option'] = $data['product']['custom_option'];
        }

        if (isset($data['product']['co_variation']) && $data['product']['co_variation']) {
            $wholeData['product']['co_variation'] = $data['product']['co_variation'];
        }
        $rangeList = $this->salable->getRangeList();
        foreach ($rangeList as $letter) {
            if (isset($data['product']['option_title_'.$letter]) && $data['product']['option_title_'.$letter]) {
                $wholeData['product']['option_title_'.$letter] = $data['product']['option_title_'.$letter];
            }
            if (isset($data['product']['option_detail_'.$letter]) && $data['product']['option_detail_'.$letter]) {
                $wholeData['product']['option_detail_'.$letter] = $data['product']['option_detail_'.$letter];
            }
        }
        if (isset($data['product']['option_type']) && $data['product']['option_type']) {
            $wholeData['product']['option_type'] = $data['product']['option_type'];
        }
        if (isset($data['product']['variation_sku']) && $data['product']['variation_sku']) {
            $wholeData['product']['variation_sku'] = $data['product']['variation_sku'];
        }
        if (isset($data['product']['variation_qty']) && $data['product']['variation_qty']) {
            $wholeData['product']['variation_qty'] = $data['product']['variation_qty'];
        }
        if (isset($data['product']['variation_follow_simple_sku_cost']) && $data['product']['variation_follow_simple_sku_cost']) {
            $wholeData['product']['variation_follow_simple_sku_cost'] = $data['product']['variation_follow_simple_sku_cost'];
        }
        if (isset($data['product']['variation_cost_setting']) && $data['product']['variation_cost_setting']) {
            $wholeData['product']['variation_cost_setting'] = $data['product']['variation_cost_setting'];
        }
        if (isset($data['product']['variation_commission_rate']) && $data['product']['variation_commission_rate']) {
            $wholeData['product']['variation_commission_rate'] = $data['product']['variation_commission_rate'];
        }
        if (isset($data['product']['variation_cost']) && $data['product']['variation_cost']) {
            $wholeData['product']['variation_cost'] = $data['product']['variation_cost'];
        }
        if (isset($data['product']['variation_price']) && $data['product']['variation_price']) {
            $wholeData['product']['variation_price'] = $data['product']['variation_price'];
        }
        if (isset($data['product']['variation_follow_simple_sku_price']) && $data['product']['variation_follow_simple_sku_price']) {
            $wholeData['product']['variation_follow_simple_sku_price'] = $data['product']['variation_follow_simple_sku_price'];
        }
        if (isset($data['product']['variation_images']) && $data['product']['variation_images']) {
            $this->processVariationImageDataForNewFormat($wholeData, $data, $profileId);
            $wholeData['product']['variation_images'] = $data['product']['variation_images'];
        }

        /*Set Custom Options Values*/
        /*if ($subject->canSaveCustomOption()) {
            $wholeData = $subject->processCustomOptionData($wholeData, $data);
        }*/
        /*Set product Status */
        if (isset($data['product']['status'])) {
            if (is_numeric($data['product']['status'])) {
                $wholeData['status'] = $data['product']['status'];
            } elseif (strtolower($data['product']['status']) == 'enabled' || strtolower($data['product']['status']) == __('Enabled')) {
                $wholeData['status'] = 1;
            } elseif (strtolower($data['product']['status']) == 'disabled' || strtolower($data['product']['status']) == __('Disabled')) {
                $wholeData['status'] = 2;
            }
        }
        /*Set mapped category for Attribute Mapping */
        if ($subject->isAttributeMappingEnabled()) {
            if (isset($data['product']['attribute_mapping_category'])) {
                $category = $data['product']['attribute_mapping_category'];
                if (strpos($category, ',') !== false) {
                    $wholeData['error'] = 1;
                    $wholeData['msg'] = __(
                        'Skipped row %1. Multiple categories are not allowed for attribute mapping',
                        $mainRow
                    );
                } else {
                    $categoryId = $subject->getCategoryIds($category);
                    $wholeData['product']['attribute_mapping_category'] = $categoryId[0];
                }
            } else {
                $wholeData['product']['attribute_mapping_category'] =  '';
            }
        }
        /*Set Cross-sell Up-sell and related product data */
        if (isset($data['product']['related_skus']) && !empty($data['product']['related_skus'])) {
            $wholeData = $subject->setRelatedCrossUpSellProductData($wholeData, $data, 'related');
        }
        if (isset($data['product']['crosssell_skus']) && !empty($data['product']['crosssell_skus'])) {
            $wholeData = $subject->setRelatedCrossUpSellProductData($wholeData, $data, 'crosssell');
        }
        if (isset($data['product']['upsell_skus']) && !empty($data['product']['upsell_skus'])) {
            $wholeData = $subject->setRelatedCrossUpSellProductData($wholeData, $data, 'upsell');
        }
        if (isset($data['product']['brand']) && empty($wholeData['product']['brand'])) {
            $wholeData['product']['brand'] = $subject->getOptionIdByLabel('brand', '其它');
        }
        $wholeData = $this->utf8Converter($wholeData);
        return $wholeData;
    }

    /**
     * Check attribute data
     *
     * @param string $attribute
     * @param string $code
     * @param string $value
     * @param array $notAllowedAttr
     * @param array $wholeData
     * @param int $row
     * @return array
     */
    public function aroundCheckAttributeData(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $attribute, $code, $value, $notAllowedAttr, $wholeData, $row
    ){
        if ($subject->isAttributeAllowed($attribute) && !in_array($code, $notAllowedAttr)) {
            if ($code == "tier_price") {
                $value = $subject->processTierPrice($value);
                if (!empty($value)) {
                    foreach ($value as $key => $vl) {
                        if (empty($vl['website_id'])) {
                            $value[$key]['website_id'] = 0;
                        }
                        if (empty($vl['price_id'])) {
                            $value[$key]['price_id'] = '';
                        }
                        if (empty($vl['delete'])) {
                            $value[$key]['delete'] = 0;
                        }
                    }
                    $wholeData['product'][$code] = $value;
                }
            } else {
                $wholeData = $subject->isRequiredAttributeEmpty($attribute, $value, $wholeData, $row);
                if (!isset($wholeData['error'])) {
                    if ($attribute["frontend_input"] == "multiselect") {
                        $valueArray = array_map('trim', explode(",", $value));
                        $optionId = $subject->getOptionIdByLabel($code, $valueArray);
                    } elseif ($attribute["frontend_input"] == "select") {
                        $value = is_object($value) ? $value->getText() : $value;
                        $optionId = $subject->getOptionIdByLabelSelect($code, [$value]);
                    } else {
                        if ($attribute["frontend_input"] == "boolean" && (strcasecmp($value, __('yes')->render()) == 0)) {
                            $value = 1;
                        } elseif ($attribute["frontend_input"] == "boolean" && (strcasecmp($value, __('no')->render()) == 0)) {
                            $value = 0;
                        }
                        $optionId = $value;
                    }
                    $wholeData['product'][$code] = $optionId;
                    if ($attribute["frontend_input"] == "date" || $attribute["frontend_input"] == "datetime") {
                        if (empty($value)) {
                            unset($wholeData['product'][$code]);
                        } else {
                            if ($attribute["frontend_input"] == "date") {
                                $errorFlag = false;
                                if (!str_contains($value, "/")) {
                                    $errorFlag = true;
                                } else {
                                    $date = explode("/", $value);
                                    if ($date[0] > 12) {
                                        $errorFlag = true;
                                    }
                                }
                                if ($errorFlag) {
                                    $wholeData['error'] = 1;
                                    $wholeData['msg'] = __(
                                        "Skipped row %1. Invalid format provided. Please use 'm/d/Y' format.",
                                        $row
                                    );
                                }
                            } elseif ($attribute["frontend_input"] == "datetime") {
                                $errorFlag = false;
                                if (!str_contains($value, "/")) {
                                    $errorFlag = true;
                                } else {
                                    $date = explode("/", $value);
                                    if ($date[0] > 12) {
                                        $errorFlag = true;
                                    }
                                }
                                if ($errorFlag) {
                                    $wholeData['error'] = 1;
                                    $wholeData['msg'] = __(
                                        "Skipped row %1. Invalid format provided. Please use 'm/d/Y H:i' format.",
                                        $row
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        return $wholeData;
    }

    /**
     * Convert array to utf-8.
     *
     * @param array $data
     * @return array
     */
    public function utf8Converter($data = []): array
    {
        array_walk_recursive($data, function (&$item, $key) {
            if ($item!=null && !is_array($item) && !is_numeric($item)){
                if (is_string($item) && !mb_detect_encoding($item, 'utf-8', true)) {
                    $item = utf8_encode($item);
                }
            }
        });
        return $data;
    }

    /**
     * Validate required attributes for imported product data.
     *
     * @param array $productData
     * @param int $row
     * @param array|null $requiredAttributes
     * @param int|null $attributeSetId
     * @return array
     */
    public function validateRequiredImportAttributes(
        array $productData,
        int $row,
        ?array $requiredAttributes = null,
        ?int $attributeSetId = null
    ): array {
        $cacheKey = $attributeSetId . '_' . $this->productType;

        if ($requiredAttributes === null) {
            if (isset($this->requiredImportAttributeCache[$cacheKey])) {
                $requiredAttributes = $this->requiredImportAttributeCache[$cacheKey];
            } else {
                $requiredAttributes = [];

                $attributeSetInfo = null;
                if ($attributeSetId !== null) {
                    $attributeSetInfo = $this->marketplaceStagingHelper->getAllAttributesByAttributeSetId(
                        $attributeSetId,
                        $this->productType
                    );

                    $attributeSetInfo = !empty($attributeSetInfo)
                        ? $attributeSetInfo
                        : [];
                }

                $attributes = $this->attributeCollection->create();

                foreach ($attributes as $attribute) {
                    $attributeId = (int)$attribute->getId();
                    $attributeCode = $attribute->getAttributeCode();
                    if (
                        (!(int)$attribute->getIsRequired() && !in_array($attributeCode, $this->hardRequiredAttributes))
                        || !(int)$attribute->getIsVisible()
                    ) {
                        continue;
                    }

                    if (
                        $attributeSetId !== null &&
                        (!($attributeSetInfo) || !isset($attributeSetInfo[$attributeId]))
                    ) {
                        continue;
                    }

                    if (in_array($attributeCode, $this->excludedAttributes)) {
                        continue;
                    }

                    $requiredAttributes[$attributeCode] =
                        $attribute->getStoreLabel() ?: $attributeCode;
                }

                $this->requiredImportAttributeCache[$cacheKey] = $requiredAttributes;
            }
        }

        foreach ($requiredAttributes as $attributeCode => $label) {
            if (
                !array_key_exists($attributeCode, $productData)
                || $productData[$attributeCode] === ''
                || $productData[$attributeCode] === null
            ) {
                return [
                    'error' => 1,
                    'msg' => __('Skipped row %1. %2 cannot be empty.', $row, $label)
                ];
            }
        }

        return [
            'error' => 0
        ];
    }

    /**
     * Validate Product Data
     *
     * @param MpMassUploadHelper $subject
     * @param array $result
     * @param array $data
     * @param string $profileType
     * @param int $row
     *
     * @return array
     */
    public function afterValidateFields(
        MpMassUploadHelper $subject,
        array              $result,
        array              $data,
        string             $profileType,
        int                $row
    ): array {
        if (!$result['error']) {
            $attributeSetId = null;
            $profileId = (int)$this->request->getParam('id');

            if ($profileId) {
                $attributeSetId = (int)$subject->getAttributeSetId($profileId);
            }

            $requiredValidation = $this->validateRequiredImportAttributes(
                $data['product'],
                $row,
                null,
                $attributeSetId
            );

            if ($requiredValidation['error']) {
                $result['error'] = 1;
                $result['data'] = $data;
                $result['msg'] = $requiredValidation['msg'];
                return $result;
            }
            if (empty($data['product']['brand']) || strlen($data['product']['brand']) <= 0) {
                $result['error'] = 1;
                $result['data'] = $data;
                $result['msg'] = __('Skipped row %1. product brand can not be empty.', $row);
                return $result;
            }
            if ($this->productType == 'simple' && (empty($data['product']['weight']) || $data['product']['weight'] <= 0)) {
                try {
                    $product = $this->_productRepository->get($data['product']['sku']);
                    if ($product->getTypeId() != 'simple') {
                        $result['error'] = 1;
                        $result['data'] = $data;
                        $result['msg'] = __('Skipped row %1. product weight can not be empty.', $row);
                        return $result;
                    }
                } catch (NoSuchEntityException $e) {
                }
            }
            if (empty($data['product']['price'])) {
                $result['error'] = 1;
                $result['msg'] = __('Skipped row %1. Price can not be empty.', $row);
                return $result;
            }
            if (empty($data['product']['special_price'])) {
                $result['error'] = 1;
                $result['msg'] = __('Skipped row %1. Special price can not be empty.', $row);
                return $result;
            }
            if (!empty($data['product']['price'])) {
                $value = (string)$data['product']['price'];
                if (!preg_match('/^-?\d+$/', $value)) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Price cannot be a decimal value. Please provide a valid non-decimal number.', $row);
                    return $result;
                }
            }
            if (!empty($data['product']['special_price'])) {
                $value = (string)$data['product']['special_price'];
                if (!preg_match('/^-?\d+$/', $value)) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Special price cannot be a decimal value. Please provide a valid non-decimal number.', $row);
                    return $result;
                }
            }
            if (isset($data['product']['wk_marketplace_preorder']) && $data['product']['wk_marketplace_preorder']) {
                if ($data['product']['wk_marketplace_preorder'] == '啟用'
                || strtolower($data['product']['wk_marketplace_preorder']) == 'enable'){
                    if (empty($data['product']['preorder_mode'])) {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Preorder mode can not be empty.', $row);
                        return $result;
                    } else {
                        if ($data['product']['preorder_mode'] == '開始/結束日期' || strtolower($data['product']['preorder_mode']) == 'Start/ End date') {
                            if (empty($data['product']['preorder_start_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder start date can not be empty.', $row);
                                return $result;
                            } elseif (empty($data['product']['preorder_end_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder end date can not be empty.', $row);
                                return $result;
                            } elseif (empty($data['product']['wk_marketplace_availability'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder availability date can not be empty.', $row);
                                return $result;
                            } elseif (strtotime($data['product']['preorder_start_date']) >= strtotime($data['product']['preorder_end_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Pre-order Start date must be less than End date.', $row);
                                return $result;
                            } elseif (strtotime($data['product']['preorder_end_date']) >= strtotime($data['product']['wk_marketplace_availability'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Pre-order End date must be less than Available date.', $row);
                                return $result;
                            }
                            if (isset($data['product']['preorder_use_qty']) && $data['product']['preorder_use_qty']) {
                                if ($data['product']['preorder_use_qty'] == '是' || strtolower($data['product']['preorder_use_qty']) == 'yes') {
                                    if (empty($data['product']['wk_mppreorder_qty']) || $data['product']['wk_mppreorder_qty'] < 1) {
                                        $result['error'] = 1;
                                        $result['msg'] = __('Skipped row %1. Please input valid Maximum qty for Pre-order.', $row);
                                        return $result;
                                    }
                                }
                            }
                        } elseif ($data['product']['preorder_mode'] == '訂購後 X 天' || $data['product']['preorder_mode'] == '下單後X天出貨' || strtolower($data['product']['preorder_mode']) == 'x days after ordering') {
                            if (empty($data['product']['preorder_end_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder end date can not be empty.', $row);
                                return $result;
                            } elseif (empty($data['product']['preorder_x_days']) || $data['product']['preorder_x_days'] < 1) {
                                $result['error'] = 1;
                                $result['msg'] = __("Skipped row %1. Preorder XDays must be greater than 0 for XDays mode.", $row);
                                return $result;
                            } else {
                                $todayTimestamp = $this->dateTime->gmtTimestamp();
                                if (strtotime($data['product']['preorder_end_date']) < $todayTimestamp) {
                                    $result['error'] = 1;
                                    $result['msg'] = __("Skipped row %1. Preorder end date must be of future.", $row);
                                    return $result;
                                }
                            }
                        } elseif ($data['product']['preorder_mode'] == '指定出貨日期' || $data['product']['preorder_mode'] == '指定出貨日' || strtolower($data['product']['preorder_mode']) == 'specify shipping date') {
                            if (empty($data['product']['preorder_end_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder end date can not be empty.', $row);
                                return $result;
                            } elseif (empty($data['product']['preorder_ship_date'])) {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Preorder ship date can not be empty.', $row);
                                return $result;
                            } else {
                                $todayTimestamp = $this->dateTime->gmtTimestamp();
                                if (strtotime($data['product']['preorder_end_date']) < $todayTimestamp) {
                                    $result['error'] = 1;
                                    $result['msg'] = __("Skipped row %1. Preorder end date must be of future.", $row);
                                    return $result;
                                } elseif (strtotime($data['product']['preorder_ship_date']) < $todayTimestamp) {
                                    $result['error'] = 1;
                                    $result['msg'] = __("Skipped row %1. Preorder ship date must be of future.", $row);
                                    return $result;
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($data['product']['custom_option'])) {
                $arraySku = [];
                $options = array_map('trim', explode(',', $data['product']['custom_option']));
                foreach ($options as $option) {
                    if (empty($option)) {
                        continue;
                    }
                    $optionData = array_map('trim', explode('|', $option));
                    $optType = isset($optionData[4]) ? trim($optionData[4]) : '';
                    if (!$optType) {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Format Custom Options not correct.', $row);
                        return $result;
                    } elseif ($this->customOptionDropdownOnly() && $optType != "drop-down" && $optType != "drop_down") {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Custom Options not allow other type than dropdown.', $row);
                        return $result;
                    } elseif ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio" || $optType == "multiple" || $optType == "checkbox") {
                        if (count($optionData) < 11) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Format Custom Options not correct.', $row);
                            return $result;
                        }
                        if (isset($optionData[7])) {
                            if (!in_array($optionData[7], $arraySku)) {
                                $arraySku[] = $optionData[7];
                            } else {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Custom Options SKU must be unique.', $row);
                                return $result;
                            }
                        }
                        if (empty($optionData[7])) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Custom Options SKU can not be empty.', $row);
                            return $result;
                        }
                    } else {
                        if (count($optionData) < 8) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Format Custom Options not correct.', $row);
                            return $result;
                        }
                        if (isset($optionData[5])) {
                            if (!in_array($optionData[5], $arraySku)) {
                                $arraySku[] = $optionData[5];
                            } else {
                                $result['error'] = 1;
                                $result['msg'] = __('Skipped row %1. Variations SKU must be unique.', $row);
                                return $result;
                            }
                        }
                        if (empty($optionData[5])) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Custom Options SKU can not be empty.', $row);
                            return $result;
                        }
                    }
                }
            }
            if (!empty($data['product']['co_variation'])) {
                if (empty($data['product']['custom_option'])) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Variations need Custom Options data', $row);
                    return $result;
                }
                $options = array_map('trim', explode(',', $data['product']['custom_option']));
                $listOption = [];
                $countMatrixOption = 0;
                $countVariation = 0;
                foreach ($options as $option) {
                    if (empty($option)) {
                        continue;
                    }
                    $optionData = array_map('trim', explode('|', $option));
                    $optType = trim($optionData[4]);
                    if ($this->customOptionDropdownOnly()) {
                        if ($optType !== "drop-down" && $optType !== "drop_down") {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Variations not allow other type than dropdown.', $row);
                            return $result;
                        }
                    } else {
                        if ($optType !== "drop-down" && $optType !== "drop_down" && $optType !== "radio") {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Variations not allow other type than dropdown or radio.', $row);
                            return $result;
                        }
                    }
                    if (isset($listOption[$optionData[0]])) {
                        $listOption[$optionData[0]]++;
                    } else {
                        $listOption[$optionData[0]] = 1;
                    }
                }
                $arraySku = [];
                $options = array_map('trim', explode(',', $data['product']['co_variation']));
                foreach ($options as $option) {
                    $optionData = array_map('trim', explode('|', $option));
                    if (count($optionData) < 6) {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Format Variations not correct.', $row);
                        return $result;
                    }
                    if (isset($optionData[4])) {
                        if (!in_array($optionData[4], $arraySku)) {
                            $arraySku[] = $optionData[4];
                        } else {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Variations SKU must be unique.', $row);
                            return $result;
                        }
                    }
                    $countVariation++;
                }
                if (!empty($listOption)) {
                    $countMatrixOption = 1;
                    foreach ($listOption as $value) {
                        $countMatrixOption = $countMatrixOption * $value;
                    }
                }
                if ($countVariation % $countMatrixOption != 0) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Please confirm that the following required fields are filled in: cost, quantity, and product SKU.', $row);
                    return $result;
                }
            }

            if (isset($data['product']['point_money_config_type']) && $data['product']['point_money_config_type']) {
                if ($data['product']['point_money_config_type'] == '點金設定-上下限設定'
                    || $data['product']['point_money_config_type'] == '點金自由'
                    || $data['product']['point_money_config_type'] == __('Free ratio')->__toString()
                    || strtolower($data['product']['point_money_config_type']) == 'free ratio') {
                    if (isset($data['product']['product_discount_limit']) && $data['product']['product_discount_limit']) {
                        if (empty($data['product']['point_value'])) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. Product point can not be empty.', $row);

                        }
                    }
                }
            }
            if (!empty($data['product']['schedule_title'])) {
                $todayTimestamp = $this->dateTime->gmtTimestamp();
                if (empty($data['product']['schedule_start_time'])) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Product data schedule are missing, please update and import again', $row);
                } elseif (empty($result['data']['id'])) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Product data does not exist, please remove schedule data', $row);
                } elseif (!strtotime($data['product']['schedule_start_time'])) {
                    $result['error'] = 1;
                    $result['msg'] = __('Skipped row %1. Product data schedule start time does not correct format (m/d/Y H:i:s), please update and import again', $row);
                } elseif (strtotime($data['product']['schedule_start_time']) < $todayTimestamp) {
                    $result['error'] = 1;
                    $result['msg'] = __("Skipped row %1. The Future Update Start Time is invalid. It can't be earlier than the current time.", $row);
                }
                if (isset($data['product']['schedule_end_time']) && $data['product']['schedule_end_time']) {
                    if (!strtotime($data['product']['schedule_end_time'])) {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Product data schedule end time does not correct format (m/d/Y H:i:s), please update and import again', $row);
                    }
                    if (strtotime($data['product']['schedule_end_time']) <= $todayTimestamp) {
                        $result['error'] = 1;
                        $result['msg'] = __('Skipped row %1. Product data schedule end time does not correct format (m/d/Y H:i:s), please update and import again', $row);
                    }
                }
                try {
                    $params = [
                        'entityRequestName' => 'id',
                        'entityTable' => 'catalog_product_entity',
                        'entityColumn' => 'entity_id'
                    ];
                    $this->request->setParams(['id' => $result['data']['id']]);
                    $schedules = $this->searchResultFactory->create($params);
                    foreach ($schedules as $schedule) {
                        $startTime = $schedule->getStartTime();
                        if (strtotime($startTime) > $todayTimestamp) {
                            $result['error'] = 1;
                            $result['msg'] = __('Skipped row %1. The product data already has a schedule and does not support other schedule settings. Please update and import again.', $row);
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }
        return $result;
    }

    /**
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param $persistentData
     * @param $fields
     * @return array
     */
    public function aroundSetFieldsValue(
        MpMassUploadHelper $subject,
        callable           $proceed,
        &$persistentData, $fields
    )
    {
        foreach ($fields['product'] as $key => $field) {
            if ($key === 'stock'
                && isset($persistentData['product'][$key])
                && is_numeric($persistentData['product'][$key]
                )
            ) {
                continue;
            }
            if (in_array($key, $this->excludedAttributes )) {
                continue;
            }
            if (empty($persistentData['product'][$key])) {
                $persistentData['product'][$key] = $field;
            }
        }
        return $persistentData;
    }
    /**
     * Maps product data if already existing
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param array $data
     * @param int $productId
     * @return array
     */
    public function aroundExistingProductDataMapping(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $data,
        $productId
    ) {
        $product = $this->_productRepository->getById($productId);
        // Existing Product Data Mapping Start
        $productArray = $product->getData();
        foreach ($data['product'] as $key => $value) {
            if ($key === 'stock' && is_numeric($value)) {
                continue;
            }
            // Handle empty values
            if (empty($value)) {
                switch ($key) {
                    case 'stock':
                        $data['product']['stock'] = $productArray['quantity_and_stock_status']['qty'];
                        break;

                    case 'type':
                        $data['product']['type'] = $productArray['type_id'];
                        break;

                    default:
                        if (array_key_exists($key, $productArray)) {
                            $code = trim($key);
                            $attribute = $subject->getAttributeDataByCode($code);
                            if (!empty($attribute) && $attribute['is_required'] == 1) {
                                $data['product'][$key] = $productArray[$key];
                            }
                        }
                        break;
                }
            } else {
                // Additional logic for 'stock'
                if ($key === 'stock') {
                    $attributeTicketSets = $this->getTicketAttributeSet();
                    if (in_array($product->getAttributeSetId(), $attributeTicketSets)) {
                        $data['product']['stock'] = 0;
                    }
                }
            }
        }
        //Existing Product Image Data Mapping Start
        if (!empty($data['product']['images']) && !empty($productArray['image'])) {
            $imageArray = [];
            if (substr_count($data['product']['images'], ',') >= 0) {
                $str = $data['product']['images'];
                $len = strlen($str);
                $count = substr_count($str, ',');
                $j=0;
                $pos = strpos($str, ',', $j);
                if (substr_count($data['product']['images'], ',') == 0) {
                    $pos = $len;
                }
                for ($i=1; $i<=($count+1); $i++) {
                    $posOfComma = strpos($str, ',', $j);
                    $imgName = substr($str, $j, $pos);
                    // if image have "|" character => exam a|XX1.jpg, b|XX2.jpg => take XX1.jpg, XX2.jpg
                    if (strpos($imgName, '|') !== false) {
                        $imgName = explode('|', $imgName)[1];
                    }
                    // Validate whether image contains url or not
                    if ((filter_var($imgName, FILTER_VALIDATE_URL)) || (strrpos($imgName, '/'))) {
                        $urlLength = strlen($imgName);
                        $posOfSlash = strrpos($imgName, '/');
                        $newImgName = substr($imgName, $posOfSlash+1, $urlLength);
                        $imageArray[$i] = $newImgName;
                    } else {
                        $imageArray[$i] = $imgName;
                    }
                    $j=$posOfComma+1;
                }
            }
            foreach ($productArray['media_gallery']['images'] as $imagesData) {
                $str = $imagesData['file'];
                $len = strlen($str);
                $pos = strrpos($str, '/');
                $imageName = substr($str, $pos+1, $len);
                $imageArrayCount = count($imageArray);
                for ($i=1; $i<=$imageArrayCount; $i++) {
                    if ($imageArray[$i] == $imageName) {
                        $imageArray[$i] = "";
                    }
                }
            }
            $result = array_filter($imageArray);
            if (\strpos($data['product']['images'], 'http') !== false) {
                $data['product']['images_url'] = $data['product']['images'];
            } else {
                $data['product']['images_url'] = '';
            }
            $data['product']['images'] = implode(',', array_values($result));
        }
        $data['id'] = $productId;
        $data['row_id'] = $product->getRowId();
        $data['back'] = 'updateExisting';
        $data['product_id'] = $productId;
        $data['product']['website_ids'][] = $product->getStore()->getWebsiteId();
        $data['product']['url_key'] = $product->getUrlKey();

        return $data;
    }

    /**
     * Save Product
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param int $sellerId
     * @param int $row
     * @param array $wholeData
     *
     * @return array
     */
    public function aroundSaveProduct(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $sellerId,
        $row,
        $wholeData
    ): array {
        if(!isset($wholeData['product']) && isset($wholeData['error']) && $wholeData['error']){
            $result['error'] = $wholeData['error'];
            $result['msg'] = $wholeData['msg'];
            $result['total_row_count'] = $wholeData['total_row_count'];
            $result['row'] = $row;
            if ($wholeData['total_row_count'] != $row) {
                $nextRow = $row+1;
                $result['next_row_data'] = $subject->calculateProductRowData(
                    $sellerId,
                    $wholeData['profile_id'],
                    $nextRow,
                    $wholeData['type']
                );
                $result['next_row_data']['profile_id'] = $wholeData['profile_id'];
                $result['next_row_data']['row'] = $nextRow;
                $result['next_row_data']['total_row_count'] = $wholeData['total_row_count'];
                $result['next_row_data']['seller_id'] = $sellerId;
                if (!empty($wholeData['store_to_upload'])) {
                    $result['next_row_data']['store_to_upload'] = $wholeData['store_to_upload'];
                }
                if (!empty($wholeData['is_multiple'])) {
                    $result['next_row_data']['is_multiple'] = $wholeData['is_multiple'];
                }
            }
            return $result;
        }
        $product = $wholeData['product'];

        if(!isset($product['cost_setting'])){
            $product['cost_setting'] = \Branch8\Catalog\Model\Source\CostSetting::FIXED;
        }
        if($product['cost_setting'] == \Branch8\Catalog\Model\Source\CostSetting::FIXED){
            $sellerId = isset($wholeData['product']['seller_id'])?$wholeData['product']['seller_id']:$sellerId;
            $sellerCommissionData = $this->marketplaceStagingHelper->getCommisionRates($sellerId);

            if($product['commission_percent'] == $sellerCommissionData['commission_rate'] && $sellerCommissionData['active_contract']){
                $product['commission_source'] = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::ACTIVE_PERIOD;
            }else if($product['commission_percent'] == $sellerCommissionData['default_commission_rate']){
                $product['commission_source'] = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::DEFAULT_SETTING;
            }else{
                $product['commission_source'] = \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource::MANULLY_INPUT;
            }
        }
        $wholeData['product'] = $product;

        $profile = $this->_profileRepository->get($wholeData['profile_id']);
        $profileAttributeId =  $profile['attribute_set_id'];
        $attributeSetCollection = $this->_attributeSetCollection
            ->create()
            ->addFieldToFilter(
                'attribute_set_id',
                ['in' => $profileAttributeId]
            );
        $result = ['error' => 0, 'config_error' => 0, 'msg' => ''];
        if ($attributeSetCollection->getSize()) {
            $outputImportArr = [];
            try {
                /** Validate price - special price */
                $specialPrice = (float)$wholeData['product']['special_price'];
                $productPrice = (float)$wholeData['product']['price'];
                if($productPrice < $specialPrice){
                    throw new LocalizedException(__('SKU: "%1" -  Special price is greater than price.', $wholeData['product']["sku"]));
                }
                /* Check if authorized seller */
                if (!empty($wholeData['is_multiple'])) {
                    $isMultiple = true;
                    $outputImportArr = $subject->validateForMultiImport($wholeData, $row, $sellerId);
                    $wholeData = $outputImportArr['wholeData'];
                    $sellerId = isset($wholeData['product']['seller_id'])?$wholeData['product']['seller_id']:$sellerId;
                }
                if (!empty($wholeData['id']) && empty($wholeData['error'])) {
                    $productId = $wholeData['id'];
                    if (empty($sellerId)) {
                        $wholeData['msg'] = __(
                            'Skipped row %1. No seller has been assigned to this product.
                        Please update csv with seller id value',
                            $row
                        );
                        $wholeData['error'] = 1;
                    }
                    $rightSeller = $subject->isRightSeller($productId, $sellerId);
                    if (!$rightSeller) {
                        $wholeData['msg'] = __(
                            'Skipped row %1. Product is already assigned to other seller.',
                            $row
                        );
                        $wholeData['error'] = 1;
                    }
                }
                if ($row == 1) {
                    $this->_customerSession->setSuccesProductCount(0);
                }
                $uploadedPro = $wholeData['total_row_count'];
                $successProCount = (int) $this->_customerSession->getSuccesProductCount();
                $area = $this->state->getAreaCode();
                /*Set Product Add Status According to seller Group*/
                if ($subject->isSellerGroupEnable() && !$subject->checkProductAllowedStatus($uploadedPro, $successProCount)) {
                    $result['error'] = 1;
                    if ($subject->getAllowedProductQty()) {
                        $result['message'] =
                            __('You are not allowed to add more than %1 Product(s)', $subject->getAllowedProductQty());
                    } else {
                        $result['message'] = __('YOUR GROUP PACK IS EXPIRED...');
                    }
                } elseif ($subject->isSellerMembershipEnable() && $area == 'frontend' &&
                    ($subject->getConfigFeeAppliedFor() == 0 && !$subject->isMembershipFeePaid())) {
                    $errorFlag = 1;
                    $data = $subject->isMembershipFeePaid($errorFlag);
                    if ($data['status']) {
                        $result['error'] = 1;
                        $result['message'] = __('Seller Membership : %1 ', $data['msg']);
                    }
                } else {
                    if (!empty($wholeData['error'])) {
                        $result['error'] = $wholeData['error'];
                        $result['msg'] = $wholeData['msg'];
                    } else {
                        $prodQty = 0;
                        $bundleGroupedQty = 0;
                        if ($wholeData['type'] == 'configurable') {
                            $prodQty = $wholeData['product']['quantity_and_stock_status']['qty'];
                            unset($wholeData['product']['quantity_and_stock_status']['qty']);
                        }
                        if (isset($wholeData['product']['type_id']) &&
                            ($wholeData['product']['type_id'] == 'bundle'
                            || $wholeData['product']['type_id'] == 'grouped')) {
                            $bundleGroupedQty = $wholeData['product']['quantity_and_stock_status']['qty'];
                            unset($wholeData['product']['quantity_and_stock_status']['qty']);
                        }
                        if ($this->request->getParam('seller_id')) {
                            $sellerId = (int)$this->request->getParam('seller_id');
                        } else {
                            $sellerId = (int)$this->_customerSession->getCustomerId();
                        }
                        $skipEditApproval = false;
                        if (!empty($wholeData['id']) && empty($wholeData['staging']['name']) && !$this->marketplaceHelperData->getIsProductEditApproval()) {
                            $skipEditApproval = true;
                        }
                        // Handle Attribute Selector
                        /*$attributeSelector = $this->b8MpConfig->getAttributeSelector();
                        if (!empty($attributeSelector)) {
                            foreach ($wholeData['product'] as $key => $value) {
                                if (in_array($key, $attributeSelector)) {
                                    $wholeData['attribute_selected'][] = $key;
                                }
                            }
                            if (!empty($wholeData['attribute_selected'])) {
                                $this->request->setPostValue('attribute_selected', $wholeData['attribute_selected']);
                            }
                        }*/

                        list($customOptions, $finalSwatches, $correspondedAlphabet, $variation_comb, $variationFlag, $old_format) = $this->buildCustomOptionData($wholeData);
                        if($old_format){
                            $wholeData['product']['wk_manage_swatch'] = $finalSwatches;
                            $wholeData['product']['wk_manage_variation'] = $variation_comb;
                        } else {
                            $variation = $this->buildVariationData($wholeData, $correspondedAlphabet, $variation_comb, $variationFlag);
                            if (!empty($variation) && !empty($finalSwatches)) {
                                $wholeData['product']['wk_manage_swatch'] = $finalSwatches;
                                $wholeData['product']['wk_manage_variation'] = $variation;
                            }
                        }

                        if(isset($wholeData['type']) && in_array($wholeData['type'], ['virtual', 'simple']) && $variationFlag) {
                            $wholeData['product']['stock_data']['manage_stock'] = 0;
                            $wholeData['product']['stock_data']['use_config_manage_stock'] = 0;
                            $wholeData['product']['quantity_and_stock_status']['is_in_stock'] = 1;
                            $wholeData['product']['options'] = $customOptions;
                            unset($wholeData['product']['custom_option']);
                        } elseif (!empty($customOptions)) {
                            $wholeData['affect_product_custom_options'] = 1;
                            $wholeData['product']['options'] = $customOptions;
                            unset($wholeData['product']['custom_option']);
                        }
                        if (empty($wholeData['id']) && !empty($wholeData['status']) && $subject->getProductApprovalRequiredStatus()) {
                            unset($wholeData['status']);
                        }

                        if (empty($wholeData['id']) || $skipEditApproval) {
                            // Handle the case of adding a new product
                            $result = $this->_saveProduct->saveProductData($sellerId, $wholeData);
                            $this->storeChangedData->execute($wholeData, (int) $result['product_id'], $sellerId, CreatedFrom::CREATED_FROM_IMPORTED, true);
                            if (isset($wholeData['product']['type_id']) && $wholeData['product']['type_id'] == 'bundle') {
                                $product = $this->_productRepository->getById($result['product_id']);
                                $this->saveBundleProduct($product, $wholeData);
                            }
                            $isInStock = 1;
                            $productId = (int) $result['product_id'];
                            if ($area != 'frontend' && isset($wholeData['store_to_upload'])) {
                                $this->_mpProduct->create()->getCollection()
                                    ->addFieldToFilter('mageproduct_id', $productId)->getFirstItem()
                                    ->setData('store_id', $wholeData['store_to_upload'])->save();
                            } else {
                                $this->_mpProduct->create()->getCollection()
                                    ->addFieldToFilter('mageproduct_id', $productId)->getFirstItem()
                                    ->setData('store_id', 0)->save();
                            }
                            if ($wholeData['type'] == 'configurable') {
                                $wholeData['product']['quantity_and_stock_status']['qty'] = $prodQty;
                            }
                            if (isset($wholeData['product']['type_id']) &&
                                ($wholeData['product']['type_id'] == 'bundle'
                                    || $wholeData['product']['type_id'] == 'grouped')) {
                                $wholeData['product']['quantity_and_stock_status']['qty'] = $bundleGroupedQty;
                            }
                            if (!(int)$wholeData['product']['quantity_and_stock_status']['qty']) {
                                $isInStock = 0;
                            }
                            $result['is_in_stock'] = $isInStock;
                            if ($productId) {
                                $successProCount = (int) $this->_customerSession->getSuccesProductCount();
                                $successProCount++;
                                $this->_customerSession->setSuccesProductCount($successProCount);
                                $this->saveInventoryLogForNewProduct($productId);
                            }
                        } else {
                            $productId = (int) $wholeData['id'];
                            try {
                                $product = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
                                if ($product->getData('status') != SellerProduct::STATUS_PENDING) {
                                    $logEntry = $this->getProductLogEntryByProductId->execute($productId);
                                    if (!empty($logEntry['id'])) {
                                        $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                                        $productVersion->setStatus(SellerProduct::STATUS_DISABLED);
                                        $this->productVersionRepository->save($productVersion);
                                    }
                                }
                            } catch (NoSuchEntityException $e) {
                            }
                            // Handle the case of updating a product
                            // Handle schedule case
                            if (isset($wholeData['staging']['name']) && $wholeData['staging']['name']) {
                                $wholeData['created_from'] = CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED;
                                if (!empty($wholeData['product']) && empty($wholeData['product']['current_store_id'])) {
                                    $wholeData['product']['current_store_id'] = 0;
                                }
                                if (isset($wholeData['product']['quantity_and_stock_status']['qty'])) {
                                    $wholeData['product']['stock_data']['qty'] = $wholeData['product']['quantity_and_stock_status']['qty'];
                                    $wholeData['product']['stock_data']['is_in_stock'] = $wholeData['product']['quantity_and_stock_status']['is_in_stock'];
                                }
                                try {
                                    $returnArr['error'] = false;
                                    $this->stagingUpdateSave->execute(
                                        [
                                            'entityId' => $productId,
                                            'stagingData' => $wholeData['staging'],
                                            'entityData' => $wholeData

                                        ]
                                    );
                                } catch (LocalizedException $e) {
                                    $returnArr['message'] = $e->getMessage();
                                    $returnArr['error'] = 1;
                                } catch (\Exception $e) {
                                    $returnArr['message'] = $e->getMessage();
                                    $returnArr['error'] = 1;
                                }
                            } else {
                                $productChangeLog = $this->productChangeLogFactory->create();
                                $productChangeLog->setProductId($productId)
                                    ->setAction('import');

                                $resolvedSellerId = (int)$sellerId;
                                if ($this->subAccountHelper->isSubAccount()) {
                                    $subAccountOwnerId = (int)$this->subAccountHelper->getCustomerId();
                                    if ($subAccountOwnerId > 0) {
                                        $resolvedSellerId = $subAccountOwnerId;
                                    }
                                }
                                $productChangeLog->setUserType(UserType::TYPE_SELLER)
                                    ->setUserId($resolvedSellerId);

                                $productData = $wholeData['product'];
                                $productData['status'] = $wholeData['status'] ?? Status::STATUS_DISABLED;
                                $productData['seller_id'] = $sellerId;
                                $productData['type_id'] = $wholeData['type'];
                                $productPostData = (string)$this->json->serialize($productData);
                                $productChangeLog->setPostData($productPostData);

                                $productObj = $this->_product->create()->load($productId);
                                if ($productObj->getId()) {
                                    $productBefore = $this->adjustProductData($productObj);
                                    $productChangeLog->setBeforeValues($productBefore);
                                }
                                if ($product->getNewNeedApprove()) {
                                    $returnArr = $this->storeChangedData->execute($wholeData, (int)$productId, $sellerId, CreatedFrom::CREATED_FROM_IMPORTED, true);
                                } else {
                                    $returnArr = $this->storeChangedData->execute($wholeData, (int)$productId, $sellerId, CreatedFrom::CREATED_FROM_IMPORTED);
                                }
                                if (empty($returnArr['error'])) {
                                    if ($productObj->getId()) {
                                        $productObj = $productObj->load($productId);
                                        $productAfter = $this->adjustProductData($productObj);
                                        $productChangeLog->setProductId($productId)
                                            ->setAfterValues($productAfter);
                                    }
                                    try {
                                        $this->productChangeLogRepository->save($productChangeLog);
                                    } catch (CouldNotSaveException $e) {
                                    }
                                }
                            }
                            if ($returnArr['error']) {
                                $result['msg'] = __('Skipped row %1. %2', $row, $returnArr['message']);
                                $result['error'] = 1;
                            } else {
                                $successProCount = (int) $this->_customerSession->getSuccesProductCount();
                                $successProCount++;
                                $this->_customerSession->setSuccesProductCount($successProCount);
                                $result['msg'] = 'Your product has been import successfully. An administrator will review and approve it if necessary.';
                                $result['product_id'] = $productId;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['msg'] = __('Skipped row %1. %2', $row, $e->getMessage());
                $result['error'] = 1;
            }
            $result['total_row_count'] = $wholeData['total_row_count'];
            $result['row'] = $row;
            if ($wholeData['total_row_count'] != $row) {
                $nextRow = $row+1;
                $result['next_row_data'] = $subject->calculateProductRowData(
                    $sellerId,
                    $wholeData['profile_id'],
                    $nextRow,
                    $wholeData['type']
                );
                $result['next_row_data']['profile_id'] = $wholeData['profile_id'];
                $result['next_row_data']['row'] = $nextRow;
                $result['next_row_data']['total_row_count'] = $wholeData['total_row_count'];
                $result['next_row_data']['seller_id'] = $sellerId;
                if (!empty($wholeData['store_to_upload'])) {
                    $result['next_row_data']['store_to_upload'] = $wholeData['store_to_upload'];
                }
                if (!empty($wholeData['is_multiple'])) {
                    $result['next_row_data']['is_multiple'] = $isMultiple;
                }
            }
            if ($result['error'] == 1) {
                if (!empty($result['message'])) {
                    $result['msg'] = $result['message'];
                }
                return $result;
            } else {
                if (empty($result['product_id'])) {
                    $result['product_id'] = 0;
                }
                $productId = (int) $result['product_id'];
            }
            if ($productId == 0) {
                $result['error'] = 1;
                $result['msg'] = __('Skipped row %1. error in importing product.', $row);
            }
        } else {

            $result['error'] = 1;
            $result['msg'] = __('Skipped row %1. Error in importing
        product selected attribute set does not exist.', $row);
        }
        return $result;
    }

    /**
     * Adjust product data to string.
     *
     * @param Product $product
     *
     * @return string
     */
    private function adjustProductData(Product $product): string
    {
        $productData = $product->getData();
        unset($productData['extension_attributes']);

        if (isset($productData['options'])) {
            unset($productData['options']);
            $optionsData = [];

            foreach ($product->getOptions() as $option) {
                $optionData = [
                    'is_delete' => '',
                    'previous_type' => $option->getType(),
                    'previous_group' => $option->getGroupByType(),
                    'record_id' => $option->getOptionId(),
                    'option_id' => $option->getOptionId(),
                    'sort_order' => $option->getSortOrder(),
                    'title' => $option->getTitle(),
                    'type' => $option->getType(),
                    'is_require' => $option->getIsRequire(),
                    'values' => [],
                ];

                $values = $option->getValues();
                if ($values) {
                    foreach ($values as $value) {
                        $optionData['values'][$value->getOptionTypeId()] = [
                            'sort_order' => $value->getSortOrder(),
                            'option_type_id' => $value->getOptionTypeId(),
                            'title' => $value->getTitle(),
                            'price' => $value->getPrice(),
                            'price_type' => $value->getPriceType(),
                            'sku' => $value->getSku(),
                            'is_bought' => $value->getData('is_bought') ?? '0',
                            'is_visible' => $value->getData('is_visible') ?? '1',
                        ];
                    }
                }

                $optionsData[] = $optionData;
            }

            $productData['options'] = $optionsData;
        }

        return (string)$this->json->serialize($productData);
    }

    /**
     * SaveBundleProduct
     *
     * @param ProductInterface $product
     * @param array $productData
     * @return null
     */
    public function saveBundleProduct(ProductInterface $product, array $productData)
    {
        $bundleOptions = [];
        $compositeReadonly = $product->getCompositeReadonly();
        if (array_key_exists('bundle_options', $productData) && array_key_exists('bundle_selections', $productData)) {
            $productData['bundle_options'] = $this->removeEmptyOptions(
                $productData['bundle_options'],
                $productData['bundle_selections']
            );
            $bundleOptions['bundle_options'] = $productData['bundle_options'];
            $bundleSelections = $productData['bundle_selections'];
        } else {
            return;
        }
        $collection = $this->_optionMod->getCollection()
            ->addFieldTofilter('parent_id', ['eq'=>$product->getId()]);
        if ($collection->getSize()) {
            $collection->walk('delete');
        }
        $i = 0;
        $l = 0;
        foreach ($bundleOptions['bundle_options'] as $key => $valueUp) {
            $bundleOptions['bundle_options'][$key]['bundle_selections'] = $bundleSelections[$key];
            $bundleOptions['bundle_options'][$key]['record_id'] = $l;
            foreach ($bundleSelections[$key] as $valueDown) {
                $bundleOptions['bundle_options'][$key]['bundle_button_proxy'][$i]['entity_id']
                    = $valueDown['product_id'];
                $i++;
            }
            $i = 0;
            $l++;
        }
        if (isset($bundleOptions['bundle_options'])) {
            $result = [];
            foreach ($bundleOptions['bundle_options'] as $key => $option) {
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
            $this->processBundleOptionsData($product, $productData);
        } else {
            return;
        }
    }

    /**
     * RemoveEmptyOptions
     *
     * @param array $bundleOptions
     * @param array $bundleSelections
     * @return array
     */
    public function removeEmptyOptions($bundleOptions, $bundleSelections)
    {
        foreach ($bundleOptions as $key => $bOption) {
            if (!array_key_exists($key, $bundleSelections)) {
                unset($bundleOptions[$key]);
            }
        }
        return $bundleOptions;
    }

    /**
     * ProcessBundleOptionsData
     *
     * @param ProductInterface $product
     * @param array $productData
     * @return void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processBundleOptionsData(ProductInterface $product, array $productData)
    {

        $bundleOptionsData = $product->getBundleOptionsData();
        $compositeReadonly = $product->getCompositeReadonly();
        if (!$bundleOptionsData) {
            return;
        }
        $options = [];
        $count = 0;
        $temp = [];
        try {
            foreach ($bundleOptionsData as $key => $dob) {
                if ($dob['delete'] == 1) {
                    unset($bundleOptionsData[$key]);
                } else {
                    $dob['default_title'] = $dob['title'];
                    $temp[] = $dob;
                }
            }
            $bundleOptionsData = $temp;

            foreach ($bundleOptionsData as $key => $optionData) {
                if ((bool)$optionData['delete']) {
                    continue;
                }

                $option = $this->_optionFactory->create(['data' => $optionData]);
                $option->setSku($product->getSku());
                $option->setOptionId(null);

                $links = [];
                $bundleLinks = $product->getBundleSelectionsData();
                if (empty($bundleLinks[$key])) {
                    continue;
                }
                foreach ($bundleLinks[$key] as $linkData) {
                    if ((bool)$linkData['delete']) {
                        continue;
                    }
                    $links = $this->setQtySku(
                        $linkData,
                        $product,
                        $links
                    );
                }
                $option->setProductLinks($links);
                $options[] = $option;
            }
            $extension = $product->getExtensionAttributes();
            $extension->setBundleProductOptions($options);
            $product->setExtensionAttributes($extension);
            $affectProductSelections = (bool)$productData['affect_bundle_product_selections'] ?? false;
            $product->setCanSaveBundleSelections($affectProductSelections && !$compositeReadonly);
            $product->setHasOptions(1);
            $product->setRequiredOptions();
            $product->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * SetQtySKU
     *
     * @param array $linkData
     * @param \Magento\Catalog\Model\Product $product
     * @param array $links
     * @return array
     */
    public function setQtySku(
        $linkData,
        $product,
        $links
    ) {

        $link = $this->_linkFactory->create(['data' => $linkData]);

        if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            if (array_key_exists('selection_price_value', $linkData)) {
                $link->setPrice($linkData['selection_price_value']);
            }
            if (array_key_exists('selection_price_type', $linkData)) {
                $link->setPriceType($linkData['selection_price_type']);
            }
        }
        $linkProduct = $this->_productRepository->getById($linkData['product_id']);
        $link->setSku($linkProduct->getSku());
        $link->setQty($linkData['selection_qty']);

        if (array_key_exists('selection_can_change_qty', $linkData)) {
            $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
        }
        $links[] = $link;
        return $links;
    }

    /**
     * Get Sample Csv File Urls.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterGetSampleCsv(
        MpMassUploadHelper $subject,
        array              $result
    ) {
        $mediaDirectory = $this->_storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $url = $mediaDirectory.'marketplace/massupload/samples/';
        $result[] = $url.'bundle.csv';
        $result[] = $url.'grouped.csv';
        return $result;
    }

    /**
     * Get Sample XML File Urls.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterGetSampleXml(
        MpMassUploadHelper $subject,
        array              $result
    ) {
        $mediaDirectory = $this->_storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $url = $mediaDirectory.'marketplace/massupload/samples/';
        $result[] = $url.'bundle.xml';
        $result[] = $url.'grouped.xml';
        return $result;
    }

    /**
     * Get Sample XLS File Urls.
     *
     * @return array
     */
    public function afterGetSampleXls(
        MpMassUploadHelper $subject,
        array              $result
    ) {
        $mediaDirectory = $this->_storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $url = $mediaDirectory.'marketplace/massupload/samples/';
        $result[0] = $url.'simple_default.xls';
        $result[] = $url.'bundle.xls';
        $result[] = $url.'grouped.xls';
        $result[6] = $url.'ticket.xls';
        $result[7] = $url.'ticket_yoxi.xls';
        $result[8] = $url.'ticket_edenred.xls';
        $result[9] = $url.'ticket_fami.xls';
        $result[10] = $url.'ticket_redeem.xls';
        $result[11] = $url.'ticket_non_redeem.xls';
        return $result;
    }

    /**
     * Get Category Ids From Name
     *
     * @param string $categories
     *
     * @return array
     */
    public function aroundGetCategoryIds(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $categories
    ){
        $categoryIds = [];
        $categoryList = $subject->getCategotyList();
        if (strpos($categories, ',') !== false) {
            $categories = array_map('trim', explode(',', $categories));
        } else {
            $categories = [$categories];
        }
        $categories = array_unique($categories);
        foreach ($categories as $category) {
            $parentId = 2;
            if (strpos($category, '>>') !== false) {
                $category = array_map('trim', explode('>>', $category));
                foreach ($category as $ch) {
                    if ($ch != "Default Category") {
                        $parentId = $subject->getChildId($ch, $parentId);
                    }
                }
                foreach ($categoryList as $key => $cat) {
                    if ($key == $parentId) {
                        $categoryIds[] = $key;
                    }
                }
            } else {
                $category = trim($category);
                if (in_array($category, $categoryList)) {
                    foreach ($categoryList as $key => $cat) {
                        if ($cat == $category) {
                            $categoryIds[] = $key;
                        }
                    }
                }
            }
        }

        return $categoryIds;
    }

    /**
     * Validate Uploaded Files
     *
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param string $noValidate
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    /**
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @return array
     */
    /**
     * @param MpMassUploadHelper $subject
     * @param callable $proceed
     * @param array $result
     * @param string $extension
     * @param string $csvFile
     * @return array
     */
    public function aroundUploadCsv(
        MpMassUploadHelper $subject,
        callable $proceed,
        $result,
        $extension,
        $csvFile
    ) {
        if ($extension == 'xlsx') {
            $profileId = $result['id'];
            try {
                $csvUploadPath = $subject->getBasePath($profileId);
                $csvUploader = $this->fileUploader->create(['fileId' => 'massupload_csv']);
                $csvUploader->setAllowedExtensions(['csv', 'xml', 'xls', 'xlsx']);
                $csvUploader->setAllowRenameFiles(true);
                $csvUploader->setFilesDispersion(false);
                $csvUploader->save($csvUploadPath, $result['name']);
                return ['error' => false];
            } catch (\Exception $e) {
                $msg = 'There is some problem in uploading csv file.' . $e->getMessage();
                return ['error' => true, 'msg' => $msg];
            }
        }
        return $proceed($result, $extension, $csvFile);
    }

    public function aroundValidateCsv(
        MpMassUploadHelper $subject,
        callable $proceed
    ) {
        try {
            $csvUploader = $this->fileUploader->create(['fileId' => 'massupload_csv']);
            $csvUploader->setAllowedExtensions(['csv', 'xml', 'xls', 'xlsx']);
            $validateData = $csvUploader->validateFile();
            $extension = $csvUploader->getFileExtension();
            $csvFilePath = $validateData['tmp_name'];
            $csvFile = $validateData['name'];
            $csvFile = $subject->getValidName($csvFile);
            $result = [
                'error' => false,
                'path' => $csvFilePath,
                'csv' => $csvFile,
                'extension' => $extension
            ];
        } catch (\Exception $e) {
            $msg = 'There is some problem in uploading file.';
            $result = ['error' => true, 'msg' => $msg];
        }
        return $result;
    }

    public function aroundValidateUploadedFiles(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $noValidate
    ){
        $validateCsv = $subject->validateCsv();
        if ($validateCsv['error']) {
            return $validateCsv;
        }
        $csvFile = $validateCsv['csv'];
        if (empty($noValidate)) {
            $validateZip = $subject->validateZip();
            if ($validateZip['error']) {
                return $validateZip;
            }
        }
        $attributeMappedArr = [];
        $attributeMappedConfig = $this->getAttributeMapping();
        $optionAttrTitle = $optionAttrData = '';
        foreach ($attributeMappedConfig as $attribute) {
            $attributeMappedArr[trim($attribute['title'])] = $attribute['attribute'];
            if ($attribute['attribute'] == 'option_title') {
                $optionAttrTitle = trim($attribute['title']);
            }
            if ($attribute['attribute'] == 'option_detail') {
                $optionAttrData = trim($attribute['title']);
            }
        }
        $rangeList = $this->salable->getRangeList();
        foreach ($rangeList as $letter) {
            if (!empty($optionAttrTitle) && !empty($optionAttrData)) {
                if(str_contains($optionAttrTitle, '{x}')) {
                    $attributeMappedArr[str_replace('{x}', $letter, $optionAttrTitle)] = 'option_title_'.$letter;
                }
                if(str_contains($optionAttrData, '{x}')) {
                    $attributeMappedArr[str_replace('{x}', $letter, $optionAttrData)] = 'option_detail_'.$letter;
                }
            }
        }

        // Start: Calculate Profile Mapped Attribute Data Array
        // for coverting uploaded file data attributes into magento attributes
        $atrrProfileId = $this->request->getParam('attribute_profile_id');
        $attributeMappedData = $this->attributeMappingRepository
            ->getByProfileId($atrrProfileId);
        foreach ($attributeMappedData as $key => $value) {
            if ($value['mage_attribute'] == 'image') {
                $attributeMappedArr[$value['file_attribute']] = 'images';
            } elseif ($value['mage_attribute'] == 'category_ids') {
                $attributeMappedArr[$value['file_attribute']] = 'category';
            } else {
                $attributeMappedArr[$value['file_attribute']] = $value['mage_attribute'];
            }
        }
        // End: Calculate Profile Mapped Attribute Data Array
        $csvFilePath = $validateCsv['path'];
        if ($validateCsv['extension'] == 'csv') {
            $uploadedFileRowData = $subject->readCsv($csvFilePath, $attributeMappedArr);
        } elseif ($validateCsv['extension'] == 'xml') {
            $uploadedFileRowData = $this->parser->load($csvFilePath)->xmlToArray();
            $dataKeyProductArray = [];
            $dataValueArray = [];
            $count = count($uploadedFileRowData);
            if (!$count || !isset($uploadedFileRowData['node']['product'])) {
                return ['error' => true, 'msg' => __('Invalid Format.')];
            }
            $flag = 1;
            foreach ($uploadedFileRowData['node']['product'] as $key => $value) {
                if (is_array($value) && is_numeric($key)) {
                    $flag = 0;
                    $dataValueProductArray = [];
                    foreach ($value as $productkey => $productValue) {
                        // Start: Coverting uploaded file data attributes into magento attributes
                        if (!empty($attributeMappedArr[$productkey])) {
                            $productkey = $attributeMappedArr[$productkey];
                        }
                        // End: Coverting uploaded file data attributes into magento attributes
                        $dataKeyProductArray[$productkey] = $productkey;
                        $dataValueProductArray[$productkey] = $productValue;
                    }
                    $dataValueArray[] = $dataValueProductArray;
                } else {
                    $dataKeyProductArray[$key] = $key;
                    $dataValueArray[] = $value;
                }
            }
            $i = 0;
            $dataKeyArray = [];
            foreach ($dataKeyProductArray as $key => $value) {
                $dataKeyArray[$i] = $value;
                if (!$flag) {
                    foreach ($dataValueArray as $productkey => $productvalue) {
                        if (empty($dataValueArray[$productkey][$value])) {
                            $dataValueArray[$productkey][$i] = '';
                            unset($dataValueArray[$productkey][$value]);
                        } else {
                            $dataValueArray[$productkey][$i] = $dataValueArray[$productkey][$value];
                            unset($dataValueArray[$productkey][$value]);
                        }
                    }
                }
                $i++;
            }
            $data[0] = $dataKeyArray;
            if (!$flag) {
                $i = 1;
                foreach ($dataValueArray as $key => $value) {
                    $data[$i] = $value;
                    $i++;
                }
            } else {
                $data[1] = $dataValueArray;
            }
            $uploadedFileRowData = $data;
        } elseif ($validateCsv['extension'] == 'xlsx') {
            try {
                $objPhpSpreadsheetReader = IOFactory::load($csvFilePath);
                $uploadedFileRowData = $objPhpSpreadsheetReader->getActiveSheet()->toArray();

                if (empty($uploadedFileRowData)) {
                    return ['error' => true, 'msg' => __('The file is empty or invalid.')];
                }

                // Start: Coverting uploaded file data attributes into magento attributes
                if (!empty($uploadedFileRowData[0])) {
                    foreach ($uploadedFileRowData[0] as $key => $productkey) {
                        if (!empty($attributeMappedArr[$productkey])) {
                            $productkey = $attributeMappedArr[$productkey];
                            $uploadedFileRowData[0][$key] = $productkey;
                        }
                    }
                }
                // End: Coverting uploaded file data attributes into magento attributes
            } catch (\Exception $e) {
                return ['error' => true, 'msg' => __('Problem in parsing XLSX file: %1', $e->getMessage())];
            }
        } else {
            $objPhpSpreadsheetReader = IOFactory::load($csvFilePath);

            $loadedSheetNames = $objPhpSpreadsheetReader->getSheetNames();

            $objWriter = IOFactory::createWriter($objPhpSpreadsheetReader, 'Csv');

            $csvXLSFilePath = $this->filesystem->getDirectoryWrite(
                    DirectoryList::MEDIA
                )->getAbsolutePath('/xlscoverted').$csvFile.'.csv';
            foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
                $objWriter->setSheetIndex($sheetIndex);
                $subject->saveObjectWriter($objWriter, $csvXLSFilePath);
            }
            $uploadedFileRowData = $subject->readCsv($csvXLSFilePath, $attributeMappedArr);
            if (!empty($uploadedFileRowData[0])) {
                if (!in_array('name', $uploadedFileRowData[0]) ||
                    !in_array('sku', $uploadedFileRowData[0]) ||
                    !in_array('price', $uploadedFileRowData[0])) {
                    return ['error' => true, 'msg' => __('The file format is invalid')];
                }
            } else {
                return ['error' => true, 'msg' => __('The file format is invalid')];
            }
        }

        $validateCsvData = $subject->validateCsvData($uploadedFileRowData);
        if ($validateCsvData['error']) {
            return $validateCsvData;
        }
        $productType = $validateCsvData['type'];
        $isDownloadableAllowed = $subject->isProductTypeAllowed('downloadable');
        if ($productType == 'downloadable' && $isDownloadableAllowed) {
            $validateLinkFiles = $subject->validateLinkFiles();
            if ($validateLinkFiles['error']) {
                return $validateLinkFiles;
            }
            if ($this->request->getParam('is_link_samples')) {
                $validateLinkSampleFiles = $subject->validateLinkSampleFiles();
                if ($validateLinkSampleFiles['error']) {
                    return $validateLinkSampleFiles;
                }
            }
            if ($this->request->getParam('is_samples')) {
                $validateSampleFiles = $subject->validateSampleFiles();
                if ($validateSampleFiles['error']) {
                    return $validateSampleFiles;
                }
            }
        }
        $result = [
            'error' => false,
            'type' => $productType,
            'csv' => $csvFile,
            'csv_data' => $uploadedFileRowData,
            'extension' => $validateCsv['extension']
        ];
        return $result;
    }

    /**
     * Upload Sample Files
     *
     * @param int $profileId
     * @param array $fileData
     * @param string $filePath
     * @param string $fileType
     *
     * @return void
     */
    public function aroundCopyFilesToDestinationFolder(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $profileId, $fileData, $filePath, $fileType
    ){
        $totalRows = $subject->getCount($fileData);
        $skuIndex = '';
        $fileIndex = '';
        $coFileIndex = '';
        $variationImagesIndex = '';
        foreach ($fileData[0] as $key => $value) {
            if ($value == 'sku') {
                $skuIndex = $key;
            }
            if ($value == $fileType) {
                $fileIndex = $key;
            }
            if ($value == 'co_variation') {
                $coFileIndex = $key;
            }
            if($value == 'variation_images') {
                $variationImagesIndex = $key;
            }
        }
        $fileTempPath = $filePath.'tempfiles/';
        for ($i=1; $i < $totalRows; $i++) {
            if (!empty($fileData[$i][$skuIndex]) && !empty($fileData[$i][$fileIndex])) {
                $sku = $fileData[$i][$skuIndex];
                $destinationPath = $filePath.$sku;
                $isDestinationExist = 0;
                $files = array_map('trim', explode(',', $fileData[$i][$fileIndex]));
                if (!empty($fileData[$i][$coFileIndex])) {
                    $options = array_map('trim', explode(',', $fileData[$i][$coFileIndex]));
                    foreach ($options as $k => $option) {
                        $optionData = array_map('trim', explode('|', $option));
                        $isSync = (int)trim($optionData[5]);
                        if (!$isSync) {
                            $newFiles = array_map('trim', explode(';', $optionData[2]));
                            $files = array_merge($files, $newFiles);
                        }
                    }
                }
                if (!empty($variationImagesIndex) && !empty($fileData[$i][$variationImagesIndex])) {
                    $variationImages = array_map('trim', explode(',', $fileData[$i][$variationImagesIndex]));
                    foreach ($variationImages as $k => $image) {
                        $image = array_map('trim', explode('|', $image));
                        if(count($image) < 2) {
                            continue;
                        }
                        if(strpos($image[1], ';') === false) {
                            $image = [$image[1]];
                        } else {
                            $image = array_map('trim', explode(';', $image[1]));
                        }
                        foreach ($image as $img) {
                            $files[] = $img;
                        }
                    }
                }
                $files = array_unique($files);
                foreach ($files as $file) {
                    if (empty(trim($file))) {
                        continue;
                    }
                    $sourcefilePath = $fileTempPath.$file;
                    if ($this->mediaDirectory->isExist($sourcefilePath)) {
                        if ($isDestinationExist == 0) {
                            $isDestinationExist = $subject->createDirectoryAtDestination($destinationPath);
                        }
                        $this->mediaDirectory->copyFile(
                            $sourcefilePath,
                            $destinationPath.'/'.$file
                        );
                    }
                }
            }
        }
        $this->mediaDirectory->delete($fileTempPath);
    }

    /**
     * Get Array From String
     *
     * @param string $string
     * @param string $delimiter
     * @return array
     */
    public function aroundGetArrayFromString(
        MpMassUploadHelper $subject,
        callable           $proceed,
        $string, $delimiter = ",")
    {
        if (strpos($string, $delimiter) !== false) {
            $data = array_map('trim', explode($delimiter, $string));
        } else {
            $data = [$string];
        }
        return $data;
    }

    public function afterGetAllStores(
        MpMassUploadHelper $subject,
        $result
    ){
        if (!empty($result)) {
            foreach ($result as $key => $store) {
                if ($key > 0) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }

    /**
     * Retrieve config for attributes mapping.
     *
     * @return array
     */
    public function getAttributeMapping(): array
    {
        $items = [];
        $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_MAPPING_ATTRIBUTE);
        if (!empty($configs) && $configs !== '[]') {
            foreach ($this->json->unserialize($configs) as $item) {
                $items[] = $item;
            }
        }
        return $items;
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
            $product = $this->_productRepository->getById($productId, false, 0);
            if (!empty($product->getId())) {
                foreach ($product->getOptions() as $option) {
                    $otpType = $option->getType();
                    if ($this->customOptionDropdownOnly()) {
                        if ($otpType == "drop-down" || $otpType == "drop_down") {
                            $optionIdsArr[] = $option->getOptionId();
                        }
                    } else {
                        if ($otpType == "drop-down" || $otpType == "drop_down" || $otpType == "radio") {
                            $optionIdsArr[] = $option->getOptionId();
                        }
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
            $product = $this->_productRepository->getById($productId, false, 0);
            $attributeSetId = $product->getAttributeSetId();
            $isTicket = in_array($attributeSetId, $this->getTicketAttributeSet());

            foreach ($variations as $variation) {
                $variationFactory = $this->variationFactory->create();
                $variation['product_id'] = $productId;

                $variationFactory->addData($variation);
                $variationFactory->save();
            }

            // Hotai Customization: After all normal variations are inserted by Mass Upload,
            // strictly synchronize the Ticket inventory globally with one action.
            if ($isTicket) {
                $this->marketplaceStagingHelper->syncTicketVariations($product);
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

    public function processVariationImageData($wholeData, &$data, $profileId)
    {
        if (!empty($data['product']['co_variation']) && !empty($data['product']['images'])) {
            $options = array_map('trim', explode(',', $data['product']['co_variation']));
            $sku = $data['product']['sku'];
            foreach ($options as $k => $option) {
                $optionData = array_map('trim', explode('|', $option));
                $isSync = (int) trim($optionData[5]);
                $images = array_map('trim', explode(';', $optionData[2]));
                if (!$isSync) {
                    $dirList = $this->directoryList->getPath('media');
                    foreach ($images as $key => $value) {
                        $imageName = '/'.$profileId.'/'.$sku.'/'.$value;
                        $imagePath = $dirList.'/tmp/catalog/product'.$imageName;
                        if (!empty(trim($value)) && $this->mediaDirectory->isExist($imagePath)) {
                            $baseImagePath = $this->getFilePath($dirList.'/wkosi/products', $imageName);
                            $this->mediaDirectory->copyFile(
                                $imagePath,
                                $baseImagePath
                            );
                            $images[$key] = $imageName;
                        }
                    }
                    $optionData[2] = implode(';', $images);
                    $options[$k] = implode('|', $optionData);
                } else {
                    $optionData[2] = '';
                    $options[$k] = implode('|', $optionData);
                }
            }
            $data['product']['co_variation'] = implode(',', $options);
        }
        return $wholeData;
    }

    public function processVariationImageDataForNewFormat(&$wholeData, &$data, $profileId){
        if (isset($data['product']['variation_images']) && $data['product']['variation_images']) {
            $sku = $data['product']['sku'];
            // keep old sku for update case to images
            $wholeData['product']['old_sku'] = $data['product']['sku'];
            $variationImages =  array_map('trim', explode(',', $data['product']['variation_images']));
            $processedVariations = [];

            foreach($variationImages as $images){
                $value = array_map('trim', explode('|', $images));
                if(count($value) === 2){
                    $imageNames = [];
                    if(!empty($value[1]) && strpos($value[1], ';') !== false){
                        $imageNames = array_map('trim', explode(';', $value[1]));
                    } elseif(!empty($value[1])){
                        $imageNames[] = trim($value[1]);
                    }

                    $validImageNames = [];
                    $dirList = $this->directoryList->getPath('media');

                    foreach ($imageNames as $imageName) {
                        $imagePath = $dirList.'/tmp/catalog/product/'.$profileId.'/'.$sku.'/'.$imageName;
                        if (!empty(trim($imageName)) && $this->mediaDirectory->isExist($imagePath)) {
                            $baseImagePath = $this->getFilePath($dirList.'/wkosi/products', '/'.$profileId.'/'.$sku.'/'.$imageName);
                            // Only copy if destination doesn't exist or to ensure fresh copy
                            $this->mediaDirectory->copyFile(
                                $imagePath,
                                $baseImagePath
                            );
                            $validImageNames[] = $imageName;
                        }
                    }

                    // Reconstruct the variation entry with only valid images
                    if (!empty($validImageNames)) {
                        $newValue = $value[0] . '|' . implode(';', $validImageNames);
                        $processedVariations[] = $newValue;
                    } else {
                        // If no valid images, keep the variation mapping but with empty image
                         $processedVariations[] = $value[0] . '|';
                    }
                }
            }

            // Update the data with filtered images
            $data['product']['variation_images'] = implode(',', $processedVariations);
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
     * Get path of uploaded images
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMediaUrl($filename)
    {
        return $this->_storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).'wkosi/products' . $filename;
    }

    /**
     * Get Ticket Attribute Set
     *
     * @return string[]
     */
    public function getTicketAttributeSet(){
        return explode(',', (string) $this->scopeConfig->getValue('virtual_ticket/general/ticket_attribute_set'));
    }


    /**
     * Flag custom option dropdown only
     *
     * @return bool
     */
    public function customOptionDropdownOnly()
    {
        return (bool) $this->scopeConfig->getValue(Config::XML_PATH_ONLY_ENABLE_DROPDOWN);
    }

    public function saveInventoryLogForNewProduct($productId){
        $stockItem = $this->salable->getProductStockItem($productId);
        if($stockItem){
            $user = $this->reportHelper->getUpdatedByUser();
            $userId = reset($user);
            $mess = '';
            if (empty($userId)) {
                $mess = __('Stock init by system');
            } else {
                $type = key($user);
                if ($type === UserType::TYPE_ADMIN) {
                    $mess = __('Stock init via admin mass upload');
                } elseif ($type === UserType::TYPE_SELLER) {
                    $mess = __('Stock init via seller mass upload');
                }
            }
            $this->salable->saveStockMovementLog($stockItem, 0, $stockItem->getQty(), $mess);
        }
    }

    protected function convertData($string, $delimiter = ','){
        // convert from string format "correspondedAlphabet|value,correspondedAlphabet|value" to array format
        $data = [];
        if (strpos($string, $delimiter) !== false) {
            $dataString = explode($delimiter, $string);
            foreach ($dataString as $key => $value) {
                $valueData = explode('|', $value);
                if(count($valueData) === 2){
                    $data[trim($valueData[0])] = trim($valueData[1]);
                }
            }
        } else {
            $data = [$string];
        }
        return $data;
    }

    protected function formatCustomOptionsFromDetail($wholeData, $option_title, $option_detail, $option_type = 'drop_down', $sort_option = 0)
    {
        // Only apply for drop_down options and variation options (new format import)
        if($option_type !== 'drop_down'){
            return [];
        }
        $option_values = explode(',', $option_detail);
        $option = [];
        $option['option_id'] = '';
        $option['title'] = $option_title;
        $option['type'] = $option_type;
        $option['is_require'] = 1;
        $option['sort_order'] = $sort_option;
        $option['values'] = [];

        foreach ($option_values as $key => $value) {
            $valueData = explode('|', $value);
            $sort_order = $key + 1;
            $is_visible = 1;
            if(isset($valueData[3])){
                if(strtolower($valueData[3]) == 'enable' || $valueData[3] == '1'){
                    $is_visible = 1;
                } elseif (strtolower($valueData[3]) == 'disable' || $valueData[3] == '0') {
                    $is_visible = 0;
                }
            }
            $option['values'][] = [
                'value_id' => '',
                'title' => trim($valueData[1]),
                'price' => 0,
                'price_type' => 'fixed',
                'sku' => trim($valueData[2]),
                'sort_order' => $sort_order,
                'is_visible' => $is_visible
            ];
        }
        if (empty($option['values'])) {
            return [];
        }
        return $option;
    }

    protected function getCorrespondedAlphabet($wholeData){
        $correspondedAlphabet = [];
        $variation_comb = [];
        $rangeList = $this->salable->getRangeList();
        foreach ($rangeList as $letter) {
            if (!empty($wholeData['product']['option_title_'.$letter]) && !empty($wholeData['product']['option_detail_'.$letter])) {
                $option_detail = explode(',', $wholeData['product']['option_detail_'.$letter]);
                $correspondedAlphabetL = [];
                $variation_combL = [];
                foreach ($option_detail as $key => $value) {
                    $valueData = explode('|', $value);
                    $correspondedAlphabetL[] = trim($valueData[0]);
                    $variation_combL[] = trim($valueData[1]);
                }
                if(count($correspondedAlphabetL) > 0 && count($variation_combL) > 0){
                    $correspondedAlphabet[] = $correspondedAlphabetL;
                    $variation_comb[] = $variation_combL;
                }
            }
        }
        if(count($correspondedAlphabet)){
            $correspondedAlphabet = $this->salable->combineArrays($correspondedAlphabet);
        }
        if(count($variation_comb)){
            $variation_comb = $this->salable->combineArrays($variation_comb);
        }
        return [$correspondedAlphabet, $variation_comb];
    }

    public function buildCustomOptionData($wholeData){
        $old_format = false;
        $customOptions = [];
        $swatches = [];
        list($correspondedAlphabet, $variation_comb) = $this->getCorrespondedAlphabet($wholeData);
        $variationFlag = false;
        $sort_option = 0;
        $rangeList = $this->salable->getRangeList();
        foreach ($rangeList as $letter) {
            if (!empty($wholeData['product']['option_title_'.$letter]) && !empty($wholeData['product']['option_detail_'.$letter])) {
                $customOptions[] = $this->formatCustomOptionsFromDetail(
                    $wholeData,
                    $wholeData['product']['option_title_'.$letter],
                    $wholeData['product']['option_detail_'.$letter],
                    'drop_down',
                    $sort_option
                );
                $swatches[$wholeData['product']['option_title_'.$letter]] = [
                    'title' => $wholeData['product']['option_title_'.$letter],
                    'is_swatch' => 1
                ];
                $sort_option++;
            }
        }
        if (count($customOptions) > 0) {
            $variationFlag = true;
            return [$customOptions, $swatches, $correspondedAlphabet, $variation_comb, $variationFlag, $old_format];
        }

        // Keep the old format for custom options
        if (!empty($wholeData['product']['custom_option'])) {
            $options = array_map('trim', explode(',', $wholeData['product']['custom_option']));
            $values = [];
            foreach ($options as $option) {
                if (empty($option)) {
                    continue;
                }
                $optionData = array_map('trim', explode('|', $option));
                $optType = $optionData[4];
                $optType = ($optType == 'drop-down') ? 'drop_down' : $optType;
                if (!empty($wholeData['id']) && !empty($optionData[0])) {
                    try {
                        $this->productOptionRepository->get($wholeData['product']['sku'], $optionData[0]);
                        $optionId = $optionData[0];
                    } catch (\Exception $e) {
                        $optionId = '';
                    }
                } else {
                    $optionId = '';
                }
                $variationFlag = false;
                if ($this->customOptionDropdownOnly()) {
                    if ($optType == 'drop_down') {
                        $variationFlag = true;
                    }
                } else {
                    if ($optType == 'drop_down' || $optType == 'radio') {
                        $variationFlag = true;
                    }
                }
                if (!empty($wholeData['product']['co_variation']) && $variationFlag) {
                    $values[$optionData[1]][$optionData[8]] = [
                        'value_id' => (!empty($wholeData['id']) && $optionId) ? $optionData[5] : '',
                        'title' => $optionData[6],
                        'price' => $optionData[10],
                        'price_type' => strtolower($optionData[9]),
                        'sku' => $optionData[7],
                        'sort_order' => $optionData[8],
                        'is_visible' => (isset($optionData[11])) ? $optionData[11] : 1
                    ];
                    $customOptions[$optionData[1]] = [
                        'option_id' => $optionId,
                        'title' => $optionData[3],
                        'type' => $optType,
                        'is_require' => $optionData[2],
                        'sort_order' => $optionData[1],
                        'values' => $values[$optionData[1]]??[]
                    ];

                    if ($optionId) {
                        $swatches[$optionData[1]] = [
                            'option_id' => $optionId,
                            'title' => $optionData[3],
                            'is_swatch' => 1
                        ];
                    } else {
                        $swatches[$optionData[1]] = [
                            'title' => $optionData[3],
                            'is_swatch' => 1
                        ];
                    }
                } else {
                    if ($optType == "drop_down" || $optType == "radio" || $optType == "multiple" || $optType == "checkbox") {
                        $values[$optionData[1]][$optionData[8]] = [
                            'value_id' => (!empty($wholeData['id']) && $optionId) ? $optionData[5] : '',
                            'title' => $optionData[6],
                            'price' => $optionData[10],
                            'price_type' => strtolower($optionData[9]),
                            'sku' => $optionData[7],
                            'sort_order' => $optionData[8],
                            'is_visible' => (isset($optionData[11])) ? $optionData[11] : 1
                        ];
                        $customOptions[$optionData[1]] = [
                            'option_id' => $optionId,
                            'title' => $optionData[3],
                            'type' => $optType,
                            'is_require' => $optionData[2],
                            'sort_order' => $optionData[1],
                            'values' => $values[$optionData[1]]??[]
                        ];
                    } else {
                        if ($optType == 'file') {
                            $fileExtension = ($optionData[8]) ? str_replace(';', ',', $optionData[8]) : 'png, jpg';
                            $customOptions[$optionData[1]] = [
                                'option_id' => $optionId,
                                'sort_order' => $optionData[1],
                                'is_require' => $optionData[2],
                                'title' => $optionData[3],
                                'type' => $optionData[4],
                                'sku' => $optionData[5],
                                'price_type' => strtolower($optionData[6]),
                                'price' => $optionData[7],
                                'file_extension' => $fileExtension
                            ];
                        } elseif ($optType == 'field' || $optType == 'area') {
                            $customOptions[$optionData[1]] = [
                                'option_id' => $optionId,
                                'sort_order' => $optionData[1],
                                'is_require' => $optionData[2],
                                'title' => $optionData[3],
                                'type' => $optionData[4],
                                'sku' => $optionData[5],
                                'price_type' => strtolower($optionData[6]),
                                'price' => $optionData[7],
                                'max_characters' => $optionData[8] ?? ''
                            ];
                        } else {
                            $customOptions[$optionData[1]] = [
                                'option_id' => $optionId,
                                'sort_order' => $optionData[1],
                                'is_require' => $optionData[2],
                                'title' => $optionData[3],
                                'type' => $optionData[4],
                                'sku' => $optionData[5],
                                'price_type' => strtolower($optionData[6]),
                                'price' => $optionData[7]
                            ];
                        }
                    }
                }
            }
        }
        if (!empty($wholeData['product']['co_variation']) && $variationFlag) {
            $old_format = true;
            $options = array_map('trim', explode(',', $wholeData['product']['co_variation']));
            foreach ($options as $option) {
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
                    if(isset($wholeData['product']['special_price']) && !empty($wholeData['product']['special_price'])){
                        $price = $wholeData['product']['special_price'];
                    } else {
                        $price = $wholeData['product']['price'] ?? 0;
                    }
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
                if ($isSync) {
                    try {
                        $product = $this->_productRepository->get($sku);
                        if ($product->getId() && $product->getTypeId() == 'simple') {
                            $qty = $this->salable->getQtyBySku($sku);
                            $images = $product->getMediaGalleryEntries();
                            $dataImages = [];
                            foreach ($images as $image) {
                                try {
                                    $this->saveFile($image->getFile());
                                    $dataImages[] = $image->getFile();
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            $weight = $product->getWeight();
                            $stock = $qty;
                            $images = implode(',',$dataImages);
                        }
                    } catch (NoSuchEntityException $e) {
                    }
                }
                $variation_comb[] = [
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
        }
        return [$customOptions, $swatches, $correspondedAlphabet, $variation_comb, $variationFlag, $old_format];
    }

    protected function removeLastCharacter($string)
    {
        $lastChar = substr($string, -1);
        if ($lastChar === '_') {
            $string = substr($string, 0, -1);
        }
        return $string;
    }

    public function buildVariationData($wholeData, $correspondedAlphabet, $variation_comb, $variationFlag)
    {
        if($variationFlag) {
            $variationData = [];
            $weight = $wholeData['product']['weight'] ?? '';
            $images = [];
            $productSku = $wholeData['product']['old_sku'] ?? $wholeData['product']['sku'];
            if (!empty($wholeData['product']['variation_images']) ) {
                $images = $this->convertData($wholeData['product']['variation_images']);
            }
            $stocks = [];
            if (!empty($wholeData['product']['variation_qty']) ) {
                $stocks = $this->convertData($wholeData['product']['variation_qty']);
            }
            $prices = [];
            if (!empty($wholeData['product']['variation_price']) ) {
                $prices = $this->convertData($wholeData['product']['variation_price']);
            }
            $skus = [];
            if (!empty($wholeData['product']['variation_sku']) ) {
                $skus = $this->convertData($wholeData['product']['variation_sku']);
            }
            $variation_cost_settings = [];
            if (!empty($wholeData['product']['variation_cost_setting']) ) {
                $variation_cost_settings = $this->convertData($wholeData['product']['variation_cost_setting']);
            }
            $variation_commission_rates = [];
            if (!empty($wholeData['product']['variation_commission_rate']) ) {
                $variation_commission_rates = $this->convertData($wholeData['product']['variation_commission_rate']);
            }
            $variation_costs = [];
            if (!empty($wholeData['product']['variation_cost']) ) {
                $variation_costs = $this->convertData($wholeData['product']['variation_cost']);
            }
            $variation_follow_simple_sku_cost = 0;
            if (!empty($wholeData['product']['variation_follow_simple_sku_cost']) ) {
                $variation_follow_simple_sku_costs = $this->convertData($wholeData['product']['variation_follow_simple_sku_cost']);
                if(count($variation_follow_simple_sku_costs) > 1){
                    foreach($variation_follow_simple_sku_costs as $key => $value){
                        if($value == 1 || $value == '1' || $value == 'Yes'){
                            $variation_follow_simple_sku_cost = 1;
                            break;
                        }
                    }
                } else {
                    if($wholeData['product']['variation_follow_simple_sku_cost'] == '1' || $wholeData['product']['variation_follow_simple_sku_cost'] == 'Yes'){
                        $variation_follow_simple_sku_cost = 1;
                    } elseif ($wholeData['product']['variation_follow_simple_sku_cost'] == '0' || $wholeData['product']['variation_follow_simple_sku_cost'] == 'No') {
                        $variation_follow_simple_sku_cost = 0;
                    }
                }

            }
            $variation_follow_simple_sku_price = 0;
            if (!empty($wholeData['product']['variation_follow_simple_sku_price']) ) {
                $variation_follow_simple_sku_prices = $this->convertData($wholeData['product']['variation_follow_simple_sku_price']);
                if(count($variation_follow_simple_sku_prices) > 1){
                    foreach($variation_follow_simple_sku_prices as $key => $value){
                        if($value == 1 || $value == '1' || $value == 'Yes'){
                            $variation_follow_simple_sku_price = 1;
                            break;
                        }
                    }
                } else {
                    if($wholeData['product']['variation_follow_simple_sku_price'] == '1' || $wholeData['product']['variation_follow_simple_sku_price'] == 'Yes'){
                        $variation_follow_simple_sku_price = 1;
                    } elseif ($wholeData['product']['variation_follow_simple_sku_price'] == '0' || $wholeData['product']['variation_follow_simple_sku_price'] == 'No') {
                        $variation_follow_simple_sku_price = 0;
                    }
                }
            }

            $existingImagesMap = [];
            if (!empty($wholeData['product']['sku'])) {
                $productRowId = $this->getProductRowIdBySku($wholeData['product']['sku']);
                if ($productRowId) {
                    $existingImagesMap = $this->getExistingVariationImages($productRowId);
                }
            }

            foreach ($variation_comb as $key => $value) {
                $value = $this->removeLastCharacter($value);
                $variation_image = '';
                $alphabet = $correspondedAlphabet[$key] ?? '';
                $alphabet = $this->removeLastCharacter($alphabet);
                $alphabet = str_replace('_', '-', $alphabet);

                // Process new images from $images array
                if(isset($images[$alphabet]) && !empty($images[$alphabet])){
                    if (!is_array($images[$alphabet])) {
                        $new_variation_image_raw = $images[$alphabet];
                        if (strpos($new_variation_image_raw, ';') !== false) {
                            $imageNames = array_map('trim', explode(';', $new_variation_image_raw));
                            $variation_images_processed = [];
                            foreach ($imageNames as $imageName) {
                                if (!empty($imageName)) {
                                    $variation_images_processed[] = '/'.$wholeData['profile_id'].'/'.$productSku.'/'.$imageName;
                                }
                            }
                            $variation_image = implode(';', $variation_images_processed);
                        } else {
                            if (!empty(trim($new_variation_image_raw))) {
                                $variation_image = '/'.$wholeData['profile_id'].'/'.$productSku.'/'.$new_variation_image_raw;
                            }
                        }
                    }
                }

                // Merge existing images with new images
                // $value is variation combination
                if (isset($existingImagesMap[$value])) {
                    $existingImageStr = $existingImagesMap[$value];

                    $newImagesArr = !empty($variation_image) ? array_map('trim', explode(';', $variation_image)) : [];
                    $oldImagesArr = !empty($existingImageStr) ? array_map('trim', explode(';', $existingImageStr)) : [];

                    $mergedImages = array_unique(array_merge($oldImagesArr, $newImagesArr));
                    $variation_image = implode(';', array_filter($mergedImages));
                }

                $stock = 0;
                if(isset($stocks[$alphabet])){
                    if (!$this->isNumericStrict($stocks[$alphabet])) {
                        throw new CouldNotSaveException(__('Product Variation Stock is required.'));
                    }
                    $stock = $stocks[$alphabet];
                }
                $sku = '';
                if(isset($skus[$alphabet])){
                    if (empty($skus[$alphabet])) {
                        throw new CouldNotSaveException(__('Product Variation SKU is required.'));
                    }
                    $sku = $skus[$alphabet];
                }
                $variation_cost_setting = '';
                $variation_commission_rate = '';
                $variation_cost = '';
                $finalPrice = 0;
                if($variation_follow_simple_sku_price == 1){
                    if(isset($wholeData['product']['special_price']) && !empty($wholeData['product']['special_price'])){
                        $finalPrice = $wholeData['product']['special_price'];
                    } else {
                        $finalPrice = $wholeData['product']['price'] ?? 0;
                    }
                } else {
                    if(isset($prices[$alphabet])){
                        if (empty($prices[$alphabet])) {
                            throw new CouldNotSaveException(__('Product Variation Price is required.'));
                        }
                        $finalPrice = $prices[$alphabet];
                    } else {
                        if(isset($wholeData['product']['special_price']) && !empty($wholeData['product']['special_price'])){
                            $finalPrice = $wholeData['product']['special_price'];
                        } else {
                            $finalPrice = $wholeData['product']['price'] ?? 0;
                        }
                    }
                }

                if($variation_follow_simple_sku_cost == 1){
                    $variation_cost_setting = $wholeData['product']['cost_setting'] ?? '';
                    $variation_commission_rate = $wholeData['product']['commission_percent'] ?? '';
                    $variation_cost = $wholeData['product']['cost'] ?? '';
                } else {
                    if(isset($variation_cost_settings[$alphabet])){
                        $variation_cost_setting = $variation_cost_settings[$alphabet];
                    }
                    if(strcasecmp($variation_cost_setting, 'Fixed Commission') == 0){
                        $variation_cost_setting = 1;
                    } elseif(strcasecmp($variation_cost_setting, 'Manually Input') == 0){
                        $variation_cost_setting = 0;
                    }
                    if(isset($variation_commission_rates[$alphabet])){
                        $variation_commission_rate = $variation_commission_rates[$alphabet];
                    }
                    if(isset($variation_costs[$alphabet])){
                        if (empty($variation_costs[$alphabet])) {
                            throw new CouldNotSaveException(__('Product Variation Cost is required.'));
                        }
                        $variation_cost = $variation_costs[$alphabet];
                    }
                }
                if(!empty($variation_cost_setting)){
                    list($variation_cost, $variation_commission_rate) = $this->salable->calculateProductCost(
                        $finalPrice, $variation_cost_setting, $variation_commission_rate, $variation_cost
                    );
                }

                $variationData[] = [
                    'comb' => $value,
                    'weight' => $weight,
                    'image' => $variation_image,
                    'stock' => $stock,
                    'sku' => $sku,
                    'is_sync' => 0,
                    'follow_simple_sku_cost_setting'  => $variation_follow_simple_sku_cost,
                    'cost_setting' => $variation_cost_setting,
                    'commission_percent' => $variation_commission_rate,
                    'cost' => $variation_cost,
                    'price' => $finalPrice,
                    'follow_simple_sku_price_setting'  => $variation_follow_simple_sku_price
                ];
            }
            return $variationData;
        }
    }

    /**
     * Get existing variation images from database
     *
     * @param int $productRowId
     * @return array
     */
    protected function getExistingVariationImages($productRowId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('wk_osi_variations');

            if (!$productRowId) {
                return [];
            }

            // Get variations
            $select = $connection->select()
                ->from($tableName, ['comb', 'image'])
                ->where('product_id = ?', $productRowId);

            $rows = $connection->fetchAll($select);

            $existingImages = [];
            foreach ($rows as $row) {
                if (!empty($row['image'])) {
                    $existingImages[$row['comb']] = $row['image'];
                }
            }

            return $existingImages;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get Product Row ID by SKU
     *
     * @param string $sku
     * @return string|false
     */
    protected function getProductRowIdBySku($sku)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $productTable = $connection->getTableName('catalog_product_entity');

            $select = $connection->select()
                ->from($productTable, ['row_id'])
                ->where('sku = ?', $sku)
                ->order('row_id DESC')
                ->limit(1);

            return $connection->fetchOne($select);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $value
     * @return false|int
     */
    public function isNumericStrict($value) {
        return is_string($value) || is_int($value) || is_float($value)
            ? preg_match('/^\d+(\.\d+)?$/', $value)
            : false;
    }
}
