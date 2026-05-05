<?php

namespace Branch8\Catalog\Helper;

use Branch8\FlagshipStore\Helper\Sales as FlagshipSales;
use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Branch8\PointMoneyConfig\Helper\Common;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\AdvancedRule\Model\Condition\Filter as FilterModel;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute as AttributeCondition;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as SalesRuleCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as FilterResource;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\FilterFactory as FilterResourceFactory;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\SaleperpartnerFactory;
use Branch8\PointMoneyCollect\Helper\Data as PointHelper;
use Webkul\MpApi\Api\SellerManagementInterface;

class Data extends AbstractHelper
{
    /** @var VirtualProduct */
    protected $hotaiCoreHelper;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;
    /**
     * @var SaleperpartnerFactory
     */
    protected $mpSalesPartner;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var SalesRuleCollectionFactory
     */
    private SalesRuleCollectionFactory $salesRuleCollectionFactory;

    /**
     * @var FilterResourceFactory
     */
    protected $filterResourceFactory;

    /**
     * @var FormatInterface
     */
    private FormatInterface $localeFormat;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PointHelper
     */
    protected $pointHelper;

    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected PriceCurrencyInterface $priceCurrency;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var SellerManagementInterface
     */
    protected SellerManagementInterface $sellerManagement;

    /**
     * @var FlagshipSales
     */
    protected FlagshipSales $flagshipSalesHelper;

    protected $cacheSellerId = [];
    protected $cacheSellerCode = [];
    protected $cacheFlagshipStore = [];

    /**
     * @param VirtualProduct $hotaiCoreHelper
     * @param MarketplaceHelper $marketplaceHelper
     * @param ManagerInterface $messageManager
     * @param SaleperpartnerFactory $saleperPartnerFactory
     * @param SalesRuleCollectionFactory $salesRuleCollectionFactory
     * @param FilterResourceFactory $filterResourceFactory
     * @param Config $config
     * @param FormatInterface $localeFormat
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param PointHelper $pointHelper
     * @param HotaiPointHelper $hotaiPointHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param SellerManagementInterface $sellerManagement
     * @param FlagshipSales $flagshipSalesHelper
     * @param Context $context
     */
    public function __construct(
        VirtualProduct                $hotaiCoreHelper,
        MarketplaceHelper             $marketplaceHelper,
        ManagerInterface              $messageManager,
        SaleperpartnerFactory         $saleperPartnerFactory,
        SalesRuleCollectionFactory    $salesRuleCollectionFactory,
        FilterResourceFactory         $filterResourceFactory,
        Config                        $config,
        FormatInterface               $localeFormat,
        ProductRepositoryInterface    $productRepository,
        ResourceConnection            $resourceConnection,
        StoreManagerInterface         $storeManager,
        PointHelper                   $pointHelper,
        HotaiPointHelper              $hotaiPointHelper,
        PriceCurrencyInterface        $priceCurrency,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        CategoryCollectionFactory     $categoryCollectionFactory,
        SellerManagementInterface     $sellerManagement,
        FlagshipSales                 $flagshipSalesHelper,
        Context                       $context
    )
    {
        $this->hotaiCoreHelper = $hotaiCoreHelper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->mpSalesPartner = $saleperPartnerFactory;
        $this->messageManager = $messageManager;
        $this->salesRuleCollectionFactory = $salesRuleCollectionFactory;
        $this->filterResourceFactory = $filterResourceFactory;
        $this->config = $config;
        $this->localeFormat = $localeFormat;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->pointHelper = $pointHelper;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->priceCurrency = $priceCurrency;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->sellerManagement = $sellerManagement;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
        parent::__construct($context);
    }


