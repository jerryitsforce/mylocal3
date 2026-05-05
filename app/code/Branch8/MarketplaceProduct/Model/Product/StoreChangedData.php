<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ProductVersionFactory;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceStaging\Helper\Variation;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification\CollectionFactory as PCCollectionFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Copier;
use Magento\Catalog\Model\ProductLink\Link;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\CustomerFactory;
use Magento\Downloadable\Helper\Download;
use Magento\Downloadable\Model\Product\Type;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Framework\Event\Manager;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\User\Model\UserFactory;
use Webkul\Marketplace\Controller\Product\Builder;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Model\Product;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Branch8\MarketplaceStaging\Api\ProductVersionDataRepositoryInterface;
use Branch8\MarketplaceStaging\Model\ProductVersionDataFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Webkul\Marketplace\Model\ProductFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as MpProductCollection;
use Webkul\MarketplacePreorder\Helper\Data;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;

class StoreChangedData
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var GetChangedData
     */
    private GetChangedData $getChangedProductData;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductVersionFactory
     */
    private ProductVersionFactory $productVersionFactory;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var SaveProductWithChanges
     */
    private SaveProductWithChanges $saveProductWithChanges;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var ProductVersionDataFactory
     */
    private ProductVersionDataFactory $productVersionDataFactory;

    /**
     * @var ProductVersionDataRepositoryInterface
     */
    private ProductVersionDataRepositoryInterface $productVersionDataRepository;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $_eventManager;

    /**
     * @var Builder
     */
    protected $_marketplaceProductBuilder;

    /**
     * @var MarketplaceHelperData
     */
    protected $_marketplaceHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Copier
     */
    protected $productCopier;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $_mpProductFactory;
    /**
     * @var MpProductCollection
     */
    protected $_mpProductCollectionFactory;
    /**
     * @var StockRegistryProviderInterface
     */
    protected $_stockRegistryProvider;
    /**
     * @var StockConfigurationInterface
     */
    protected $_stockConfiguration;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var StagingLocator
     */
    private $stagingLocator;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var CategoryFactory
     */
    protected $categoryModel;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    protected BuildVariation $buildVariation;

    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $preorderHelper;

    /**
     * @var \Branch8\MarketplaceStaging\Helper\Variation
     */
    protected $variationaHelper;

    /**
     * @var ProductCopyTracking
     */
    private ProductCopyTracking $productCopyTracking;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var PCCollectionFactory
     */
    private PCCollectionFactory $pcCollectionFactory;

    /**
     * @var array
     */
    public static array $arrayDateAttribute = [
        'limit_purchased_start_time',
        'limit_purchased_end_time',
        'preorder_start_date',
        'preorder_end_date',
        'preorder_ship_date',
        'wk_marketplace_availability',
    ];

    protected $b8CustomerHelper;

    protected $_conn;

    protected $productApproval;

    /**
     * StoreChangedData constructor.
     *
     * @param SerializerInterface $serializer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetChangedData $getChangedProductData
     * @param ProductRepositoryInterface $productRepository
     * @param ProductVersionFactory $productVersionFactory
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param SaveProductWithChanges $saveProductWithChanges
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionDataFactory $productVersionDataFactory
     * @param ProductVersionDataRepositoryInterface $productVersionDataRepository
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param Manager $eventManager
     * @param Builder $marketplaceProductBuilder
     * @param MarketplaceHelperData $marketplaceHelperData
     * @param Copier $productCopier
     * @param ProductFactory $mpProductFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param StockConfigurationInterface $stockConfiguration
     * @param ManagerInterface $messageManager
     * @param DateTime $date
     * @param StagingLocator $stagingLocator
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param BuildVariation $buildVariation
     * @param Data $preorderHelper
     * @param Variation $variationaHelper
     * @param ProductCopyTracking $productCopyTracking
     * @param AuthSession $authSession
     * @param SellerSubAccountHelper $sellerSubAccountHelper
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param \Magento\Framework\App\ResourceConnection   $resourceConnection
     * @param \Branch8\MarketplaceProduct\Helper\ProductApproval $productApproval
     * @param \Magento\Catalog\Model\ProductFactory|null $productFactory
     * @param CustomerFactory|null $customerModel
     * @param CategoryFactory|null $categoryModel
     * @param MpEmailHelper|null $mpEmailHelper
     * @param UpdateRepositoryInterface|null $updateRepository
     */
    public function __construct(
        SerializerInterface                         $serializer,
        SearchCriteriaBuilder                       $searchCriteriaBuilder,
        GetChangedData                              $getChangedProductData,
        ProductRepositoryInterface                  $productRepository,
        ProductVersionFactory                       $productVersionFactory,
        ProductVersionCollectionFactory             $productVersionCollectionFactory,
        SaveProductWithChanges                      $saveProductWithChanges,
        ProductVersionRepositoryInterface           $productVersionRepository,
        ProductVersionDataFactory                   $productVersionDataFactory,
        ProductVersionDataRepositoryInterface       $productVersionDataRepository,
        MarketplaceProductManagement                $marketplaceProductManagement,
        \Magento\Framework\Event\Manager            $eventManager,
        Builder                                     $marketplaceProductBuilder,
        MarketplaceHelperData                       $marketplaceHelperData,
        \Magento\Catalog\Model\Product\Copier       $productCopier,
        \Webkul\Marketplace\Model\ProductFactory    $mpProductFactory,
        MpProductCollection                         $mpProductCollectionFactory,
        StockRegistryProviderInterface              $stockRegistryProvider,
        StockConfigurationInterface                 $stockConfiguration,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        StagingLocator                              $stagingLocator,
        ProductAttributeRepositoryInterface         $productAttributeRepository,
        BuildVariation                              $buildVariation,
        \Webkul\MarketplacePreorder\Helper\Data     $preorderHelper,
        Variation                                   $variationaHelper,
        ProductCopyTracking                         $productCopyTracking,
        UserContextInterface                        $userContext,
        UserFactory                                 $userFactory,
        AuthSession                                 $authSession,
        SellerSubAccountHelper                      $sellerSubAccountHelper,
        \Branch8\Customer\Helper\Data               $b8CustomerHelper,
        \Magento\Framework\App\ResourceConnection   $resourceConnection,
        \Branch8\MarketplaceProduct\Helper\ProductApproval $productApproval,
        \Magento\Catalog\Model\ProductFactory       $productFactory = null,
        CustomerFactory                             $customerModel = null,
        CategoryFactory                             $categoryModel = null,
        MpEmailHelper                               $mpEmailHelper = null,
        UpdateRepositoryInterface                   $updateRepository = null,
        PCCollectionFactory                         $pcCollectionFactory = null
    ) {
        $this->serializer = $serializer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getChangedProductData = $getChangedProductData;
        $this->productRepository = $productRepository;
        $this->productVersionFactory = $productVersionFactory;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->saveProductWithChanges = $saveProductWithChanges;
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionDataFactory = $productVersionDataFactory;
        $this->productVersionDataRepository = $productVersionDataRepository;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->_eventManager = $eventManager;
        $this->_marketplaceProductBuilder = $marketplaceProductBuilder;
        $this->_marketplaceHelperData = $marketplaceHelperData;
        $this->productCopier = $productCopier;
        $this->_mpProductFactory = $mpProductFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->_stockRegistryProvider = $stockRegistryProvider;
        $this->_stockConfiguration = $stockConfiguration;
        $this->_messageManager = $messageManager;
        $this->_date = $date;
        $this->stagingLocator = $stagingLocator;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->buildVariation = $buildVariation;
        $this->preorderHelper = $preorderHelper;
        $this->variationaHelper = $variationaHelper;
        $this->productCopyTracking = $productCopyTracking;
        $this->userContext = $userContext;
        $this->userFactory = $userFactory;
        $this->authSession = $authSession;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->productApproval = $productApproval;
        $this->_productFactory = $productFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Catalog\Model\ProductFactory::class);
        $this->customerModel = $customerModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerFactory::class);
        $this->categoryModel = $categoryModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CategoryFactory::class);
        $this->mpEmailHelper = $mpEmailHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpEmailHelper::class);
        $this->updateRepository = $updateRepository ?:
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(UpdateRepositoryInterface::class);
        $this->_conn = $resourceConnection->getConnection();
        $this->pcCollectionFactory = $pcCollectionFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(PCCollectionFactory::class);
    }

    /**
     * Execute to store changed product data.
     *
     * @param array $data
     * @param int $productId
     * @param int $sellerId
     * @param int $createdFrom
     * @param bool $isNew
     * @return array
     */
    public function execute(array $data, int $productId, int $sellerId, int $createdFrom = CreatedFrom::CREATED_FROM_SELLER, bool $isNew = false, bool $isNewUpdate = false): array
    {
        try {
            if ($productId > 0) {
                if ($createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE) {
                    $product = $this->stagingLocator->getProduct();
                } else {
                    $product = $this->productRepository->getById($productId);
                }
            }
            /** Set Approval first status */
            $firstApprovalStatus = ApprovalFlowStatus::CURATOR_PENDING_APPROVAL;
            if ($this->isSellerInDealer($sellerId)) {
                $firstApprovalStatus = ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL;
            }

            $editingData = $this->collectEditingData($data);
            $originalData = $this->collectOriginalData($product);
            $changedData = $this->getChangedProductData->execute($editingData, $originalData, $isNew);
            $duplicateData = $this->processDuplicateProduct($data, $sellerId);

            $saveVariation = false;
            $productRowId = $product->getRowId();
            $hasStockChange = false;
            $dataChangeCheck = $changedData;
            unset($dataChangeCheck[BuildVariation::KEY_VARIATION]);
            unset($dataChangeCheck[BuildVariation::KEY_SWATCH]);
            unset($dataChangeCheck['options']);
            unset($dataChangeCheck['stock_data']);
            $hasDataChange = !empty($dataChangeCheck);
            if (array_key_exists(BuildVariation::KEY_VARIATION, $data['product'])) {
                $isImport = is_array($data['product'][BuildVariation::KEY_VARIATION]);
                $editVariation = $this->buildVariation->buildVariation($data['product'][BuildVariation::KEY_VARIATION]);
                $origVariation = $this->buildVariation->getCurrentVariation($productRowId, $isImport);
                $diff1 = $this->getChangedProductData->recursiveArrayDiff($editVariation, $origVariation, ['product_id', 'entity_id']);
                $diff2 = $this->getChangedProductData->recursiveArrayDiff($origVariation, $editVariation, ['product_id', 'entity_id']);
                $diffStock1 = $this->getChangedProductData->recursiveArrayDiff($editVariation, $origVariation, ['product_id', 'entity_id', 'weight']);
                $diffStock2 = $this->getChangedProductData->recursiveArrayDiff($origVariation, $editVariation, ['product_id', 'entity_id', 'weight']);
                $diffWeight1 = $this->getChangedProductData->recursiveArrayDiff($editVariation, $origVariation, ['product_id', 'entity_id', 'stock']);
                $diffWeight2 = $this->getChangedProductData->recursiveArrayDiff($origVariation, $editVariation, ['product_id', 'entity_id', 'stock']);
                if (!empty($diffWeight1) || !empty($diffWeight2)) {
                    $hasDataChange = true;
                }
                if (!empty($diffStock1) || !empty($diffStock2)) {
                    $this->buildVariation->saveStock($editVariation, $productRowId);
                    $this->variationaHelper->syncNeedToRefillVariation($product->getId());
                    if (!$hasDataChange) {
                        $hasStockChange = true;
                    }
                }
                if (!empty($diff1) || !empty($diff2)) {
                    if (array_key_exists('image', $editVariation) && count($editVariation['image'])) {
                        $this->buildVariation->saveImages($editVariation['image']);
                    }
                    $saveVariation = true;
                    $changedData[BuildVariation::KEY_VARIATION] = [
                        'before' => $origVariation ?: null,
                        'after' => $editVariation
                    ];
                } elseif (!$isNew) {
                    if (empty($changedData['options'])) {
                        unset($changedData[BuildVariation::KEY_VARIATION]);
                        unset($changedData['options']);
                    }
                    unset($changedData['stock_data']);
                }
            }

            if (array_key_exists(BuildVariation::KEY_SWATCH, $data['product'])) {
                $editSwatch = $this->buildVariation->buildSwatch($data['product'][BuildVariation::KEY_SWATCH]);
                $origSwatch = $this->buildVariation->getCurrentSwatch($productRowId);
                $diff1 = $this->getChangedProductData->recursiveArrayDiff($editSwatch, $origSwatch, ['option_id', 'product_id', 'entity_id']);
                $diff2 = $this->getChangedProductData->recursiveArrayDiff($origSwatch, $editSwatch, ['option_id', 'product_id', 'entity_id']);
                if (!empty($diff1) || !empty($diff2)) {
                    $changedData[BuildVariation::KEY_SWATCH] = [
                        'before' => $origSwatch ?: null,
                        'after' => $editSwatch
                    ];
                } elseif (!$isNew) {
                    if (!$saveVariation) {
                        unset($changedData[BuildVariation::KEY_SWATCH]);
                    } else {
                        $changedData[BuildVariation::KEY_SWATCH] = [
                            'before' => $origSwatch ?: null,
                            'after' => $editSwatch
                        ];
                    }
                }
            }

            // START: Re-collect changed data for configurable product
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                if (!isset($changedData[BuildConfigurableProduct::VARIATIONS_MATRIX])) {
                    unset($changedData[BuildConfigurableProduct::ATTRIBUTES_DATA]);
                } else {
                    $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS] = [
                        'before' => $originalData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS],
                        'after' => array_filter($editingData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS])
                    ];
                }
            }
            // END: Re-collect changed data for configurable product
            $skipCheck = true;
            if ($createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE || $createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                $stagingData = $data['staging'] ?? [];
                if (!empty($stagingData['update_id'])) {
                    $update = $this->updateRepository->get($stagingData['update_id']);

                    if (empty($stagingData['start_time']) || strtotime($update->getStartTime()) != strtotime($stagingData['start_time'])) {
                        $skipCheck = false;
                    }
                    if($update && strtotime($update->getStartTime()) != strtotime($stagingData['start_time'])){
                        $data['rid'] = $data['product']['rid'];
                        $data['schedule_change_start_time'] = true;
                        unset($data['product']['rid']);
                    }

                    if ($update->getEndTime() || !empty($stagingData['end_time'])) {
                        if ((!$update->getEndTime() && !empty($stagingData['end_time']))
                            || ($update->getEndTime() && empty($stagingData['end_time']))
                            || (strtotime($update->getEndTime()) != strtotime($stagingData['end_time']))
                        ) {
                            $skipCheck = false;
                        }
                    }
                    if ($update->getName() != $stagingData['name'] || $update->getDescription() != $stagingData['description']) {
                        $skipCheck = false;
                    }
                    if (isset($stagingData['select_id']) && $stagingData['select_id'] != $stagingData['update_id']) {
                        $skipCheck = false;
                    }
                }
            }/* elseif (isset($data['attribute_selected']) && $data['attribute_selected']) {
                $changedData['attribute_selected']['before'] = 'no change';
                $changedData['attribute_selected']['after'] = $data['attribute_selected'];
            }*/

            if (empty($changedData) && $skipCheck) {
                if (isset($duplicateData['product_id'])) {
                    return ['error' => true, 'message' => __('No changes were made.'), 'product_id' => $duplicateData['product_id']];
                }
                return ['error' => true, 'message' => __('No changes were made.')];
            }
            $productVersionCheck = $this->productVersionCollectionFactory->create();
            $productVersionCheck->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', 0);
            $productVersionCheck->getSelect()->where(
                "((created_from!='" . $createdFrom . "') OR (created_from='" . $createdFrom . "' AND created_from!='" . CreatedFrom::CREATED_FROM_SCHEDULE . "' AND created_from!='" . CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED . "'))"
            );
            if ($productVersionCheck->getSize() > 0) {
                if (isset($duplicateData['product_id'])) {
                    return ['error' => true, 'message' => __('This product is reviewing. Please wait for the result.'), 'product_id' => $duplicateData['product_id']];
                }
                return ['error' => true, 'message' => __('This product is reviewing. Please wait for the result.')];
            } elseif ($createdFrom == CreatedFrom::CREATED_FROM_IMPORTED && !$isNew) {
                $productVersionCheck = $this->_mpProductFactory->create()->getCollection();
                $productVersionCheck->addFieldToFilter('mageproduct_id', $productId)
                    ->addFieldToFilter('status', 0);
                if ($productVersionCheck->getSize() > 0) {
                    if (isset($duplicateData['product_id'])) {
                        return ['error' => true, 'message' => __('This product is reviewing. Please wait for the result.'), 'product_id' => $duplicateData['product_id']];
                    }
                    return ['error' => true, 'message' => __('This product is reviewing. Please wait for the result.')];
                }
            }

            $status = '';
            if ($createdFrom != CreatedFrom::CREATED_FROM_SCHEDULE && $createdFrom != CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED && !$isNew && $hasDataChange) {
                $executorOutput = $this->saveProductWithChanges->execute($product, $sellerId, $changedData);
                $status = $executorOutput->getData('status');
                $additionalData = $executorOutput->getData('additional_data');

                if ($additionalData && is_array($additionalData)) {
                    $changedData = array_merge_recursive($changedData, $additionalData);
                }
            }
            if ((isset($changedData[BuildBundleProduct::BUNDLE_OPTIONS]) && !isset($changedData[BuildBundleProduct::BUNDLE_SELECTIONS]))
                || (!isset($changedData[BuildBundleProduct::BUNDLE_OPTIONS]) && isset($changedData[BuildBundleProduct::BUNDLE_SELECTIONS]))
            ) {
                if (isset($changedData[BuildBundleProduct::BUNDLE_OPTIONS])) {
                    if (isset($originalData[BuildBundleProduct::BUNDLE_SELECTIONS])) {
                        $changedData[BuildBundleProduct::BUNDLE_SELECTIONS]['before'] = 'no change';
                        $changedData[BuildBundleProduct::BUNDLE_SELECTIONS]['after'] = $originalData[BuildBundleProduct::BUNDLE_SELECTIONS];
                    } elseif (isset($editingData[BuildBundleProduct::BUNDLE_SELECTIONS])) {
                        $changedData[BuildBundleProduct::BUNDLE_SELECTIONS]['before'] = 'no change';
                        $changedData[BuildBundleProduct::BUNDLE_SELECTIONS]['after'] = $editingData[BuildBundleProduct::BUNDLE_SELECTIONS];
                    }
                } elseif (isset($changedData[BuildBundleProduct::BUNDLE_SELECTIONS])) {
                    if (isset($originalData[BuildBundleProduct::BUNDLE_OPTIONS])) {
                        $changedData[BuildBundleProduct::BUNDLE_OPTIONS]['before'] = 'no change';
                        $changedData[BuildBundleProduct::BUNDLE_OPTIONS]['after'] = $originalData[BuildBundleProduct::BUNDLE_OPTIONS];
                    } elseif (isset($editingData[BuildBundleProduct::BUNDLE_OPTIONS])) {
                        $changedData[BuildBundleProduct::BUNDLE_OPTIONS]['before'] = 'no change';
                        $changedData[BuildBundleProduct::BUNDLE_OPTIONS]['after'] = $editingData[BuildBundleProduct::BUNDLE_OPTIONS];
                    }
                }
            }
            if (isset($changedData[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS])) {
                $changedData[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS]['before'] = 'no change';
            }

            // START: Save a new record to the product version table
            $productVersion = $this->productVersionFactory->create();
            $productVersion->setApprovalFlowStatus($firstApprovalStatus);
            $productVersion->setProductId($productId);
            $productVersion->setProductSku($editingData['sku']);
            $productVersion->setProductName($editingData['name']);
            $productVersion->setPrice((float)($editingData['price'] ?? $product->getPrice()));
            $productVersion->setSpecialPrice((float)($editingData['special_price'] ?? $product->getData('special_price')));
            $productVersion->setCost(round((float)($editingData['cost'] ?? $product->getData('cost'))));
            $productVersion->setAdditionalInformation($this->serializer->serialize($changedData) ?: '');
            $productVersion->setCommissionPercent($editingData['commission_percent']);
            /** Check variation negative commision rate */
            $isVariationCommissionRateNegative = 0;
            if($this->productApproval->isVariantionNegative($productVersion->getData())){
                $isVariationCommissionRateNegative = 1;
            }
            $productVersion->setIsVariationCommissionRateNegative($isVariationCommissionRateNegative);
            if (isset($changedData['cost']) || isset($changedData['price'])) {
                $productVersion->setIsPriceCostChanged(1);
            } else {
                $productVersion->setIsPriceCostChanged(0);
            }

            if (isset($changedData['cost'])) {
                $productVersion->setIsCostChanged(1);
            } else {
                $productVersion->setIsCostChanged(0);
            }

            if (isset($changedData['special_price'])) {
                $productVersion->setIsSpecialPriceChanged(1);
            } else {
                $productVersion->setIsSpecialPriceChanged(0);
            }

            if ($isNew || $isNewUpdate) {
                $productVersion->setIsNewProduct(1);
            }
            $productVersion->setUserUpdated($this->getUpdatedByUser());

            $productVersion->setCreatedFrom($createdFrom);
            if ($createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE || $createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                $stagingData = $data['staging'] ?? [];
                if (isset($stagingData['start_time'])) {
                    $productVersion->setScheduleStartTime($stagingData['start_time']);
                }
                if (isset($stagingData['end_time'])) {
                    $productVersion->setScheduleEndTime($stagingData['end_time']);
                }
            }
            if ($status || $hasStockChange) {
                $productVersion->setStatus(Product::STATUS_ENABLED);
                $status = Product::STATUS_ENABLED;
            }
            $this->productVersionRepository->save($productVersion);
            // END: Save a new record to the product version table

            if ($createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE || $createdFrom == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                $productVersionData = $this->productVersionDataFactory->create();
                $productVersionData->setParentId($productVersion->getId());
                $productVersionData->setInformation($this->serializer->serialize($data));
                $this->productVersionDataRepository->save($productVersionData);
            } else {
                $marketplaceProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
                $marketplaceProduct->setData('status', $status);
                $marketplaceProduct->setData('dealer_approve_status', 0);
                $this->marketplaceProductManagement->save($marketplaceProduct);
            }

            if (isset($duplicateData['product_id'])) {
                return ['error' => false, 'data' => $changedData, 'product_id' => $duplicateData['product_id'], 'log_id' => $productVersion->getId()];
            }
            return ['error' => false, 'data' => $changedData, 'log_id' => $productVersion->getId()];
        } catch (\Exception $e) {
            if (isset($duplicateData['product_id'])) {
                return ['error' => true, 'message' => __($e->getMessage()), 'product_id' => $duplicateData['product_id']];
            }
            return ['error' => true, 'message' => __($e->getMessage())];
        }
    }

    /**
     * Collect product data that needs editing following product format data.
     *
     * @param array $rawData
     *
     * @return array
     */
    private function collectEditingData(array $rawData): array
    {
        $data = $rawData['product'] ?? [];
        if (!isset($data['status']) && isset($rawData['status']) && $rawData['status']) {
            $data['status'] = $rawData['status'];
        }

        if (isset($rawData['editor_updated_fields'])) {
            $data['editor_updated_fields'] = $rawData['editor_updated_fields'];
        }

        $cost = $data['cost'] ?? null;
        if (!empty($cost) && (int)$cost != $cost) {
            $data['cost'] = round((float)($cost));
        }

        $productType = $rawData['type'] ?? null;
        $data[ProductInterface::TYPE_ID] = $productType;
        $data[ProductInterface::ATTRIBUTE_SET_ID] = $rawData['set'] ?? null;

        // START: Collect data for configurable product
        if ($productType === Configurable::TYPE_CODE) {
            $data['quantity_and_stock_status']['qty'] = 0;
            $data[BuildConfigurableProduct::ATTRIBUTES] = $rawData['attributes'] ?? [];
            $data[BuildConfigurableProduct::AFFECT_ATTRIBUTES] = $rawData['affect_configurable_product_attributes'] ?? 0;
            $data[BuildConfigurableProduct::ATTRIBUTES_DATA] = $rawData['product']['configurable_attributes_data'] ?? [];
            $data[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS] = $rawData['associated_product_ids'] ?? [];
            $data[BuildConfigurableProduct::VARIATIONS_MATRIX] = $rawData['variations-matrix'] ?? [];
        }
        // END: Collect data for configurable product

        // START: Collect data for bundle product
        if ($productType === BundleType::TYPE_CODE) {
            $data[BuildBundleProduct::BUNDLE_OPTIONS] = $rawData[BuildBundleProduct::BUNDLE_OPTIONS] ?? [];
            $data[BuildBundleProduct::BUNDLE_SELECTIONS] = $rawData[BuildBundleProduct::BUNDLE_SELECTIONS] ?? [];
            $data[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS] = $rawData[BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS] ?? 0;
        }
        // END: Collect data for bundle product

        // START: Collect data for image gallery
        $mediaTypes = [
            AddImageToMediaGallery::KEY_IMAGE,
            AddImageToMediaGallery::KEY_SMALL_IMAGE,
            AddImageToMediaGallery::KEY_THUMBNAIL,
            AddImageToMediaGallery::KEY_SWATCH_IMAGE,
            AddImageToMediaGallery::KEY_DPA_IMAGE
        ];
        foreach ($mediaTypes as $type) {
            $file = $data[$type] ?? null;
            if ($file && strrpos((string)$file, '.tmp') == strlen($file) - 4) {
                $file = substr($file, 0, strlen($file) - 4);
                $data[$type] = $file;
            }
        }
        if (!empty($data['quantity_and_stock_status']) && isset($data['quantity_and_stock_status']['is_in_stock'])) {
            $data['quantity_and_stock_status']['is_in_stock'] = $data['quantity_and_stock_status']['is_in_stock'] ? 1 : 0;
        }

        if (!empty($data['media_gallery']['images'])) {
            foreach ($data['media_gallery']['images'] as $image) {
                $file = $image['file'] ?? '';
                if ($file && strrpos((string)$file, '.tmp') == strlen($file) - 4) {
                    $file = substr($file, 0, strlen($file) - 4);
                }
                $data['image_gallery'][trim($image['value_id']) ? $image['value_id'] : substr(hash('sha256', microtime()), 0, 10)] = [
                    'file' => $file,
                    'value_id' => $image['value_id'] ?? null,
                    'media_type' => $image['media_type'] ?? null,
                    'label' => $image['label'] ?? null,
                    'position' => $image['position'] ?? null,
                    'disabled' => $image['disabled'] ?? null,
                    'removed' => $image['removed'] ?? null,
                ];
            }
            unset($data['media_gallery']);
        }
        // END: Collect data for image gallery

        // START: Collect data for downloadable product
        if (!empty($rawData['is_downloadable'])) {
            $data['product_has_weight'] = 0;
            $data['weight'] = null;

            // START: Collect data for downloadable link
            foreach (($rawData['downloadable']['link'] ?? []) as $downloadableLink) {
                $linkType = $downloadableLink['type'] ?? null;
                if ($linkType === Download::LINK_TYPE_FILE) {
                    $downloadableLink['file'] = $this->serializer->unserialize($downloadableLink['file'] ?? '{}');
                    $downloadableLink['sample']['file'] = $this->serializer->unserialize($downloadableLink['sample']['file'] ?? '{}');
                }
                $data[BuildDownloadableProductLinks::DOWNLOADABLE_LINK][] = [
                    'link_id' => !empty($downloadableLink['link_id']) ? $downloadableLink['link_id'] : null,
                    'title' => $downloadableLink['title'] ?? null,
                    'price' => (float)($downloadableLink['price'] ?? null),
                    'type' => $downloadableLink['type'] ?? null,
                    'file' => isset($downloadableLink['file']) && $downloadableLink['file'] !== '[]'
                        ? $downloadableLink['file'] : null,
                    'link_url' => $downloadableLink['link_url'] ?? null,
                    'sample' => [
                        'type' => $downloadableLink['sample']['type'] ?? null,
                        'file' => isset($downloadableLink['sample']['file']) && $downloadableLink['sample']['file'] !== '[]'
                            ? $downloadableLink['sample']['file'] : null,
                        'url' => $downloadableLink['sample']['url'] ?: null,
                    ],
                    'sort_order' => $downloadableLink['sort_order'] ?? null,
                    'number_of_downloads' => $downloadableLink['number_of_downloads'] ?? 0,
                    'is_shareable' => $downloadableLink['is_shareable'] ?? null,
                    'is_delete' => !empty($downloadableLink['is_delete']) ? $downloadableLink['is_delete'] : null,
                ];
            }
            // END: Collect data for downloadable link

            // START: Collect data for downloadable sample
            foreach (($rawData['downloadable']['sample'] ?? []) as $downloadableSample) {
                $linkType = $downloadableSample['type'] ?? null;
                if ($linkType === Download::LINK_TYPE_FILE) {
                    $downloadableSample['file'] = $this->serializer->unserialize($downloadableSample['file'] ?? '{}');
                }
                $data[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE][] = [
                    'sample_id' => !empty($downloadableSample['sample_id']) ? $downloadableSample['sample_id'] : null,
                    'title' => $downloadableSample['title'] ?? null,
                    'type' => $downloadableSample['type'] ?? null,
                    'file' => isset($downloadableSample['file']) && $downloadableSample['file'] !== '[]'
                        ? $downloadableSample['file'] : null,
                    'sample_url' => $downloadableSample['sample_url'] ?? null,
                    'sort_order' => $downloadableSample['sort_order'] ?? null,
                    'is_delete' => !empty($downloadableSample['is_delete']) ? $downloadableSample['is_delete'] : null,
                ];
            }
            // END: Collect data for downloadable sample
        }
        // END: Collect data for downloadable product
        // START: Collect data for product link (grouped, related, upsell and crosssell product)
        foreach ($rawData['links'] ?? [] as $linkType => $productLinks) {
            $plus = 0;
            if ($linkType == BuildGroupedProductLinks::KEY_GROUPED_TYPE) {
                $data[$linkType] = $productLinks;
            }
            ksort($productLinks);
            foreach ($productLinks as $position => $productLink) {
                if (!$position) $plus = 1;
                if (isset($productLink[Link::KEY_SKU])) {
                    $data[$linkType][$position + $plus] = $productLink[Link::KEY_SKU];
                }
            }
        }

        if (isset($data['wk_marketplace_preorder'])) {
            $isEnablePreorder = $this->preorderHelper->isEnablePreorder($data['wk_marketplace_preorder']);
            if (!$isEnablePreorder) {
                unset($data['preorder_mode']);
                unset($data['preorder_x_days']);
                unset($data['preorder_start_date']);
                unset($data['preorder_use_qty']);
                unset($data['preorder_end_date']);
                unset($data['preorder_ship_date']);
                unset($data['wk_marketplace_availability']);
            }
        }
        if (isset($data['limit_purchased_enable']) && $data['limit_purchased_enable'] == 0) {
            unset($data['limit_purchased_qty']);
            unset($data['limit_purchased_start_time']);
            unset($data['limit_purchased_end_time']);
            unset($data['limit_purchased_customer_group']);
        }

        if (isset($data['shipping_method'])) {
        }

        // END: Collect data for product link (grouped, related, upsell and crosssell product)
        return $data;
    }

    /**
     * Collect original product data before editing following product data format.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    private function collectOriginalData(ProductInterface $product): array
    {
        $data = $product->getData();
        $preservationStatusAttribute = $product->getCustomAttribute('preservation_status');
        if ($preservationStatusAttribute && isset($data['preservation_status'])) {
            $preservationStatusValue = $preservationStatusAttribute->getValue();
            if ($preservationStatusValue == 'normal') {
                $data['preservation_status'] = '';
            } else {
                $data['preservation_status'] = $preservationStatusValue;
            }
        }
        if (isset($data['long_time_ship']) && !$data['long_time_ship']) {
            $data['long_time_ship'] = '';
        }
        $cost = $data['cost'] ?? null;
        if (!empty($cost) && (int)$cost != $cost) {
            $data['cost'] = round((float)($cost));
        }
        $extensionAttributes = $product->getExtensionAttributes();

        $data['product_has_weight'] = $product->getWeight() ? 1 : 0;
        if ($product->getTypeId() !== 'simple') {
            $data['product_has_weight'] = 0;
        }

        foreach (self::$arrayDateAttribute as $attribute) {
            if ($product->getData($attribute)) {
                $data[$attribute] = date("m/d/Y", strtotime($product->getData($attribute)));
            }
        }

        // START: Collect certification data (Branch8)
        if ($product->getId()) {
            $rowId = (int)($product->getData('row_id') ?: $product->getId());
            $pcColl = $this->pcCollectionFactory->create()
                ->addProductFilter($rowId);
            $certData = [];
            foreach ($pcColl as $pc) {
                $certData[(string)$pc->getData('certification_type_id')] = (string)$pc->getData('certification_value');
            }
            if (!empty($certData)) {
                $data['branch8_certifications_post'] = $this->serializer->serialize($certData);
            } else {
                $data['branch8_certifications_post'] = '';
            }
        } else {
            $data['branch8_certifications_post'] = '';
        }

        // START: Collect data for image gallery
        if (!empty($data['media_gallery']['images'])) {
            foreach ($data['media_gallery']['images'] as $image) {
                $file = $image['file'] ?? '';
                $data['image_gallery'][$image['value_id'] ?? null] = [
                    'file' => $file,
                    'value_id' => $image['value_id'] ?? null,
                    'media_type' => $image['media_type'] ?? null,
                    'label' => $image['label'] ?? null,
                    'position' => $image['position'] ?? null,
                    'disabled' => $image['disabled'] ?? null,
                    'removed' => ''
                ];
            }
            unset($data['media_gallery']);
        }
        // END: Collect data for image gallery

        // START: Collect data for downloadable product
        if ($product->getTypeId() === Type::TYPE_DOWNLOADABLE) {
            // START: Collect data for downloadable link
            $data[BuildDownloadableProductLinks::DOWNLOADABLE_LINK] = [];
            $downloadableProductLinks = $extensionAttributes?->getDownloadableProductLinks();
            if ($downloadableProductLinks) {
                foreach ($downloadableProductLinks as $downloadableProductLink) {
                    $data[BuildDownloadableProductLinks::DOWNLOADABLE_LINK][] = [
                        'link_id' => $downloadableProductLink->getId(),
                        'title' => $downloadableProductLink->getTitle(),
                        'price' => (float)$downloadableProductLink->getPrice(),
                        'type' => $downloadableProductLink->getLinkType(),
                        'file' => $downloadableProductLink->getLinkFile(),
                        'link_url' => $downloadableProductLink->getLinkUrl(),
                        'sample' => [
                            'type' => $downloadableProductLink->getSampleType(),
                            'file' => $downloadableProductLink->getSampleFile(),
                            'url' => $downloadableProductLink->getSampleUrl(),
                        ],
                        'sort_order' => $downloadableProductLink->getSortOrder(),
                        'number_of_downloads' => (int)$downloadableProductLink->getNumberOfDownloads(),
                        'is_shareable' => $downloadableProductLink->getIsShareable(),
                        'is_delete' => null
                    ];
                }
            }
            // END: Collect data for downloadable link

            // START: Collect data for downloadable sample
            $data[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE] = [];
            $downloadableProductSamples = $extensionAttributes?->getDownloadableProductSamples();
            if ($downloadableProductSamples) {
                foreach ($downloadableProductSamples as $downloadableProductSample) {
                    $data[BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE][] = [
                        'sample_id' => $downloadableProductSample->getId(),
                        'title' => $downloadableProductSample->getTitle(),
                        'type' => $downloadableProductSample->getSampleType(),
                        'file' => $downloadableProductSample->getSampleFile(),
                        'sample_url' => $downloadableProductSample->getSampleUrl(),
                        'sort_order' => $downloadableProductSample->getSortOrder(),
                        'is_delete' => null
                    ];
                }
            }
            // START: Collect data for downloadable sample
        }
        // END: Collect data for downloadable product

        // START: Collect data for configurable product
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $configurableProductLinks = $extensionAttributes?->getConfigurableProductLinks() ?: [];
            $data[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS] = array_values($configurableProductLinks);
            $data[BuildConfigurableProduct::AFFECT_ATTRIBUTES] = 1;

            $configurableProductOptions = $extensionAttributes?->getConfigurableProductOptions() ?: [];
            $attributes = array_map(fn($option) => $option->getAttributeId(), $configurableProductOptions);
            $data[BuildConfigurableProduct::ATTRIBUTES] = $attributes;
        }
        // END: Collect data for configurable product

        // START: Collect data for bundle product
        if ($product->getTypeId() === BundleType::TYPE_CODE) {
            $data[BuildBundleProduct::BUNDLE_OPTIONS] = [];
            $bundleOptions = $extensionAttributes?->getBundleProductOptions() ?: [];
            foreach ($bundleOptions as $option) {
                $data[BuildBundleProduct::BUNDLE_OPTIONS][$option->getOptionId()] = [
                    'option_id' => $option->getOptionId(),
                    'required' => $option->getRequired(),
                    'position' => $option->getPosition(),
                    'type' => $option->getType(),
                    'title' => $option->getTitle(),
                ];
            }
            $selectionCollection = $product->getTypeInstance(true)
                ->getSelectionsCollection(
                    $product->getTypeInstance(true)->getOptionsIds($product),
                    $product
                );
            $selectionData = [];
            foreach ($selectionCollection as $selection) {
                $selectionArray = [];
                $selectionArray['selection_id'] = $selection->getSelectionId();
                $selectionArray['option_id'] = $selection->getOptionId();
                $selectionArray['selection_price_value'] = $selection->getSelectionPriceValue();
                $selectionArray['selection_price_type'] = $selection->getSelectionPriceType();
                $selectionArray['selection_qty'] = $selection->getSelectionQty();
                $selectionArray['selection_can_change_qty'] = $selection->getSelectionCanChangeQty();
                $selectionArray['product_id'] = $selection->getProductId();
                $selectionArray['position'] = $selection->getPosition();
                $selectionData[$selection->getOptionId()][$selection->getSelectionId()] = $selectionArray;
            }
            $data[BuildBundleProduct::BUNDLE_SELECTIONS] = $selectionData;
        }
        // END: Collect data for configurable product

        foreach ($product->getProductLinks() as $productLink) {
            $data[$productLink->getLinkType()][$productLink->getPosition()] = $productLink->getLinkedProductSku();
        }

        //        foreach ($this->getMultiselectAttributes() as $attributeCode) {
        //            $rawValue = $product->getData($attributeCode);
        //            $data[$attributeCode] = $rawValue ? explode(',', $rawValue) : [''];
        //        }
        //
        //        // Sort category IDs
        //        $categoryIds = $data['category_ids'] ?? [];
        //        sort($categoryIds);
        //        $data['category_ids'] = $categoryIds;
        if (isset($data['quantity_and_stock_status']['is_in_stock']) && !$data['quantity_and_stock_status']['is_in_stock']) {
            $data['quantity_and_stock_status']['is_in_stock'] = 0;
        }
        if (!isset($data['quantity_and_stock_status']['qty']) && isset($data['quantity_and_stock_status']['is_in_stock'])) {
            $data['quantity_and_stock_status']['qty'] = 0;
        }
        if (isset($data['flagstore_category']) && !$data['flagstore_category']) {
            $data['flagstore_category'] = '';
        }

        return $data;
    }

    /**
     * Collect original product data before editing following product data format.
     *
     * @param mixed[] $wholedata
     *
     * @return array
     */
    private function processDuplicateProduct($wholedata, $sellerId)
    {
        $returnArr = [];
        /*
        * Create duplicate product
        */
        if (isset($wholedata['back']) && $wholedata['back'] === 'duplicate') {
            // get and save product for admin store id
            if (isset($wholedata['product']['website_ids']) && !$wholedata['product']['website_ids']) {
                $wholedata['product']['website_ids'][] = $this->_marketplaceHelperData->getWebsiteId();
            }
            $storeId = 0;
            $duplicateCatalogProduct = $this->_marketplaceProductBuilder->build($wholedata, $storeId);
            $duplicateCatalogProduct->setName('copy-' . $duplicateCatalogProduct->getName());

            $status1 = $this->_marketplaceHelperData->getIsProductApproval() ?
                SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;

            $newProduct = $this->productCopier->copy($duplicateCatalogProduct);
            $storeIds = $newProduct->getStoreIds();
            $productId = $newProduct->getId();
            $productResource = $newProduct->getResource();
            $currentUrlKey = $newProduct->getUrlKey();
            foreach ($storeIds as $storeId) {
                $urlKey = $productResource->getAttributeRawValue($productId, 'url_key', $storeId);
                if ($currentUrlKey != $urlKey) {
                    $newProduct->setStoreId($storeId);
                    $newProduct->setUrlKey($currentUrlKey);
                    $productResource->saveAttribute($newProduct, 'url_key');
                }
            }
            $newProduct->setStoreId(Store::DEFAULT_STORE_ID);
            $productLinks = $duplicateCatalogProduct->getProductLinks();
            if (!empty($productLinks)) {
                foreach ($productLinks as $sk => $productLink) {
                    $productLink->setSku($newProduct->getSku());
                    $productLinks[$sk] = $productLink;
                }
                $product = $this->productRepository->getById($newProduct->getId(), true, Store::DEFAULT_STORE_ID);
                $product->setProductLinks($productLinks);
                $this->productRepository->save($product);
            }

            // setting stock quantity and status
            $this->updateStockData($newProduct->getId());
            $this->variationaHelper->saveVariation($newProduct->getId(), $wholedata);

            // Reset flagstore_category: copied from original product but belongs to
            // the original seller's flagship store, invalid for the new product.
            $oldFlagstoreCategory = $duplicateCatalogProduct->getData('flagstore_category');
            if (!empty($oldFlagstoreCategory)) {
                $newProduct->setData('flagstore_category', null);
                $productResource->saveAttribute($newProduct, 'flagstore_category');
                $categoryIds = $newProduct->getCategoryIds();
                if (is_array($categoryIds) && in_array($oldFlagstoreCategory, $categoryIds)) {
                    $categoryIds = array_values(array_diff($categoryIds, [$oldFlagstoreCategory]));
                    $newProduct->setCategoryIds($categoryIds);
                    $productResource->saveAttribute($newProduct, 'category_ids');
                }
            }

            $this->_messageManager->addSuccessMessage(__('You duplicated the product.'));
            $newProductId = $newProduct->getEntityId();
            $wholedata['id'] = $newProductId;
            $editFlag = 0;

            $this->_eventManager->dispatch(
                'mp_customoption_setdata',
                [$wholedata]
            );

            $newAssociatedProductIds = [];

            /* Update marketplace product for duplicate product*/
            $this->saveMaketplaceProductTable(
                $newProductId,
                $newProduct->getRowId(),
                $sellerId,
                $status1,
                $editFlag,
                $newAssociatedProductIds
            );

            // custom here: add if the product needs tracking after being copied/duplicated
            $this->productCopyTracking->insert((int)$newProduct->getId());

            /*
            * Marketplace Custom Attribute Set Tier Price Observer for duplicate product
            */
            $this->_eventManager->dispatch(
                'mp_customattribute_settierpricedata',
                [$wholedata]
            );

            /*
            * Marketplace Product Save After Observer for duplicate product
            */
            $this->_eventManager->dispatch(
                'mp_product_save_after',
                [$wholedata]
            );

            /*
            * Marketplace Product Send Mail Function for duplicate product
            */
            $this->sendProductMail($wholedata, $sellerId, $editFlag);
            $returnArr['product_id'] = $newProductId;
            $returnArr['row_id'] = $newProduct->getRowId();
        }
        return $returnArr;
    }

    /**
     * Update Stock Data of Product
     *
     * @param integer $productId
     * @param integer $qty
     * @param integer $isInStock
     */
    private function updateStockData($productId, $qty = 0, $isInStock = 0)
    {
        try {
            $scopeId = $this->_stockConfiguration->getDefaultScopeId();
            $stockItem = $this->_stockRegistryProvider->getStockItem($productId, $scopeId);
            if ($stockItem->getItemId()) {
                $stockItem->setQty($qty);
                $stockItem->setIsInStock($isInStock);
                $stockItem->save();
            }
        } catch (\Exception $e) {
            $this->_marketplaceHelperData->logDataInLogger(
                "Controller_Product_SaveProduct updateStockData : " . $e->getMessage()
            );
        }
    }

    /**
     * Set Product Records in marketplace_product table.
     *
     * @param int $mageProductId
     * @param int $rowId
     * @param int $sellerId
     * @param int $status
     * @param int $editFlag
     * @param array $associatedProductIds
     */
    private function saveMaketplaceProductTable(
        $mageProductId,
        $rowId,
        $sellerId,
        $status,
        $editFlag,
        $associatedProductIds
    ) {
        $savedIsApproved = 0;
        $sellerProductId = 0;
        $helper = $this->_marketplaceHelperData;
        if ($mageProductId) {
            $sellerProductColls = $this->_mpProductCollectionFactory->create()
                ->addFieldToFilter(
                    'mageproduct_id',
                    $mageProductId
                )->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
            foreach ($sellerProductColls as $sellerProductColl) {
                $sellerProductId = $sellerProductColl->getId();
                $savedIsApproved = $sellerProductColl->getIsApproved();
            }
            $collection1 = $this->_mpProductFactory->create()->load($sellerProductId);
            $collection1->setMageproductId($mageProductId);
            $collection1->setSellerId($sellerId);
            $collection1->setMageProRowId($rowId);
            $collection1->setStatus($status);
            $isApproved = 1;
            if ($helper->getIsProductEditApproval()) {
                $collection1->setAdminPendingNotification(2);
            }
            if (!$editFlag) {
                $collection1->setCreatedAt($this->_date->gmtDate());
                $collection1->setAdminPendingNotification(1);
                if ($helper->getIsProductApproval()) {
                    $isApproved = 0;
                }
            } elseif (!$helper->getIsProductEditApproval()) {
                $isApproved = $savedIsApproved;
            } else {
                $isApproved = 0;
            }
            $collection1->setIsApproved($isApproved);
            $collection1->setUpdatedAt($this->_date->gmtDate());
            $collection1->save();
        }

        foreach ($associatedProductIds as $associatedProductId) {
            if ($associatedProductId) {
                $sellerAssociatedProductId = 0;
                $sellerProductColls = $this->_mpProductCollectionFactory->create()
                    ->addFieldToFilter(
                        'mageproduct_id',
                        $associatedProductId
                    )
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    );
                foreach ($sellerProductColls as $sellerProductColl) {
                    $sellerAssociatedProductId = $sellerProductColl->getId();
                }
                $storeId = $this->_marketplaceHelperData->getCurrentStoreId();
                $productFactory = $this->_productFactory->create();
                $productFactory->setStoreId($storeId);
                $productFactory->load($associatedProductId);
                $assorowId = $productFactory->getRowId();
                $collection1 = $this->_mpProductFactory->create()->load($sellerAssociatedProductId);
                $collection1->setMageproductId($associatedProductId);
                $collection1->setMageProRowId($assorowId);
                if (!$editFlag) {
                    /* If new product is added*/
                    $collection1->setCreatedAt($this->_date->gmtDate());
                }
                if ($editFlag) {
                    $collection1->setAdminPendingNotification(2);
                }
                $collection1->setUpdatedAt($this->_date->gmtDate());
                $collection1->setSellerId($sellerId);
                $collection1->setIsApproved($isApproved);
                $collection1->save();
            }
        }
    }

    /**
     * Send product email
     *
     * @param array $data
     * @param string $sellerId
     * @param bool $editFlag
     */
    private function sendProductMail($data, $sellerId, $editFlag = null)
    {
        $helper = $this->_marketplaceHelperData;

        $customer = $this->customerModel->create()->load($sellerId);

        $sellerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        $sellerEmail = $customer->getEmail();

        if (isset($data['product']) && !empty($data['product']['category_ids'])) {
            $categoriesy = $this->categoryModel->create()->load(
                $data['product']['category_ids'][0]
            );
            $categoryname = $categoriesy->getName();
        } else {
            $categoryname = '';
        }

        $emailTempVariables = [];
        $adminStoremail = $helper->getAdminEmailId();
        $adminEmail = $adminStoremail ?
            $adminStoremail : $helper->getDefaultTransEmailId();
        $adminUsername = $helper->getAdminName();

        $emailTempVariables['myvar1'] = $data['product']['name'];
        $emailTempVariables['myvar2'] = $categoryname;
        $emailTempVariables['myvar3'] = $adminUsername;
        if ($editFlag == null) {
            $emailTempVariables['myvar4'] = __(
                'I would like to inform you that recently I have added a new product in the store.'
            );
        } else {
            $emailTempVariables['myvar4'] = __(
                'I would like to inform you that recently I have updated a  product in the store.'
            );
        }
        $senderInfo = [
            'name' => $sellerName,
            'email' => $sellerEmail,
        ];
        $receiverInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
        ];
        if (($editFlag == null && $helper->getIsProductApproval() == 1)
            || ($editFlag && $helper->getIsProductEditApproval() == 1)
        ) {
            $this->mpEmailHelper->sendNewProductMail(
                $emailTempVariables,
                $senderInfo,
                $receiverInfo,
                $editFlag
            );
        }
    }

    /**
     * Retrieve multiselect attributes of product.
     *
     * @return array
     */
    private function getMultiselectAttributes(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AttributeInterface::FRONTEND_INPUT, ['multiselect'], 'in')
            ->create();

        $items = $this->productAttributeRepository->getList($searchCriteria)->getItems();
        $attributes = [];
        foreach ($items as $attribute) {
            $attributes[$attribute->getAttributeId()] = $attribute->getAttributeCode();
        }
        return $attributes;
    }

    /**
     * Get the user who updated the data.
     *
     * @return string
     */
    private function getUpdatedByUser(): string
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return $this->userFactory->create()->load($userId)->getUserName();
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                $customer = $this->customerModel->create()->load($userId);
                return $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        $sellerName = $this->authSession->getUser()?->getUserName();
        if (!empty($sellerName)) {
            return $sellerName;
        }

        $isPartner = $this->_marketplaceHelperData->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->_marketplaceHelperData->getCustomerId();
            }
            $customer = $this->customerModel->create()->load($sellerId);
            return $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
        }

        return '';
    }

    protected function isSellerInDealer($sellerId)
    {
        $select = $this->_conn->select()
            ->from('amasty_amrolepermissions_rule_seller', ['rule_id'])
            ->where('seller_id = ?', $sellerId);
        return $this->_conn->fetchOne($select);
    }
}
