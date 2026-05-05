<?php

namespace Branch8\CatalogCustom\Controller\Product;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Downloadable\Api\Data\SampleInterfaceFactory as SampleFactory;
use Magento\Framework\Filter\FilterInput;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Serialize\SerializerInterface;
use Webkul\Marketplace\Controller\Product\Builder;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as MpProductCollection;

class SaveProduct extends \Webkul\Marketplace\Controller\Product\SaveProduct
{
    public const END_TIME = 'end_time';
    public const START_TIME = 'start_time';
    protected $update;
    protected $updateRepository;
    protected $versionManager;
    protected $productStaging;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $_serializerInterface;

    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Catalog\Model\Product\TypeTransitionManager $catalogProductTypeManager,
        \Magento\ConfigurableProduct\Model\Product\VariationHandler $variationHandler,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeConfigurable,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
        \Magento\Catalog\Model\Product\Link\Resolver $linkResolver,
        \Magento\Catalog\Model\Product\Initialization\Helper\ProductLinks $productLinks,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Filesystem $filesystem,
        Builder $marketplaceProductBuilder,
        MarketplaceHelperData $marketplaceHelperData,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\Copier $productCopier,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Webkul\Marketplace\Model\ProductFactory $mpProductFactory,
        MpProductCollection $mpProductCollectionFactory,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Staging\Api\Data\UpdateInterface $update,
        \Magento\CatalogStaging\Api\ProductStagingInterface $productStaging,
        SerializerInterface $serializerInterface,
        CustomerFactory $customerModel = null,
        CategoryFactory $categoryModel = null,
        MpEmailHelper $mpEmailHelper = null,
        ProductCustomOptionInterfaceFactory $productCustomOption = null,
        \Magento\Downloadable\Model\Link\Builder $linkBuilder = null,
        LinkFactory $linkFactory = null,
        CategoryLinkManagementInterface $categoryLinkManagement = null,
        \Magento\Downloadable\Model\Sample\Builder $sampleBuilder = null,
        SampleFactory $sampleFactory = null,
        \Magento\Catalog\Model\ProductFactory $productFactory = null,
        ModuleManager $moduleManager = null
    ) {
        $this->update = $update;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->productStaging = $productStaging;
        $this->_serializerInterface         = $serializerInterface;
        parent::__construct($eventManager, $date, $dateFilter, $catalogProductTypeManager, $variationHandler,
            $productTypeConfigurable, $productRepositoryInterface, $productLinkFactory, $linkResolver, $productLinks,
            $jsHelper, $filesystem, $marketplaceProductBuilder, $marketplaceHelperData, $registry, $productCopier,
            $messageManager, $mpProductFactory, $mpProductCollectionFactory, $stockRegistryProvider,
            $stockConfiguration, $jsonHelper, $customerModel, $categoryModel, $mpEmailHelper, $productCustomOption,
            $linkBuilder, $linkFactory, $categoryLinkManagement, $sampleBuilder, $sampleFactory, $productFactory,
            $moduleManager);
    }

    /**
     * Get LinkFactory instance.
     *
     * @return LinkFactory
     */
    private function getLinkFactory()
    {
        return $this->linkFactory;
    }

    /**
     * Get Sample Factory.
     *
     * @return SampleFactory
     */
    private function getSampleFactory()
    {
        return $this->sampleFactory;
    }

    /**
     * SaveProductData method for seller's product save action.
     *
     * @param int $sellerId
     * @param mixed[] $wholedata
     *
     * @return array
     */
    public function saveProductData($sellerId, $wholedata, $profile = '')
    {
        $returnArr = [];
        $returnArr['error'] = 0;
        $returnArr['product_id'] = '';
        $returnArr['message'] = '';
        $wholedata['new-variations-attribute-set-id'] = $wholedata['set'];
        $wholedata['product']['attribute_set_id'] = $wholedata['set'];

        $helper = $this->_marketplaceHelperData;

        if (!$this->_registry->registry('mp_flat_catalog_flag')) {
            $this->_registry->register('mp_flat_catalog_flag', 1);
        }
        if (!empty($wholedata['id'])) {
            $mageProductId = $wholedata['id'];
            $editFlag = 1;
            $storeId = $helper->getCurrentStoreId();
            $savedWebsiteIds = $this->_productRepositoryInterface
                ->getById(
                    $mageProductId
                )->getWebsiteIds();
            $wholedata['product']['website_ids'] = $savedWebsiteIds;
            $this->_eventManager->dispatch(
                'mp_customattribute_deletetierpricedata',
                [$wholedata]
            );
            $wholedata = $this->adminStoreMediaImages($mageProductId, $wholedata);
        } else {
            $mageProductId = '';
            $editFlag = 0;
            $storeId = 0;
            if (!isset($wholedata['product']['website_ids'])) {
                $wholedata['product']['website_ids'][] = $helper->getWebsiteId();
            }
            $wholedata['product']['url_key'] = $wholedata['product']['url_key'] ?? '';
        }
        if ($mageProductId) {
            $status1 = $helper->getIsProductEditApproval() ?
                SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
            if ($helper->getIsProductApproval() && !$helper->getIsProductEditApproval()) {
                $sellerProductColls = $this->_mpProductCollectionFactory->create()
                    ->addFieldToFilter(
                        'mageproduct_id',
                        $mageProductId
                    )->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    );
                foreach ($sellerProductColls as $sellerProductColl) {
                    $status1 = !$sellerProductColl->getIsApproved() ?
                        SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
                }
            }
        } else {
            $status1 = $helper->getIsProductApproval() ?
                SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
        }

        $status = isset($wholedata['status'])?$wholedata['status']:$status1;

        $wholedata['store'] = $storeId;
        /*
        * Marketplace Product save before Observer
        */
        $this->_eventManager->dispatch(
            'mp_product_save_before',
            [$wholedata]
        );

        $catalogProductTypeId = $wholedata['type'];
        if ($this->moduleManager->isEnabled('Magento_SharedCatalog')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $sellerData = $this->customerModel->create()->load($sellerId);
            $sellerGrpId = $sellerData->getGroupId();
            $sharedCatalog = $objectManager->create(
                \Magento\SharedCatalog\Model\ProductItemFactory::class
            )->create();
            $sharedCatalog->getCollection()
                ->addFieldToFilter('sku', ['eq' => $wholedata['product']['sku']])
                ->addFieldToFilter('customer_group_id', ['eq' => $sellerGrpId]);
            if (!$sharedCatalog->getSize()) {
                $productItemManagement = $objectManager->create(
                    \Magento\SharedCatalog\Api\ProductItemManagementInterface::class
                );
                $productItemManagement->saveItem($wholedata['product']['sku'], $sellerGrpId);
            }
        }
        /*
        * Product Initialize method to set product data
        */
        $catalogProduct = $this->productInitialize(
            $this->_marketplaceProductBuilder->build($wholedata, $storeId),
            $wholedata
        );

        /*for downloadable products start*/

        $catalogProduct = $this->buildDownloadableProduct($catalogProduct, $wholedata);

        /*for downloadable products end*/

        /*for configurable products start*/

        $associatedProductIds = [];

        $resultData = $this->buildConfigurableProduct($catalogProduct, $wholedata);

        $catalogProduct = $resultData['catalogProduct'];
        $associatedProductIds = $resultData['associatedProductIds'];

        /*for configurable products end*/

        $this->_catalogProductTypeManager->processProduct($catalogProduct);

        $set = $catalogProduct->getAttributeSetId();

        $type = $catalogProduct->getTypeId();

        if (isset($set) && isset($type)) {
            $allowedsets = explode(',', $helper->getAllowedAttributesetIds());
            $allowedtypes = explode(',', $helper->getAllowedProductType());
            if (!in_array($type, $allowedtypes) || !in_array($set, $allowedsets)) {
                $returnArr['error'] = 1;
                $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

                return $returnArr;
            }
        } else {
            $returnArr['error'] = 1;
            $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

            return $returnArr;
        }
        if (isset($type) && $type == 'configurable') {
            $allowedtypes = explode(',', $helper->getAllowedProductType());
            if (!in_array('virtual', $allowedtypes) && empty($wholedata['product']['weight'])) {
                $returnArr['error'] = 1;
                $returnArr['message'] = __(
                    'You are not allowed to add virtual products, so please set product weight.'
                );

                return $returnArr;
            }
        }

        // if ($catalogProduct->getSpecialPrice() == '') {
        //     $catalogProduct->setSpecialPrice(null);
        //     $catalogProduct->getResource()->saveAttribute($catalogProduct, 'special_price');
        // }

        $originalSku = $catalogProduct->getSku();
        $isCheckUpdateProductStaging = $this->isCheckUpdateProductStaging($catalogProduct, $profile);
        if($isCheckUpdateProductStaging){
            $returnArr['product_id'] = $mageProductId;
            $returnArr['row_id'] = $catalogProduct->getRowId();
            return $returnArr;
        }
        $catalogProduct->setStatus($status)->save();
        $mageProductId = $catalogProduct->getId();

        $this->handleImageRemoveError($wholedata, $mageProductId);
        $this->getCategoryLinkManagement()->assignProductToCategories(
            $catalogProduct->getSku(),
            $catalogProduct->getCategoryIds()
        );

        /*for configurable associated products save start*/

        $this->saveConfigurableAssociatedProducts($wholedata, $storeId);

        /*for configurable associated products save end*/

        $wholedata['id'] = $mageProductId;
        $this->_eventManager->dispatch(
            'mp_customoption_setdata',
            [$wholedata]
        );

        /* Update marketplace product*/
        $this->saveMaketplaceProductTable(
            $mageProductId,
            $catalogProduct->getRowId(),
            $sellerId,
            $status1,
            $editFlag,
            $associatedProductIds
        );

        /*
        * Marketplace Custom Attribute Set Tier Price Observer
        */
        $this->_eventManager->dispatch(
            'mp_customattribute_settierpricedata',
            [$wholedata]
        );

        /*
        * Marketplace Product Save After Observer
        */
        $this->_eventManager->dispatch(
            'mp_product_save_after',
            [$wholedata]
        );

        /*
        * Marketplace Product Send Mail Function
        */
        $this->sendProductMail($wholedata, $sellerId, $editFlag);

        $returnArr['product_id'] = $mageProductId;
        $returnArr['row_id'] = $catalogProduct->getRowId();

        /*
        * Create duplicate product
        */
        if (isset($wholedata['back']) && $wholedata['back'] === 'duplicate') {
            // get and save product for admin store id
            $storeId = 0;
            $duplicateCatalogProduct = $this->_marketplaceProductBuilder->build($wholedata, $storeId);

            $status1 = $helper->getIsProductApproval() ?
                SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;

            $newProduct = $this->productCopier->copy($duplicateCatalogProduct);

            // setting stock quantity and status
            $this->updateStockData($newProduct->getId());

            // Reset flagstore_category: copied from original product but belongs to
            // the original seller's flagship store, invalid for the new product.
            $oldFlagstoreCategory = $duplicateCatalogProduct->getData('flagstore_category');
            if (!empty($oldFlagstoreCategory)) {
                $newProduct->setData('flagstore_category', null);
                $newProductResource = $newProduct->getResource();
                $newProductResource->saveAttribute($newProduct, 'flagstore_category');
                $categoryIds = $newProduct->getCategoryIds();
                if (is_array($categoryIds) && in_array($oldFlagstoreCategory, $categoryIds)) {
                    $categoryIds = array_values(array_diff($categoryIds, [$oldFlagstoreCategory]));
                    $newProduct->setCategoryIds($categoryIds);
                    $newProductResource->saveAttribute($newProduct, 'category_ids');
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
     * Save configurable associed prodcuts
     *
     * @param mixed[] $wholedata
     * @param int $storeId
     */
    private function saveConfigurableAssociatedProducts($wholedata, $storeId)
    {
        $configurations = [];
        if (!empty($wholedata['configurations'])) {
            $configurations = $wholedata['configurations'];
        }

        if (!empty($configurations)) {
            $configurations = $this->_variationHandler
                ->duplicateImagesForVariations($configurations);
            foreach ($configurations as $associtedProductId => $associtedProductData) {
                $associtedProduct = $this->_productRepositoryInterface
                    ->getById(
                        $associtedProductId,
                        false,
                        $storeId
                    );
                $associtedProductData = $this->_variationHandler
                    ->processMediaGallery(
                        $associtedProduct,
                        $associtedProductData
                    );
                $associtedProduct->addData($associtedProductData);
                if ($associtedProduct->hasDataChanges()) {
                    $associtedProduct->save();
                }
            }
        }
    }

    /**
     * Get categorylink management
     *
     * @return CategoryLinkManagementInterface
     */
    private function getCategoryLinkManagement()
    {
        return $this->categoryLinkManagement;
    }

    /**
     * Set Downloadable Data in Product Model.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param array $wholedata
     *
     * @return \Magento\Catalog\Model\Product
     */
    private function buildConfigurableProduct($catalogProduct, $wholedata)
    {
        $associatedProductIds = [];
        if (!empty($wholedata['attributes'])) {
            $requestProductData = $wholedata['product'];
            $attributes = $wholedata['attributes'];
            $setId = $wholedata['set'];
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
                $samples[] = $this->sampleBuilder->setData(
                    $sampleData
                )->build(
                    $this->getSampleFactory()->create()
                );
            }
        }
        return $samples;
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
                    $this->getLinkFactory()->create()
                );
            }
        }
        return $links;
    }

    /**
     * Notify seller when image was not deleted in specific case.
     *
     * @param array $wholedata
     * @param int $mageProductId
     * @return void
     */
    private function handleImageRemoveError($wholedata, $mageProductId)
    {
        if (isset($wholedata['product']['media_gallery']['images'])) {
            $removedImagesCount = 0;
            foreach ($wholedata['product']['media_gallery']['images'] as $image) {
                if (!empty($image['removed'])) {
                    $removedImagesCount++;
                }
            }
            if ($removedImagesCount) {
                $expectedImagesCount = count($wholedata['product']['media_gallery']['images']) - $removedImagesCount;
                $catalogProduct = $this->_productRepositoryInterface->getById($mageProductId);
                if ($expectedImagesCount != count($catalogProduct->getMediaGallery('images'))) {
                    $this->_messageManager->addNotice(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
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
     * @param array  $data
     * @param string $sellerId
     * @param bool   $editFlag
     */
    private function sendProductMail($data, $sellerId, $editFlag = null)
    {
        $helper = $this->_marketplaceHelperData;

        $customer = $this->customerModel->create()->load($sellerId);

        $sellerName = $customer->getFirstname().' '.$customer->getLastname();
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
            || ($editFlag && $helper->getIsProductEditApproval() == 1)) {
            $this->mpEmailHelper->sendNewProductMail(
                $emailTempVariables,
                $senderInfo,
                $receiverInfo,
                $editFlag
            );
        }
    }

    /**
     * Manage prodcut stock
     *
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageSellerProductStock($requestProductData)
    {
        if ($requestProductData) {
            if (isset($requestProductData['quantity_and_stock_status']['qty'])) {
                if ($requestProductData['quantity_and_stock_status']['qty'] < 0) {
                    $requestProductData['quantity_and_stock_status']['qty'] = abs(
                        $requestProductData['quantity_and_stock_status']['qty']
                    );
                    $requestProductData['stock_data']['qty'] = $requestProductData['quantity_and_stock_status']['qty'];
                }
            }
            $stockData = isset($requestProductData['stock_data']) ?
                $requestProductData['stock_data'] : [];
            if (isset($stockData['qty']) && (double) $stockData['qty'] > 99999999.9999) {
                $stockData['qty'] = 99999999.9999;
            }
            if (isset($stockData['min_qty']) && (int) $stockData['min_qty'] < 0) {
                $stockData['min_qty'] = 0;
            }
            if (!isset($stockData['use_config_manage_stock'])) {
                $stockData['use_config_manage_stock'] = 0;
            }
            if ($stockData['use_config_manage_stock'] == 1 && !isset($stockData['manage_stock'])) {
                $stockData['manage_stock'] = $this->stockConfiguration
                    ->getManageStock();
            }
            if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
                $stockData['is_decimal_divided'] = 0;
            }
            $requestProductData['stock_data'] = $stockData;
        }
        return $requestProductData;
    }

    /**
     * Product initialize function before saving.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param array $requestData
     *
     * @return \Magento\Catalog\Model\Product
     */
    private function productInitialize(\Magento\Catalog\Model\Product $catalogProduct, $requestData)
    {
        $helper = $this->_marketplaceHelperData;
        $requestProductData = $requestData['product'];
        unset($requestProductData['custom_attributes']);
        unset($requestProductData['extension_attributes']);

        /*
        * Manage seller product Stock data
        */
        $requestProductData = $this->manageSellerProductStock($requestProductData);

        $requestProductData = $this->normalizeProductData($requestProductData);

        if (!empty($requestProductData['is_downloadable'])) {
            $requestProductData['product_has_weight'] = 0;
        }

        $requestProductData = $this->manageProductCategoryWebsiteData($requestProductData);

        $wasLockedMedia = false;
        if ($catalogProduct->isLockedAttribute('media')) {
            $catalogProduct->unlockAttribute('media');
            $wasLockedMedia = true;
        }

        $requestProductData = $this->manageProductDateTimeFilter($catalogProduct, $requestProductData);

        if (isset($requestProductData['options'])) {
            $productOptions = $requestProductData['options'];
            unset($requestProductData['options']);
        } else {
            $productOptions = [];
        }

        $catalogProduct->addData($requestProductData);

        if ($wasLockedMedia) {
            $catalogProduct->lockAttribute('media');
        }

        if ($helper->getSingleStoreModeStatus()) {
            $catalogProduct->setWebsiteIds([$helper->getWebsiteId()]);
        }

        /*
         * Check for "Use Default Value" field value
         */
        $catalogProduct = $this->manageProductForDefaultAttribute($catalogProduct, $requestData);

        /*
         * Set Downloadable links if available
         */
        $catalogProduct = $this->manageProductDownloadableData($catalogProduct, $requestData);

        /*
         * Set Product options to product if exist
         */
        $catalogProduct = $this->manageProductOptionData($catalogProduct, $productOptions);

        /*
         * Set Product Custom options status to product
         */
        if (empty($requestData['affect_product_custom_options'])) {
            $requestData['affect_product_custom_options'] = '';
        }

        $catalogProduct->setCanSaveCustomOptions(
            (bool) $requestData['affect_product_custom_options']
            && !$catalogProduct->getOptionsReadonly()
        );

        return $catalogProduct;
    }

    /**
     * Manage product date time filter data
     *
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageProductDateTimeFilter($catalogProduct, $requestProductData)
    {
        $dateFieldFilters = [];
        $attributes = $catalogProduct->getAttributes();
        foreach ($attributes as $attrKey => $attribute) {
            if ($attribute->getBackend()->getType() == 'datetime') {
                if (array_key_exists($attrKey, $requestProductData) && $requestProductData[$attrKey]!='') {
                    $dateFieldFilters[$attrKey] = $this->_dateFilter;
                }
            }
        }
        $inputFilter = new FilterInput(
            $dateFieldFilters,
            [],
            $requestProductData
        );
        $requestProductData = $inputFilter->getUnescaped();
        return $requestProductData;
    }


    /**
     * Manage prodcut category data
     *
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageProductCategoryWebsiteData($requestProductData)
    {
        foreach (['category_ids', 'website_ids'] as $field) {
            if (!isset($requestProductData[$field])) {
                $requestProductData[$field] = [
                    0=>$this->_marketplaceHelperData->getRootCategoryIdByStoreId()
                ];
            }
        }
        foreach ($requestProductData['website_ids'] as $websiteId => $checkboxValue) {
            if (!$checkboxValue) {
                unset($requestProductData['website_ids'][$websiteId]);
            }
        }
        return $requestProductData;
    }

    /**
     * Internal normalization
     *
     * @param array $requestProductData
     *
     * @return array
     */
    private function normalizeProductData(array $requestProductData)
    {
        foreach ($requestProductData as $key => $value) {
            if (is_scalar($value)) {
                if ($value === 'true') {
                    $requestProductData[$key] = '1';
                } elseif ($value === 'false') {
                    $requestProductData[$key] = '0';
                }
            } elseif (is_array($value)) {
                $requestProductData[$key] = $this->normalizeProductData($value);
            }
        }

        return $requestProductData;
    }

    /**
     * Manage default attribute for product
     *
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param array $requestData
     *
     * @return Magento/Catalog/Model/Product
     */
    private function manageProductForDefaultAttribute($catalogProduct, $requestData)
    {
        if (!empty($requestData['use_default'])) {
            foreach ($requestData['use_default'] as $attributeCode => $useDefaultState) {
                if ($useDefaultState) {
                    $catalogProduct->setData($attributeCode, null);
                    if ($catalogProduct->hasData('use_config_'.$attributeCode)) {
                        $catalogProduct->setData('use_config_'.$attributeCode, false);
                    }
                }
            }
        }
        return $catalogProduct;
    }

    /**
     * Manage downloadable data
     *
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param array $requestData
     *
     * @return Magento/Catalog/Model/Product
     */
    private function manageProductDownloadableData($catalogProduct, $requestData)
    {
        if (!empty($requestData['links'])) {
            $links = $this->_linkResolver->getLinks();

            $catalogProduct->setProductLinks([]);

            $catalogProduct = $this->_productLinks->initializeLinks($catalogProduct, $links);
            $productLinks = $catalogProduct->getProductLinks();

            $linkTypes = [
                'related' => $catalogProduct->getRelatedReadonly(),
                'upsell' => $catalogProduct->getUpsellReadonly(),
                'crosssell' => $catalogProduct->getCrosssellReadonly()
            ];
            foreach ($linkTypes as $linkType => $readonly) {
                if (isset($links[$linkType]) && !$readonly) {
                    foreach ((array) $links[$linkType] as $linkData) {
                        if (empty($linkData['id'])) {
                            continue;
                        }

                        $linkProduct = $this->_productRepositoryInterface->getById($linkData['id']);
                        $link = $this->_productLinkFactory->create();
                        $link->setSku($catalogProduct->getSku())
                            ->setLinkedProductSku($linkProduct->getSku())
                            ->setLinkType($linkType)
                            ->setPosition(isset($linkData['position']) ? (int)$linkData['position'] : 0);
                        $productLinks[] = $link;
                    }
                }
            }
            $catalogProduct->setProductLinks($productLinks);
        } else {
            $catalogProduct->setProductLinks([]);
        }

        return $catalogProduct;
    }

    /**
     * @param $data
     * @param $product
     * @param $starTime
     * @param $endTime
     * @return bool
     */
    public function updateProductStaging($product, $starTime, $endTime){
        try {
            $schedule = $this->update;
            $schedule->setName("Import Update #");
            $schedule->setStartTime($starTime);
            $schedule->setEndTime($endTime);
            $stagingRepo = $this->updateRepository->save($schedule);
            $this->versionManager->setCurrentVersionId($stagingRepo->getId());
//            if(is_array($data) && !empty($data)){
//                $skipRow = ['sku', '_attribute_set', 'end_time', 'start_time'];
//                foreach ($data as $key => $value){
//                    if(in_array($key, $skipRow)){
//                        continue;
//                    }
//                    if(strtolower($value) == 'yes'){
//                        $value = 1;
//                    }else if(strtolower($value) == 'no'){
//                        $value = 0;
//                    }
//                    $product->setData($key, $value);
//                }
//            }
            $this->productStaging->schedule($product, $stagingRepo->getId());
        }catch (\Exception $exception){
            return false;
        }
        return true;
    }

    public function isCheckUpdateProductStaging($product, $profile){
        if ($profile) {
            $uploadedFileRowData = $this->_serializerInterface->unserialize($profile->getDataRow());
            $keys = array_values($uploadedFileRowData[0]);
            if(in_array(self::START_TIME, $keys) && in_array(self::END_TIME, $keys)){
                if (!array_key_exists(1, $uploadedFileRowData)) {
                    return false;
                }
                $starTime = $uploadedFileRowData[1][array_search(self::START_TIME, $keys)];
                $endTime = $uploadedFileRowData[1][array_search(self::END_TIME, $keys)];
                return $this->updateProductStaging($product, $starTime, $endTime);
            }
        }
        return false;
    }
}