    public function warningCommmisionRate($productData)
    {

        if (in_array($productData['type_id'],
            [\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE, \Magento\Bundle\Model\Product\Type::TYPE_CODE,
                \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE, \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD])
        ) {
            return;
        }

        if (!isset($productData['seller_id'])) {
            $sellerId = $this->marketplaceHelper->getSellerIdByProductId($productData['entity_id']);
        } else {
            $sellerId = $productData['seller_id'];
        }
        $partner = $this->mpSalesPartner->create()
            ->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('min_commission_rate')
            ->addFieldToSelect('commission_rate')
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            )->getFirstItem();
        if (!isset($productData['cost'])) $productData['cost'] = 0;
        if (isset($productData['special_price']) && $productData['special_price'] > 0) {
            $commission = ((float)$productData['special_price'] - (float)$productData['cost']) / (float)$productData['special_price'] * 100;
        } else {
            if ((float)$productData['price'] == 0) {
                $commission = 1;
                $message = __('The price is equal to 0.');
                $this->messageManager->addWarningMessage($message);
            } else {
                $commission = ((float)$productData['price'] - (float)$productData['cost']) / (float)$productData['price'] * 100;
            }
        }
        $message = '';
        if ($commission < (float)$partner->getCommissionRate()) {
            $message = __('It\'s below the average commission.');
        }
        if ($commission < (float)$partner->getMinCommissionRate()) {
            $message = __('It\'s below the contract minimum commission.');
        }
        if ($message != '') {
            $this->messageManager->addWarningMessage($message);
        }
    }

    /**
     * Get product by sku
     * @param $productSku
     * @return string|ProductInterface
     */
    public function getProductBySku($productSku): string|ProductInterface
    {
        $product = '';
        if (!$productSku) {
            return $product;
        }
        try {
            $product = $this->productRepository->get($productSku);
        } catch (NoSuchEntityException $e) {
            $this->_logger->critical($e);
        }
        return $product;
    }

    /**
     * Get all promotion by product id
     * @param $product
     * @return array
     */
    public function getAllPromotionByProduct($product): array
    {
        $activeRules = [];

        try {
            $ruleIds = $this->getFilteredRuleIds($product);
            list($notRuleIds, $containRuleIds) = $this->getNotFilteredRuleIds($product, $ruleIds);
            $rules = $this->salesRuleCollectionFactory->create();
            $rules->addFieldToFilter('is_active', 1);
            $rules->addFieldToFilter('display_on_frontend', 1);
            $rules->addFieldToFilter('rule_id', ['in' => $containRuleIds]);
            if (!empty($notRuleIds)) {
                $rules->addFieldToFilter('rule_id', ['nin' => $notRuleIds]);
            }
            $activeRules = $rules->getItems();
        } catch (NoSuchEntityException|LocalizedException $e) {
        }
        return $activeRules;
    }

    /**
     * Returns Rule IDs after filtering.
     *
     * @param $product
     * @return array
     * @throws LocalizedException
     */
    protected function getFilteredRuleIds($product)
    {
        /** @var \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel */
        $filterResourceModel = $this->filterResourceFactory->create();
        $prefix = 'product:attribute:sku';
        $filterTextGenerators = $this->getFilterTextGenerators($filterResourceModel);
        $filterCriteria = [['true']];
        $findInset = [];
        foreach ($filterTextGenerators as $filterGeneratorData) {
            $filterText = $filterGeneratorData['filter_text'];
            $filterSkus = (string)$filterGeneratorData['filter_skus'];
            $filterTextGeneratorArguments = $filterGeneratorData[Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS];
            $filterTextGeneratorArguments = json_decode($filterTextGeneratorArguments, true);
            $filterGeneratorClass = $filterGeneratorData['filter_text_generator_class'];
            if (str_contains($filterText, $prefix) && is_string($filterSkus) && ($skus = explode(',', $filterSkus))) {
                $findInset[] = new \Zend_Db_Expr('FIND_IN_SET(\'' . trim($product->getSku()) . '\',filter_skus)');
            } elseif (isset($filterTextGeneratorArguments['attribute'])) {
                $filterCriteria[] = $this->generateFilterTextAttribute($product, $filterTextGeneratorArguments['attribute']);
            } else {
                $filterCriteria[] = $this->generateFilterTextCategory($product);
            }
        }
        $filterCriteria = array_merge([], ...$filterCriteria);
        $filterCriteria = array_unique($filterCriteria);
        return $this->filterRules($filterResourceModel, $filterCriteria, $findInset);
    }

    /**
     * Returns Rule IDs after filtering.
     *
     * @param $product
     * @param $ruleIds
     * @return array
     * @throws LocalizedException
     */
    protected function getNotFilteredRuleIds($product, $ruleIds)
    {
        /** @var \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel */
        $filterResourceModel = $this->filterResourceFactory->create();
        $filterTextGenerators = $this->getNotFilterTextGenerators($filterResourceModel);
        $filterCriteria = [];
        $filterNotContain = [];
        $sellerCode = '';
        $flagshipStoreId = '';
        foreach ($filterTextGenerators as $filterGeneratorData) {
            if ($filterGeneratorData == 'category_ids') {
                $categoryIds = $product->getCategoryIds();
                $categoryNames = $this->categoryCollectionFactory->create()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('entity_id', ['in' => $categoryIds])
                    ->getColumnValues('name');
                foreach ($categoryIds as $categoryId) {
                    $text = $filterGeneratorData . ':' . $categoryId;
                    if (!in_array($text, $filterCriteria)) {
                        $filterCriteria[] = $text;
                        $filterNotContain[][$filterGeneratorData] = trim($categoryId);
                    }
                }
                if (!empty($categoryNames)) {
                    foreach ($categoryNames as $categoryName) {
                        $text = $filterGeneratorData . ':' . $categoryName;
                        if (!in_array($text, $filterCriteria)) {
                            $filterCriteria[] = $text;
                            $filterNotContain[][$filterGeneratorData] = $categoryName;
                        }
                    }
                }
            } elseif ($filterGeneratorData == 'sku') {
                $sku = trim($product->getSku());
                $text = $filterGeneratorData . ':' . $sku;
                if (!in_array($text, $filterCriteria)) {
                    $filterCriteria[] = $text;
                    $filterNotContain[][$filterGeneratorData] = $sku;
                }
            } elseif ($filterGeneratorData == 'seller') {
                if (isset($this->cacheSellerCode[$product->getId()])) {
                    $sellerCode = $this->cacheSellerCode[$product->getId()];
                }
                if (!$sellerCode) {
                    $sellerId = $this->cacheSellerId[$product->getId()] = $this->cacheSellerId[$product->getId()] ?? $this->marketplaceHelper->getSellerIdByProductId($product->getId());
                    if (!$sellerId) {
                        continue;
                    }
                    $sellers = $this->sellerManagement->getSeller($sellerId);
                    if ($sellers->getTotalCount() > 0) {
                        $seller = $sellers->getItems()[0];
                        $sellerCode = $seller['seller_code'] ?? '';
                    }
                    $this->cacheSellerCode[$product->getId()] = $sellerCode;
                }
                if ($sellerCode) {
                    $text = $filterGeneratorData . ':' . $sellerCode;
                    if (!in_array($text, $filterCriteria)) {
                        $filterCriteria[] = $text;
                        $filterNotContain[][$filterGeneratorData] = $sellerCode;
                    }
                }
            } elseif ($filterGeneratorData == 'flagship_store') {
                if (isset($this->cacheFlagshipStore[$product->getId()])) {
                    $flagshipStoreId = $this->cacheFlagshipStore[$product->getId()];
                }
                if (!$flagshipStoreId) {
                    $sellerId = $this->cacheSellerId[$product->getId()] = $this->cacheSellerId[$product->getId()] ?? $this->marketplaceHelper->getSellerIdByProductId($product->getId());
                    if (!$sellerId) {
                        continue;
                    }
                    $flagshipStoreId = $this->cacheFlagshipStore[$product->getId()] = $this->flagshipSalesHelper->getFlagshipStoreFromSellerId($sellerId);
                }
                if ($flagshipStoreId) {
                    $text = $filterGeneratorData . ':' . $flagshipStoreId;
                    if (!in_array($text, $filterCriteria)) {
                        $filterCriteria[] = $text;
                        $filterNotContain[][$filterGeneratorData] = $flagshipStoreId;
                    }
                }
            } else {
                $text = $filterGeneratorData . ':' . $product->getData($filterGeneratorData);
                if (!in_array($text, $filterCriteria)) {
                    $filterCriteria[] = $text;
                    $filterNotContain[][$filterGeneratorData] = $product->getData($filterGeneratorData);
                }
            }
        }
        $filterCriteria = array_unique($filterCriteria);

        $connection = $filterResourceModel->getConnection();
        $select = $connection->select()->from(
            'branch8_salesrule_isnot_filter',
            ['rule_id']
        )->where(
            $connection->quoteInto(
                FilterModel::KEY_FILTER_TEXT . ' IN (?) AND is_one_of = 0',
                $filterCriteria
            )
        )->group('rule_id');
        $ruleDiffIds = $ruleIds;
        if ($filterNotContain) {
            $selectCRules = $connection->select()->from(
                'branch8_salesrule_isnot_filter',
                ['rule_id']
            )->where(
                $connection->quoteInto(
                    'rule_id IN (?) AND is_contain = 1 AND flag_contain = 1',
                    $ruleIds
                )
            )->group('rule_id');
            $resultsContainRule = $connection->fetchAssoc($selectCRules);
            $containRuleIds = array_keys($resultsContainRule);
            $selectContain = null;
            foreach ($filterNotContain as $filter) {
                foreach ($filter as $attribute => $value) {
                    if ($attribute == 'category_ids') {
                        if (is_numeric($value)) {
                            $select->orWhere(
                                $connection->quoteInto('attribute = ? AND ', $attribute) . FilterModel::KEY_FILTER_TEXT . $connection->quoteInto(" = ? AND is_contain = 1 AND flag_contain = 0", trim($value))
                            );
                            if (!$selectContain) {
                                $selectContain = $connection->select()->from(
                                    'branch8_salesrule_isnot_filter',
                                    ['rule_id']
                                )->where(
                                    $connection->quoteInto('attribute = ? AND ', $attribute) . FilterModel::KEY_FILTER_TEXT . $connection->quoteInto(" = ? AND is_contain = 1 AND flag_contain = 1", trim($value))
                                )->group('rule_id');
                            } else {
                                $selectContain->orWhere(
                                    $connection->quoteInto('attribute = ? AND ', $attribute) . FilterModel::KEY_FILTER_TEXT . $connection->quoteInto(" = ? AND is_contain = 1 AND flag_contain = 1", trim($value))
                                );
                            }
                        } else {
                            $select->orWhere(
                                $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 0"
                            );
                            if (!$selectContain) {
                                $selectContain = $connection->select()->from(
                                    'branch8_salesrule_isnot_filter',
                                    ['rule_id']
                                )->where(
                                    $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 1"
                                )->group('rule_id');
                            } else {
                                $selectContain->orWhere(
                                    $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 1"
                                );
                            }
                        }
                    } else {
                        $select->orWhere(
                            $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 0"
                        );
                        if (!$selectContain) {
                            $selectContain = $connection->select()->from(
                                'branch8_salesrule_isnot_filter',
                                ['rule_id']
                            )->where(
                                $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 1"
                            )->group('rule_id');
                        } else {
                            $selectContain->orWhere(
                                $connection->quoteInto('attribute = ? AND ', $attribute) . $connection->quoteInto(" ? LIKE CONCAT('%', ", $value) . FilterModel::KEY_FILTER_TEXT . ", '%') AND is_contain = 1 AND flag_contain = 1"
                            );
                        }
                    }
                }
            }
            $selectIOO = $connection->select()->from(
                'branch8_salesrule_isnot_filter',
                ['rule_id']
            )->where(
                $connection->quoteInto(
                    'rule_id IN (?) AND is_one_of = 1',
                    $ruleIds
                )
            )->group('rule_id');
            $resultsIOO = $connection->fetchAssoc($selectIOO);
            $iOORuleIds = array_keys($resultsIOO);
            $selectIOOFilter = $connection->select()->from(
                'branch8_salesrule_isnot_filter',
                ['rule_id']
            )->where(
                $connection->quoteInto(
                    FilterModel::KEY_FILTER_TEXT . ' IN (?) AND is_one_of = 1',
                    $filterCriteria
                )
            )->group('rule_id');
            $resultsIOOFilter = $connection->fetchAssoc($selectIOOFilter);
            $iOORuleFilterIds = array_keys($resultsIOOFilter);
            $ruleDiffIOOIds = array_diff($iOORuleIds, $iOORuleFilterIds);
            $resultsContain = $selectContain ? $connection->fetchAssoc($selectContain) : [];
            $ruleContainIds = array_keys($resultsContain);
            $ruleDiffIds = array_diff($containRuleIds, $ruleContainIds);
            $ruleDiffIds = array_diff($ruleIds, $ruleDiffIds, $ruleDiffIOOIds);
            $ruleDiffIds = array_merge($ruleDiffIds, $ruleContainIds, $iOORuleFilterIds);
        }

        $results = $connection->fetchAssoc($select);
        return [array_keys($results), $ruleDiffIds];
    }

    /**
     * @param FilterResource $filterResourceModel
     * @return mixed
     */
    public function getFilterTextGenerators(FilterResource $filterResourceModel)
    {
        $connection = $filterResourceModel->getConnection();
        $select = $connection->select()->from(
            'magento_salesrule_filter',
            [
                'filter_text',
                'filter_skus',
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS

            ]
        )->where(
            FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS . ' IS NOT NULL'
        )->group(
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS
            ]
        );
        return $connection->fetchAll($select);
    }

    /**
     * @param FilterResource $filterResourceModel
     * @return mixed
     */
    public function getNotFilterTextGenerators(FilterResource $filterResourceModel)
    {
        $connection = $filterResourceModel->getConnection();
        $select = $connection->select()->from(
            'branch8_salesrule_isnot_filter',
            [
                'attribute'
            ]
        )->group('attribute');
        $results = $connection->fetchAssoc($select);
        return array_keys($results);
    }

    /**
     * @param $product
     * @param $attributeCode
     * @param $filterGenerateClass
     * @return array
     * @throws LocalizedException
     */
    protected function generateFilterTextAttribute($product, $attributeCode)
    {
        $filterText = [];
        $sellerCode = '';
        $flagshipStoreId = '';
        if ($attributeCode == 'seller') {
            if (isset($this->cacheSellerCode[$product->getId()])) {
                $sellerCode = $this->cacheSellerCode[$product->getId()];
            }
            if (!$sellerCode) {
                $sellerId = $this->cacheSellerId[$product->getId()] = $this->cacheSellerId[$product->getId()] ?? $this->marketplaceHelper->getSellerIdByProductId($product->getId());
                if (!$sellerId) {
                    return $filterText;
                }
                $sellers = $this->sellerManagement->getSeller($sellerId);
                if ($sellers->getTotalCount() > 0) {
                    $seller = $sellers->getItems()[0];
                    $sellerCode = $seller['seller_code'] ?? '';
                }
                $this->cacheSellerCode[$product->getId()] = $sellerCode;
            }
            if ($sellerCode) {
                $text = AttributeCondition::FILTER_TEXT_PREFIX . $attributeCode . ':' . $sellerCode;
                if (!in_array($text, $filterText)) {
                    $filterText[] = $text;
                }
            }
        } elseif ($attributeCode == 'flagship_store') {
            if (isset($this->cacheFlagshipStore[$product->getId()])) {
                $flagshipStoreId = $this->cacheFlagshipStore[$product->getId()];
            }
            if (!$flagshipStoreId) {
                $sellerId = $this->cacheSellerId[$product->getId()] = $this->cacheSellerId[$product->getId()] ?? $this->marketplaceHelper->getSellerIdByProductId($product->getId());
                if (!$sellerId) {
                    return $filterText;
                }
                $flagshipStoreId = $this->cacheFlagshipStore[$product->getId()] = $this->flagshipSalesHelper->getFlagshipStoreFromSellerId($sellerId);
            }
            if ($flagshipStoreId) {
                $text = AttributeCondition::FILTER_TEXT_PREFIX . $attributeCode . ':' . $flagshipStoreId;
                if (!in_array($text, $filterText)) {
                    $filterText[] = $text;
                }
            }
        } else {
            $attribute = $this->config->getAttribute(Product::ENTITY, $attributeCode);
            $value = $product->getData($attributeCode);
            if ($attribute && $attribute->getBackendType() === 'decimal') {
                $value = $this->localeFormat->getNumber($value);
            }
            if (is_scalar($value)) {
                $text = AttributeCondition::FILTER_TEXT_PREFIX . $attributeCode . ':' . $value;
                if (!in_array($text, $filterText)) {
                    $filterText[] = $text;
                }
            }
        }
        return $filterText;
    }

    /**
     * @param $product
     * @return string[]
     */
    protected function generateFilterTextCategory($product)
    {
        $filterText = [];
        $categoryIds = $product->getCategoryIds();
        foreach ($categoryIds as $categoryId) {
            $text = Categories::FILTER_TEXT_PREFIX . $categoryId;
            if (!in_array($text, $filterText)) {
                $filterText[] = $text;
            }
        }
        return $filterText;
    }

    /**
     * @param FilterResource $collection
     * @param array $filterText
     * @param array $findInset
     * @return int[]|string[]
     * @throws LocalizedException
     */
    private function filterRules(FilterResource $collection, array $filterText, array $findInset)
    {
        $connection = $collection->getConnection();
        $select = $connection->select()->from(
            $collection->getMainTable(),
            ['rule_id']
        )->where(
            $connection->quoteInto(
                FilterModel::KEY_FILTER_TEXT . ' IN (?) ',
                $filterText
            )
        )->group(
            ['group_id', 'rule_id']
        )->having(
            'sum(weight) > 0'
        );
        if ($findInset) {
            foreach ($findInset as $filterSku) {
                $select->orWhere($filterSku);
            }
        }
        $results = $connection->fetchAssoc($select);
        return array_keys($results);
    }

    /**
     * Get qty ordered
     * @param $productId
     * @return mixed
     */
    public function getQtyOrdered($productId)
    {
        try {
            return $this->getProductSold($productId);
        } catch (Exception $e) {
        }
        return 0;
    }

    /**
     * Get point of product
     * @param $price
     * @param $pointType
     * @param $pointProductPoint
     * @param $pointLowerType
     * @param $pointLowerValue
     * @param $pointUpperType
     * @param $pointUpperValue
     * @return Phrase|string
     */
    public function getPointOfProduct($price, $pointType, $pointProductPoint, $pointLowerType, $pointLowerValue, $pointUpperType, $pointUpperValue)
    {
        $productPrice = $price;
        $pointIcon = $this->hotaiPointHelper->getPointImage(null, 16);
        $isUserLogin = $this->b8CustomerHelper->isLoggedInAndIsBuyer();
        if ($isUserLogin) {
            $userPoint = $this->hotaiPointHelper->getHotaiPoint();
        } else {
            $userPoint = 0;
        }
        $pointText = '';
        if (in_array($pointType, Common::TYPES_ONLY_PRODUCT_POINT)) {
            $pointText = __("Points %1 only, full discount", $pointIcon);
        } elseif (in_array($pointType, Common::TYPES_REQUIRE_PRODUCT_POINT)) {
            $pointRequiredUnit = $pointProductPoint;
            $pointRequiredUnit = floor($pointRequiredUnit);
            if ($isUserLogin) {
                if ($userPoint < $pointRequiredUnit) {
                    $pointText = __("Points %1 discount is not applicable", $pointIcon);
                } else {
                    $price = $productPrice - ($pointRequiredUnit * $this->pointHelper->getPointConvertRate());
                    $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                    $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointRequiredUnit . '</span></span>';
                    $pointText = __("Discount up to %1 , price after discount is %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
                }
            } else {
                $price = $productPrice - ($pointRequiredUnit * $this->pointHelper->getPointConvertRate());
                $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointRequiredUnit . '</span></span>';
                $pointText = __("Maximum deduction %1 , final price %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
            }
        } elseif (in_array($pointType, Common::TYPES_ALLOW_FREE_RATIO_REDEEM_SETTING)) {
            if ($pointType == '5') {
                $productPoint = $productPrice / $this->pointHelper->getPointConvertRate();
                $productPoint = floor($productPoint);
                $price = 0;
                $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $productPoint . '</span></span>';
                $pointText = __("Maximum deduction %1 , final price %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
            } else {
                if ($pointLowerType == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                    $pointRequiredUnit = $productPrice * ((float)$pointLowerValue / 100) / (float)$this->pointHelper->getPointConvertRate();
                } else {
                    $pointRequiredUnit = (int)$pointLowerValue;
                }
                if ($pointUpperType == PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE) {
                    $pointMaxUnit = $productPrice * ((float)$pointUpperValue / 100) / (float)$this->pointHelper->getPointConvertRate();
                } else {
                    $pointMaxUnit = (int)$pointUpperValue;
                }
                $pointRequiredUnit = floor($pointRequiredUnit);
                $pointMaxUnit = floor($pointMaxUnit);
                if ($isUserLogin) {
                    if ($userPoint < $pointRequiredUnit) {
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointRequiredUnit . '</span></span>';
                        $pointText = __("A minimum of %1 points is required to purchase this product.", $hotaiPoints);
                    } elseif ($userPoint >= $pointMaxUnit && $pointMaxUnit > 0) {
                        $price = $productPrice - ($pointMaxUnit * $this->pointHelper->getPointConvertRate());
                        $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointMaxUnit . '</span></span>';
                        $pointText = __("Discount up to %1 , price after discount is %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
                    } else {
                        $price = $productPrice - ($userPoint * $this->pointHelper->getPointConvertRate());
                        if ($price < 0) {
                            $price = 0;
                            $userPoint = $productPrice / $this->pointHelper->getPointConvertRate();
                        }
                        $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $userPoint . '</span></span>';
                        $pointText = __("Discount up to %1 , price after discount is %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
                    }
                } else {
                    if ($pointMaxUnit > 0) {
                        $price = $productPrice - ($pointMaxUnit * $this->pointHelper->getPointConvertRate());
                        $priceFormat = $this->priceCurrency->convertAndFormat($price, false, 2);
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointMaxUnit . '</span></span>';
                        $pointText = __("Maximum deduction %1 , final price %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
                    } else if ($pointRequiredUnit > 0) {
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $pointRequiredUnit . '</span></span>';
                        $pointText = __("A minimum of %1 points is required to purchase this product.", $hotaiPoints);
                    } else {
                        $point = $productPrice / $this->pointHelper->getPointConvertRate();
                        $priceFormat = $this->priceCurrency->convertAndFormat(0, false, 2);
                        $hotaiPoints = '<span class="hotai-customer-points">' . $pointIcon . '<span>' . $point . '</span></span>';
                        $pointText = __("Maximum deduction %1 , final price %2", $hotaiPoints, '<span class="price">' . $priceFormat . '</span>');
                    }
                }
            }
        } else {
            /**
             * Money only, do no things
             */
            $pointText = __("Points %1 discount is not applicable", $pointIcon);
        }

        return $pointText;
    }

    /**
     *
     * @return array
     */
    private function getProductSold($productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $storeId = $this->storeManager->getStore()->getId();
        $tblBestSeller = $connection->getTableName('sales_bestsellers_aggregated_daily');
        $qtyOrdered = $connection->fetchOne("
SELECT SUM(sbd.qty_ordered) AS `qty_ordered`
FROM `{$tblBestSeller}` as sbd
WHERE sbd.store_id = '{$storeId}'
  AND sbd.rating_pos <= 5
  AND sbd.product_id = '{$productId}'
GROUP BY sbd.product_id");

        return $qtyOrdered;
    }

    public function isBatchImportTicketProduct(int|string $productId): bool
    {
        $this->hotaiCoreHelper->isBatchImportTicketProduct($productId);
    }

    public function getTicketAvailableBatchData(int|string $productId): array
    {
        return $this->hotaiCoreHelper->getTicketAvailableBatchData($productId);
    }
}
