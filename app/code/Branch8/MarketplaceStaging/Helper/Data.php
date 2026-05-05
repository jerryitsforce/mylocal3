<?php
namespace Branch8\MarketplaceStaging\Helper;

use Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig;
use Exception;
use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;
use Branch8\FlagshipStore\Helper\Sales;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Branch8\MarketplaceProduct\Model\Config as MarketplaceProductConfig;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Config\Source\ProductPriceOptionsInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Msrp\Model\Config;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Webkul\Marketplace\Model\SaleperpartnerFactory;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Webkul\Marketplace\Helper\Data as WkMpHelperData;
use Magento\Framework\Filter\Input\PurifierInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Branch8\Catalog\Model\ConfigData;
/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private const XML_PATH_ATTRIBUTE_SELECTOR = 'marketplace/branch8_product_approval/attribute_selector';
    private const XML_PATH_ATTRIBUTE_HINT = 'marketplace/producthint_settings/hint_attributes';
    public const ALLOW_ATTRIBUTE_LIST = [
        'virtual_product_type',
        'country_of_manufacture',
        'is_returnable',
        'display_serial_number',
        'display_barcode',
        'barcode_type',
        'is_offline_operation',
        'exchange_url',
        'exchange_hint',
        'return_ticket_value',
    ];

    public const VIRTUAL_SET_MAP = [
        'ticket' => 1,
        'ticket_yoxi' => 2,
        'ticket_edenred' => 3,
        'ticket_fami' => 4,
        'ticket_redeem' => 5,
        'ticket_non_redeem' => 6,
        'ticket_7ELEVEN' => 7,
        'ticket_openhub' => 8
    ];

    /**
     * Customer groups cache
     *
     * @var array
     */
    protected $customerGroups;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ProductAttributeManagementInterface
     */
    protected $productAttributeManagement;

    /**
     * @var AttributeRepository
     */
    protected $attributeOptionRepository;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductPriceOptionsInterface
     */
    private $productPriceOptions;

    /**
     * @var MarketplaceProductConfig
     */
    protected $marketplaceProductConfig;

    /**
     * @var MpProductFactory
     */
    protected $mpProductFactory;
    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;
    /**
     * @var WkMpHelperData
     */
    protected $wkMpHelperData;
    /**
     * @var SaleperpartnerFactory
     */
    protected $saleperPartner;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var SellerCollectionFactory
     */
    protected $sellerCollectionFactory;

    /**
     * @var array
     */
    protected static $_attributeSetNameCache = [];

    /**
     * @var array
     */
    protected static $_ticketStockCache = [];

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var Tree
     */
    protected $tree;

    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    private $json;

    /**
     * @var ProductCopyTracking
     */
    private ProductCopyTracking $productCopyTracking;

    /**
     * @var PurifierInterface
     */
    private PurifierInterface $purifier;

    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipStoreSalesHelper;

    protected array $attributesHint;
    protected $allowedCategoryIds;
    protected array $flagshipCategory;
    protected array $shownCategoriesIds;
    protected int $minLevel = 2;/**Use for Flag category attribute */
    protected $flagShipSoreId = null;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private GetSalableQuantityDataBySku $getSalableQuantityDataBySku;

    private \Branch8\Catalog\Model\ConfigData $configData;

    protected $commissionSource;

    protected $contractCollectionFactory;

    private MediaGalleryUploaderConfig $mediaGaleryUploaderConfig;
    
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var \Branch8\MarketplaceStaging\Model\Ticket\SynchronizerPool
     */
    protected $synchronizerPool;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     * @param ProductAttributeManagementInterface $productAttributeManagement
     * @param AttributeRepository $attributeOptionRepository
     * @param UpdateRepositoryInterface $updateRepository
     * @param VersionManager $versionManager
     * @param Config $config
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupManagementInterface $groupManagement
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductPriceOptionsInterface $productPriceOptions
     * @param MarketplaceProductConfig $marketplaceProductConfig
     * @param MpProductFactory $mpProductFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param WkMpHelperData $wkMpHelperData
     * @param SaleperpartnerFactory $saleperPartnerFactory
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param SellerCollectionFactory $sellerCollectionFactory
     * @param StockRegistryInterface $stockRegistry
     * @param CategoryRepository $categoryRepository
     * @param Tree $tree
     * @param Json $json
     * @param PurifierInterface $purifier
     * @param ProductCopyTracking $productCopyTracking
     * @param Sales $flagshipStoreSalesHelper
     * @param ConfigData $configData
     * @param \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource $commissionSource
     * @param \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context                            $context,
        TimezoneInterface                  $timezone,
        DateTime                           $dateTime,
        ProductAttributeManagementInterface $productAttributeManagement,
        AttributeRepository                $attributeOptionRepository,
        UpdateRepositoryInterface          $updateRepository,
        VersionManager                     $versionManager,
        Config                             $config,
        \Magento\Eav\Model\Config          $eavConfig,
        GroupRepositoryInterface           $groupRepository,
        GroupManagementInterface           $groupManagement,
        StoreManagerInterface              $storeManager,
        SearchCriteriaBuilder              $searchCriteriaBuilder,
        ProductPriceOptionsInterface       $productPriceOptions,
        MarketplaceProductConfig           $marketplaceProductConfig,
        MpProductFactory                   $mpProductFactory,
        CollectionFactory                  $categoryCollectionFactory,
        WkMpHelperData                     $wkMpHelperData,
        SaleperpartnerFactory              $saleperPartnerFactory,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        SellerCollectionFactory            $sellerCollectionFactory,
        StockRegistryInterface             $stockRegistry,
        CategoryRepository                 $categoryRepository,
        Tree                               $tree,
        Json                               $json,
        PurifierInterface                  $purifier,
        ProductCopyTracking                $productCopyTracking,
        GetSalableQuantityDataBySku        $getSalableQuantityDataBySku,
        Sales                              $flagshipStoreSalesHelper,
        \Branch8\Catalog\Model\ConfigData $configData,
        \Branch8\SellerContactInformation\Model\Config\Source\CommissionSource $commissionSource,
        \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractCollectionFactory,
        MediaGalleryUploaderConfig $mediaGalleryUploaderConfig,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepository,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Branch8\MarketplaceStaging\Model\Ticket\SynchronizerPool $synchronizerPool,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
        $this->productAttributeManagement = $productAttributeManagement;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->updateRepository = $updateRepository;
        $this->versionManager = $versionManager;
        $this->config = $config;
        $this->eavConfig = $eavConfig;
        $this->groupRepository = $groupRepository;
        $this->groupManagement = $groupManagement;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productPriceOptions = $productPriceOptions;
        $this->marketplaceProductConfig = $marketplaceProductConfig;
        $this->mpProductFactory = $mpProductFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->wkMpHelperData = $wkMpHelperData;
        $this->saleperPartner  = $saleperPartnerFactory;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->categoryRepository = $categoryRepository;
        $this->tree = $tree;
        $this->json = $json;
        $this->purifier = $purifier;
        $this->productCopyTracking = $productCopyTracking;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->flagshipStoreSalesHelper = $flagshipStoreSalesHelper;
        $this->configData=$configData;
        $this->commissionSource= $commissionSource;
        $this->contractCollectionFactory = $contractCollectionFactory;
        $this->mediaGaleryUploaderConfig = $mediaGalleryUploaderConfig;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->variationsFactory = $variationsFactory;
        $this->synchronizerPool = $synchronizerPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get attributes by attribute set id
     * @param int|null $setId
     * @param string|null $type
     * @return array
     */
    public function getAttributesByAttributeSetId(?int $setId, ?string $type): array
    {
        $attributeData = [];
        try {
            $attributes = $this->productAttributeManagement->getAttributes($setId);
            foreach($attributes as $k => $attribute)
            {
                if (!in_array($attribute->getAttributeCode(), self::ALLOW_ATTRIBUTE_LIST)
                    && ($attribute->getIsUserDefined() == 0
                        || $attribute->getAttributeCode() == 'cost'
                        || $attribute->getAttributeCode() == 'cost_setting'
                        || $attribute->getAttributeCode() == 'commission_percent'
                        || $attribute->getAttributeCode() == 'product_type')
                ) {
                    continue;
                }
                $attributeCatalog = $this->attributeOptionRepository->get($attribute->getAttributeCode());
                $applyTo = $attributeCatalog->getApplyTo();
                if (!empty($applyTo) && !in_array($type, $applyTo)) continue;
                $attributeData[$k] = $attribute;
                $attributeData[$k]->setIsWysiwygEnabled(
                    ($attribute->getFrontendInput() == 'textarea' && $attributeCatalog->getIsWysiwygEnabled()) ? 1 : 0
                );
            }
        } catch (NoSuchEntityException $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                $this->_logger->critical($e);
            }
        }
        return $attributeData;
    }

    /**
     * Get attributes by attribute set id
     * @param int|null $setId
     * @param string|null $type
     * @return array
     */
    public function getAllAttributesByAttributeSetId(?int $setId, ?string $type): array
    {
        $attributeData = [];
        try {
            $attributes = $this->productAttributeManagement->getAttributes($setId);
            foreach($attributes as $k => $attribute)
            {
                if (!in_array($attribute->getAttributeCode(), self::ALLOW_ATTRIBUTE_LIST)
                    && ($attribute->getAttributeCode() == 'cost_setting'
                        || $attribute->getAttributeCode() == 'commission_percent'
                        || $attribute->getAttributeCode() == 'product_type')
                ) {
                    continue;
                }
                $attributeCatalog = $this->attributeOptionRepository->get($attribute->getAttributeCode());
                $applyTo = $attributeCatalog->getApplyTo();
                if (!empty($applyTo) && !in_array($type, $applyTo)) continue;
                $attributeData[$attribute->getAttributeId()] = $attribute->getAttributeCode();
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e);
        }
        return $attributeData;
    }

    /**
     * Get staging info
     * @param int|null $updateId
     * @return array
     */
    public function getStagingInfo(?int $updateId): array
    {
        if ($updateId) {
            $update = null;
            try {
                $update = $this->updateRepository->get($updateId);
                $this->versionManager->setCurrentVersionId($update->getId());
                if ($update) {
                    $startTime = $update->getStartTime() ? $this->timezone->date(new \DateTime($update->getStartTime()))->format('m/d/Y h:i A') : '';
                    $endTime = $update->getEndTime() ? $this->timezone->date(new \DateTime($update->getEndTime()))->format('m/d/Y h:i A') : '';
                    return [
                        'update_id' => $update->getId(),
                        'name' => $update->getName(),
                        'description' => $update->getDescription(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ];
                }
            } catch (NoSuchEntityException $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                    $this->_logger->critical($e);
                }
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                    $this->_logger->critical($e);
                }
            }
        }
        return [
            'update_id' => '',
            'name' => '',
            'description' => '',
            'start_time' => '',
            'end_time' => '',
        ];
    }

    public function getStockItem($productId){
        return $this->stockRegistry->getStockItem($productId);
    }

    /**
     * Get store config timezone
     * @return string
     */
    public function getStoreTimezone(): string
    {
        return $this->timezone->getConfigTimezone();
    }

    /**
     * Flag to check if product can be removed from update
     * @param int|null $updateId
     * @return bool
     */
    public function canRemoveFromUpdate(?int $updateId): bool
    {
        if (null !== $updateId) {
            $update = $this->updateRepository->get($updateId);
            $startTime = $this->dateTime->gmtTimestamp($update->getStartTime());
            $currentDateTime = $this->dateTime->gmtTimestamp();
            return $currentDateTime < $startTime;
        }
        return false;
    }

    /**
     * Check if MSRP can be shown
     * @return bool
     */
    public function canShowMsrp(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Get MSRP display actual price type options
     * @return array
     */
    public function getMsrpTypeOptions(): array
    {
        try {
            $attribute = $this->eavConfig->getAttribute('catalog_product', 'msrp_display_actual_price_type');
            return $attribute->getSource()->getAllOptions();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve allowed customer groups
     *
     * @param int|null $groupId return name by customer group id
     * @return array|string
     */
    public function getCustomerGroups(int $groupId = null): array|string
    {
        try {
            if ($this->customerGroups === null) {
                $this->customerGroups = $this->_getInitialCustomerGroups();
                /** @var \Magento\Customer\Api\Data\GroupInterface[] $groups */
                $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create());
                foreach ($groups->getItems() as $group) {
                    $this->customerGroups[$group->getId()] = $group->getCode();
                }
            }

            if ($groupId !== null) {
                return $this->customerGroups[$groupId] ?? [];
            }

            return $this->customerGroups;
        } catch (NoSuchEntityException|LocalizedException $e) {
            return [];
        }
    }

    /**
     * Retrieve list of initial customer groups
     *
     * @return array
     */
    protected function _getInitialCustomerGroups(): array
    {
        try {
            return [$this->groupManagement->getAllCustomersGroup()->getId() => __('ALL GROUPS')];
        } catch (NoSuchEntityException|LocalizedException $e) {
            return [];
        }
    }

    /**
     * Retrieve default value for customer group
     *
     * @return int
     */
    public function getDefaultCustomerGroup(): int
    {
        try {
            return $this->groupManagement->getAllCustomersGroup()->getId();
        } catch (NoSuchEntityException|LocalizedException $e) {
            return 0;
        }
    }

    /**
     * Retrieve default value for website
     *
     * @return int
     */
    public function getDefaultWebsite($product = ''): int
    {
        return 0;
        /*try {
            if ($this->storeManager->isSingleStoreMode()) {
                return 0;
            } else {
                if ($product) {
                    return $this->storeManager->getStore($product->getStoreId())->getWebsiteId();
                } else {
                    return $this->storeManager->getStore()->getWebsiteId();
                }
            }
        } catch (NoSuchEntityException|LocalizedException $e) {
            return 0;
        }*/
    }

    /**
     * Retrieve price type options
     *
     * @return array
     */
    public function getPriceTypes(): array
    {
        return $this->productPriceOptions->toOptionArray();
    }

    /**
     * Gets list of product tier prices
     *
     * @param $tierPrices
     * @return array
     */
    public function getFormatTierPrices($tierPrices): array
    {
        $prices = [];
        if ($tierPrices) {
            foreach ($tierPrices as $price) {
                $tierPrice['price_id'] = $price['price_id'];
                $tierPrice['cust_group'] = $price['cust_group'];
                $tierPrice['price_qty'] = (float)$price['price_qty'] ?? 0;
                $typePrice = 'fixed';
                if (isset($price['percentage_value']) && $price['percentage_value'] > 0) {
                    $typePrice = 'percent';
                    $tierPrice['percentage_value'] = $price['percentage_value'] ?? 0;
                } else {
                    $tierPrice['price'] = $price['price'] ?? 0;
                }
                $tierPrice['value_type'] = $typePrice;
                $prices[] = $tierPrice;
            }
        }
        return $prices;
    }

    public function getAttributeSelectedByProduct($productId, $productRowId)
    {
        try {
            return $this->mpProductFactory->create()->getCollection()
                ->addFieldToFilter('mage_pro_row_id', $productRowId)
                ->addFieldToFilter('mageproduct_id', $productId)
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retrieve config for exclude attributes.
     *
     * @param array $listAttribute
     * @return array
     */
    /*public function getAttributeSelector(array $listAttribute): array
    {
        $result = $listAttribute;
        $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_SELECTOR);
        if (!empty($configs) && $configs !== '') {
            $items = explode(',', $configs);
            foreach ($listAttribute as $k => $attribute) {
                if (!in_array($attribute->getAttributeCode(), $items)) {
                    unset($result[$k]);
                }
            }
        }
        return $result;
    }*/

    /**
     * Retrieve config for hint attributes.
     *
     * @return array
     */
    public function getAttributesHint(): array
    {
        $items = [];
        if (!isset($this->attributesHint)) {
            $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_HINT);
            if (!empty($configs) && $configs !== '[]') {
                foreach ($this->json->unserialize($configs) as $item) {
                    $items[$item['attribute']] = $item['hint'];
                }
            }
            $this->attributesHint = $items;
        }
        return $this->attributesHint;
    }

    /**
     * Retrieve note html
     *
     * @param $code
     * @return string
     */
    public function getHintHtml($code): string
    {
        $result = '';
        $attributesHint = $this->getAttributesHint();
        if (array_key_exists($code, $attributesHint)) {
            $result = '<div class="admin__field-tooltip">
                        <a class="admin__field-tooltip-action action-help" target="_blank" tabindex="1">
                            <span>' . __('What is this?') . '</span>
                        </a>
                        <div class="admin__field-tooltip-content">'.nl2br($attributesHint[$code]).'</div>
                    </div>';
        }
        return $result;
    }

    /**
     * Return offset of current timezone with GMT in seconds
     *
     * @return int
     */
    public function getTimezoneOffsetSeconds()
    {
        return $this->dateTime->getGmtOffset();
    }

    /**
     * Getter for store timestamp based on store timezone settings
     *
     * @param null|string|bool|int|\Magento\Store\Model\Store $store
     * @return int
     */
    public function getStoreTimestamp($store = null)
    {
        return $this->timezone->scopeTimeStamp($store);
    }

    public function getCategoryCollection(){
        return $this->categoryCollectionFactory->create();
    }

    /**
     * Check a Seller can edit Large Item or Not
     * @return bool
     */
    public function isAllowSettingLargeItem(){
        $seller = $this->wkMpHelperData->getSeller();
        return isset($seller['is_allow_large_item']) ? (bool)$seller['is_allow_large_item'] : false;
    }

    public function formatDate($date){
        return $date ? $this->timezone->date(strtotime($date))->format('m/d/Y') : '';
    }

    public function getSellerInfo(){
        $sellerId = $this->wkMpHelperData->getCustomerId();
        return $this->getCommisionRates($sellerId);
    }

    public function getCommisionRates($sellerId){
        $partner = $this->saleperPartner->create()
            ->getCollection()
            ->addFieldToSelect(
                ['commission_rate', 'min_commission_rate', 'commission_status', 'default_commission_rate', 'default_min_commission_rate']
            )
            ->addFieldToFilter('seller_id', $sellerId)
            ->getFirstItem();
        // $commissionRate = null;
        // //check condition like core
        // if($partner->getCommissionStatus() == 1){
        //     $commissionRate = $partner->getCommissionRate();
        // }
        // if($commissionRate === null){
        //     $commissionRate = $this->wkMpHelperData->getConfigCommissionRate();
        // }
        /** Get Active contract */
        $activeContractCol = $this->contractCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('is_active', 1);
        $select = $activeContractCol->getSelect();
        $select->columns(new \Zend_Db_Expr('addtime(contracts_from, "08:00:00") as contract_from'))
            ->columns(new \Zend_Db_Expr('addtime(contracts_to, "08:00:00") as contract_to'));
        $activeContract = $activeContractCol->getFirstItem();
        if($activeContract->getId()){
            $activeContractData = ['from' => $activeContract->getContractFrom(), 'to' => $activeContract->getContractTo()];
        }else{
            $activeContractData = false;
        }
        return [
            'commission_rate' => (int)$partner->getCommissionRate(),
            'min_commission_rate' => (int)$partner->getMinCommissionRate(),
            'default_commission_rate' => (int)$partner->getDefaultCommissionRate(),
            'default_min_commission_rate' => (int)$partner->getDefaultMinCommissionRate(),
            'active_contract' => $activeContractData
        ];
    }

    public function getSellerSetting($sellerId){
        $sellerSetting = $this->sellerCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId)
            ->getFirstItem();
        return $sellerSetting;
    }

    /**
     * Get product temp data by temp id
     * @param int $tempId
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface|null
     */
    public function getProductTempDataRepositoryById($tempId): ?\Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface
    {
        try{
            return $this->productTempDataRepository->getById($tempId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get product temp data by product id
     * @param int $productId
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface|null
     */
    public function getProductTempDataRepositoryByProductId($productId): ?\Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface
    {
        try{
            return $this->productTempDataRepository->get($productId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get ticket attribute set
     * @return string
     */
    public function getTicketAttributeSet(){
        return $this->scopeConfig->getValue('virtual_ticket/general/ticket_attribute_set');
    }

    /**
     * Get tree by category ids
     * @return array
     */
    public function getTreeByCategoryIds($categoryIds)
    {
        $result = [];
        try {
            $categories = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', array('in' => $categoryIds));
            foreach ($categories as $category) {
                if ($category->getLevel() > 3) {
                    $storeId = $this->storeManager->getStore()->getId();
                    $categoryTree = $this->tree->setStoreId($storeId)->loadBreadcrumbsArray($category->getPath());

                    $categoryTreePath = '';
                    foreach ($categoryTree as $eachCategory) {
                        if ($eachCategory['level'] < 2) continue;
                        if (!$categoryTreePath) {
                            $categoryTreePath = $eachCategory['name'];
                        } else {
                            $categoryTreePath = $categoryTreePath . ' > ' . $eachCategory['name'];
                        }
                    }
                    $result[] = [
                        'value' => $category->getId(),
                        'label' => $categoryTreePath
                    ];
                }
            }
        } catch (\Exception $e) {
            return $result;
        }

        return $result;
    }

    public function getVirtualIdBySet($set)
    {
        return self::VIRTUAL_SET_MAP[$set] ?? 0;
    }

    public function getDisableOption($set, $code, $option)
    {
        if ($code == 'display_barcode') {
            if ($set == 'ticket_redeem') {
                if ($option > 1) {
                    return true;
                } else {
                    return false;
                }
            } elseif ($set == 'ticket_non_redeem') {
                return false;
            } elseif ($set == 'ticket_yoxi') {
                if ($option > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                if ($option == 1) {
                    return false;
                } else {
                    return true;
                }
            }
        } elseif ($code == 'display_serial_number') {
            if ($set == 'ticket_edenred' || $set == 'ticket_fami') {
                if ($option == 1) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get Flagship Category
     *
     * @return array
     */
    public function getFlagshipCategory()
    {
        if (!isset($this->flagshipCategory)) {
            $this->flagshipCategory = [];
            $flagshipCategory = $this->scopeConfig->getValue(
                'seller_flagship/general/categories',
                ScopeInterface::SCOPE_STORE
            );
            if ($flagshipCategory) {
                $this->flagshipCategory = explode(',', $flagshipCategory);
            }
        }
        return $this->flagshipCategory;
    }

    /**
     * Get Shown Category Ids
     *
     * @return array
     */
    public function getFlagshipCategoryIds()
    {
        if ($this->getAllowedCategoryIds()) {
            $flagshipCategory = $this->getFlagshipCategory();
            $shownCategoriesIds = $this->getShownCategoryIds();
            $shownCategoriesIds = array_keys($shownCategoriesIds);
            return array_intersect($shownCategoriesIds, $flagshipCategory);
        }
        return [];
    }

    /**
     * Allowed category ids
     *
     * @return array
     */
    protected function getAllowedCategoryIds()
    {
        $allowedCategories = '';
        $model = $this->wkMpHelperData->getSellerCollection()
            ->addFieldToFilter('seller_id', $this->wkMpHelperData->getCustomerId())
            ->addFieldToFilter('store_id', $this->wkMpHelperData->getCurrentStoreId());
        foreach ($model as $key => $value) {
            $allowedCategories = $value['allowed_categories'];
        }
        if ($allowedCategories == '') {
            $model = $this->wkMpHelperData->getSellerCollection()
                ->addFieldToFilter('seller_id', $this->wkMpHelperData->getCustomerId())
                ->addFieldToFilter('store_id', 0);
            foreach ($model as $key => $value) {
                $allowedCategories = $value['allowed_categories'];
            }
        }
        return $allowedCategories;
    }

    /**
     * Retrieve categories tree
     *
     * @param bool $flagship
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCategoriesTree($flagship = false)
    {
        $flagshipCategory = $this->getFlagshipCategory();
        $shownCategoriesIds = $this->getShownCategoryIds();
        $shownCategoriesIds = array_keys($shownCategoriesIds);
        if (!$flagship) {
            $shownCategoriesIds = array_diff($shownCategoriesIds, $flagshipCategory);
        } else {
            $shownCategoriesIds = array_intersect($shownCategoriesIds, $flagshipCategory);
            $shownCategoriesIds = $this->getShownFlagshipCategoryIds($shownCategoriesIds);
        }
        $flagShipStoreCate = null;
        if($flagship && $this->flagShipSoreId){
            $flagShipStoreInfo = $this->flagshipStoreSalesHelper->getFlagshipStoreInfor($this->flagShipSoreId);
            $flagShipStoreCate = $flagShipStoreInfo['category_id'];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('entity_id', ['in' => $shownCategoriesIds])
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'level']);

        $sellerCategory = [
            Category::TREE_ROOT_ID => [
                'value' => Category::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            $catId = $category->getId();
            $catParentId = $category->getParentId();

            /**
             * Specify flagship
             */
            if($flagShipStoreCate){
                if($category->getLevel() == 3 && $flagShipStoreCate != $catId){
                    continue;
                }
            }
            foreach ([$catId, $catParentId] as $categoryId) {
                if (!isset($sellerCategory[$categoryId])) {
                    $sellerCategory[$categoryId] = ['value' => $categoryId];
                }
            }

            $sellerCategory[$catId]['is_active'] = $category->getIsActive();
            $sellerCategory[$catId]['label'] = $category->getName();
            $sellerCategory[$catParentId]['optgroup'][] = &$sellerCategory[$catId];
        }

        return json_encode($sellerCategory[Category::TREE_ROOT_ID]['optgroup']);
    }

    /**
     * Get Shown Category Ids
     *
     * @return array
     */
    public function getShownCategoryIds()
    {
        if (isset($this->shownCategoriesIds)) {
            return $this->shownCategoriesIds;
        }
        if (!isset($this->allowedCategoryIds)) {
            $this->allowedCategoryIds = $this->wkMpHelperData->getAllowedCategoryIds();
        }
        $storeId = $this->wkMpHelperData->getCurrentStoreId();
        $categoryCollection = $this->categoryCollectionFactory->create();

        if ($this->allowedCategoryIds) {
            $allowedCategoryIds = explode(',', trim($this->allowedCategoryIds));
            $categoryCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['in' => $allowedCategoryIds])
                ->setStoreId($storeId);
        } else {
            $categoryCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => Category::TREE_ROOT_ID])
                ->setStoreId($storeId);
        }

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($categoryCollection as $category) {
            foreach (explode('/', $category['path']) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }
        $this->shownCategoriesIds = $shownCategoriesIds;

        return $this->shownCategoriesIds;
    }

    /**
     * Get Shown Category Ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function getShownFlagshipCategoryIds($arrayId)
    {
        if (empty($arrayId)) {
            return [];
        }
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('level')->addAttributeToSelect('path')
            ->addAttributeToFilter('entity_id', ['in' => $arrayId]);

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        $level = 0;
        foreach ($categoryCollection as $category) {
            if (!$level) {
                $level = $category['level'];
            } elseif ($level > $category['level']) {
                $level = $category['level'];
            }
            foreach (explode('/', $category['path']) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }
        // $this->minLevel = $level;

        return array_keys($shownCategoriesIds);
    }

    public function isSellerInFlagshipStore($sellerId)
    {
        $result = false;
        $flShipStoreId = $this->flagshipStoreSalesHelper->getFlagshipStoreFromSellerId($sellerId);
        if ((int)$flShipStoreId) {
            $this->flagShipSoreId = (int)$flShipStoreId;
            $result = true;
        }

        return $result;
    }

    public function isInFlagshipStore($productId)
    {
        $result = false;
        $sellerId = (int)$this->wkMpHelperData->getSellerIdByProductId($productId);
        $flShipStoreId = $this->flagshipStoreSalesHelper->getFlagshipStoreFromSellerId($sellerId);
        if ((int)$flShipStoreId) {
            $this->flagShipSoreId = (int)$flShipStoreId;
            $result = true;
        }

        return $result;
    }

    /**
     * Get Min Level
     *
     * @return int
     */
    public function getMinLevel()
    {
        return $this->minLevel;
    }

    /**
     * Get Category by ID
     *
     * @param int $categoryId
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategorybyID($categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
            $path = explode('/', $category->getPath());
            if (isset($path[3])) {
                return $this->categoryRepository->get($path[3], $this->storeManager->getStore()->getId());
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return null;
    }

    /**
     * Purify HTML
     *
     * @param string $html
     * @return string
     */
    public function purify($html)
    {
        return $this->purifier->purify($html);
    }

    /**
     * Check if the product is tracked.
     *
     * @param int $productId
     *
     * @return bool
     */
    public function isProductCopyTracked(int $productId): bool
    {
        return $this->productCopyTracking->isTracked($productId);
    }

    /**
     * Get salable quantity data of product by sku
     *
     * @param Product $object
     * @return string
     * @throws LocalizedException
     */
    public function getSalableQty($object)
    {
        if ($object->getSku()) {
            try {
                $salableQty = $this->getSalableQuantityDataBySku->execute($object->getSku());
                if (isset($salableQty[0]['qty'])) {
                    return (string)$salableQty[0]['qty'];
                }
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getValidateImageTagsConfig()
    {
        return [
            'enableValidateImageTags' => (bool)$this->configData->enableValidateImageTags(),
            'imageTagValidateNeedToConfig' => $this->configData->getImageTagsNeedToValidate()
        ];
    }

    public function showCommissionSource($sourceVal){
        $sourceVal = (int)$sourceVal();
        $allSources = $this->commissionSource->toOptionArray();
        $commissionSource = isset($allSources[$sourceVal]) ? $allSources[$sourceVal] : 'N/A';

        return $commissionSource;
    }

    public function getCommissionSourceTxt($sourceVal, $sellerComissionRate){
        $activeContract = $sellerComissionRate['active_contract'];
        $commissionSourceTxt = '';
        if($sourceVal == 2 && $activeContract){
            $commissionSourceTxt = __('Based on Active Period: %1 to %2', $activeContract['from'], $activeContract['to']);
        }else if($sourceVal == 3){
            $commissionSourceTxt = __('Based on Default Settings');
        }else if($sourceVal == 1){
            $commissionSourceTxt = __('Manually Input');
        }
        return $commissionSourceTxt;


        $allSources = $this->commissionSource->toOptionArray();
        if(isset($allSources[$sourceVal])){
            return $allSources[$sourceVal];
        }
        return __('N/A');
    }

    /**
     * @return array
     */
    public function getMediaUploadConfig()
    {
        return $this->mediaGaleryUploaderConfig->getMediaUploadConfig();
    }

    /**
     * Get ticket stock by batch code (comb)
     * Delegated to specific synchronizer providers.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string|null $batchCode
     * @return int
     */
    public function countSerialNumbersByBatchCode($product, $batchCode = null)
    {
        $typeId = $product->getData('virtual_product_type');
        $synchronizer = $this->synchronizerPool->get($typeId);
        
        if ($synchronizer) {
            return $synchronizer->getAvailableCount($product, $batchCode);
        }
        
        return 0;
    }

    /**
     * Centralized logic to synchronize variations for ticket products.
     * Uses the Strategy pattern via SynchronizerPool.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    /**
     * Get variation combinations for a product.
     * Logic extracted from Webkul OSI.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getVariationCombination($product)
    {
        $combinations = [];
        try {
            $options = $product->getOptions();
            if (!$options) {
                return $combinations;
            }

            foreach ($options as $option) {
                if ($option->getTitle() === \Branch8\HotaiCore\Helper\VirtualProduct::TICKET_BATCH_SETTING_OPTION_TITLE) {
                    $values = $option->getValues();
                    foreach ($values as $value) {
                        $combinations[$value->getTitle()] = [
                            'sku' => $value->getSku(),
                            'option_id' => $option->getId(),
                            'option_value_id' => $value->getId()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error
        }
        return $combinations;
    }

    /**
     * Update variation data directly in DB to avoid overhead of loading objects.
     *
     * @param int $productRowId
     * @param string $comb
     * @param int $stock
     * @return void
     */
    public function updateVariationDataDirectly($productRowId, $comb, $stock, array $additionalData = [])
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('wk_osi_variations');
            
            // Check if variation already exists
            $select = $connection->select()
                ->from($tableName, ['entity_id'])
                ->where('product_id = ?', $productRowId)
                ->where('comb = ?', $comb);
                
            $entityId = $connection->fetchOne($select);
            
            if ($entityId) {
                // Update existing variation
                $connection->update(
                    $tableName,
                    ['stock' => $stock],
                    ['entity_id = ?' => $entityId]
                );
            } else {
                // Prepare insert data
                // Case 1: Clone an existing variation for this product to preserve templates/settings
                $templateSelect = $connection->select()
                    ->from($tableName)
                    ->where('product_id = ?', $productRowId)
                    ->limit(1);
                    
                $templateData = $connection->fetchRow($templateSelect);
                
                if ($templateData) {
                    $insertData = $templateData;
                    unset($insertData['entity_id']);
                    $insertData['comb']  = $comb;
                    $insertData['stock'] = $stock;
                    $insertData['sku']   = $additionalData['sku'] ?? $insertData['sku'];
                } else {
                    // Case 2: New variation completely
                    $insertData = [
                        'product_id'     => $productRowId,
                        'mageproduct_id' => $additionalData['mageproduct_id'] ?? $productRowId,
                        'comb'           => $comb,
                        'stock'          => $stock,
                        'image'          => $additionalData['image'] ?? '',
                        'sku'            => $additionalData['sku'] ?? '',
                        'price'          => $additionalData['price'] ?? 0,
                        'cost'           => $additionalData['cost'] ?? 0,
                        'cost_setting'   => $additionalData['cost_setting'] ?? 0,
                        'commission_percent' => $additionalData['commission_percent'] ?? 0,
                        'follow_simple_sku_price_setting' => 1,
                        'follow_simple_sku_cost_setting'  => 1,
                    ];
                }

                $connection->insert($tableName, $insertData);
            }
        } catch (\Exception $e) {
            // Log error
        }
    }

    public function syncTicketVariations($product)
    {
        $typeId = $product->getData('virtual_product_type');
        $synchronizer = $this->synchronizerPool->get($typeId);
        
        if ($synchronizer) {
            $synchronizer->sync($product);
        }
    }
}
