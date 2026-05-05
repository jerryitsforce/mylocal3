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

namespace Branch8\MarketplaceProduct\Controller\Product;

use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Helper\Images;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\PostSaveProcessor\Composite;
use Branch8\MarketplaceProduct\Model\Product\StoreChangedData;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceStaging\Helper\Data;
use Branch8\MarketplaceStaging\Helper\Variation;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as ReportHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Webkul\Marketplace\Controller\Product\SaveProduct;
use Webkul\Marketplace\Helper\Data as HelperData;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterfaceFactory;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Webkul\SellerSubAccount\Helper\Data as SubAccountHelper;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

/**
 * Webkul Marketplace Product Save Controller.
 */
class Save extends \Webkul\Marketplace\Controller\Product\Save
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @var SaveProduct
     */
    protected $_saveProduct;

    /**
     * @var ProductResourceModel
     */
    protected $_productResourceModel;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var StoreChangedData
     */
    protected StoreChangedData $storeChangedData;

    /**
     * @var \Branch8\MarketplaceStaging\Helper\Data
     */
    protected $helperStaging;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var ProductTempDataInterfaceFactory
     */
    protected $productTempDataFactory;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var \Branch8\Catalog\Helper\Data
     */
    protected $b8CatalogHelper;

    /**
     * @var \Branch8\MarketplaceStaging\Helper\Variation
     */
    protected $variationaHelper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $_preorderHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var \Webkul\SellerSubAccount\Helper\Data
     */
    protected \Webkul\SellerSubAccount\Helper\Data $sellerSubAccountHelper;

    /**
     * @var \Branch8\MarketplaceProduct\Helper\Images
     */
    protected \Branch8\MarketplaceProduct\Helper\Images $helperImages;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     * @since 100.0.3
     */
    protected $productUrl;

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
     * @var Json
     */
    private $serializer;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var ProductCopyTracking
     */
    private ProductCopyTracking $productCopyTracking;

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
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;
    private Composite $postProcessorComposite;
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param SaveProduct $saveProduct
     * @param ProductResourceModel $productResourceModel
     * @param StoreChangedData $storeChangedData
     * @param Data $helperStaging
     * @param Variation $variationaHelper
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param TimezoneInterface $timezoneInterface
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param ProductTempDataInterfaceFactory $productTempDataFactory
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param \Branch8\Catalog\Helper\Data $b8CatalogHelper
     * @param ResourceConnection $resourceConnection
     * @param SubAccountHelper $sellerSubAccountHelper
     * @param Images $helperImages
     * @param Registry $registry
     * @param ProductCopyTracking $productCopyTracking
     * @param SubAccountHelper $subAccountHelper
     * @param ReportHelper $reportHelper
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     * @param Url $productUrl
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param Composite $postProcessorComposite
     * @param HelperData|null $helper
     * @param DataPersistorInterface|null $dataPersistor
     * @param ProductFactory|null $productFactory
     * @param Json|null $serializer
     */
    public function __construct(
        Context                                   $context,
        CustomerSession                           $customerSession,
        FormKeyValidator                          $formKeyValidator,
        SaveProduct                               $saveProduct,
        ProductResourceModel                      $productResourceModel,
        StoreChangedData                          $storeChangedData,
        \Branch8\MarketplaceStaging\Helper\Data   $helperStaging,
        Variation                                 $variationaHelper,
        \Webkul\MarketplacePreorder\Helper\Data   $preorderHelper,
        TimezoneInterface                         $timezoneInterface,
        ProductTempDataRepositoryInterface        $productTempDataRepository,
        ProductTempDataInterfaceFactory           $productTempDataFactory,
        ProductVersionCollectionFactory           $productVersionCollectionFactory,
        \Branch8\Catalog\Helper\Data              $b8CatalogHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Webkul\SellerSubAccount\Helper\Data      $sellerSubAccountHelper,
        \Branch8\MarketplaceProduct\Helper\Images $helperImages,
        Registry                                  $registry,
        ProductCopyTracking                       $productCopyTracking,
        SubAccountHelper                          $subAccountHelper,
        ReportHelper                              $reportHelper,
        ProductChangeLogInterfaceFactory          $productChangeLogFactory,
        ProductChangeLogRepositoryInterface       $productChangeLogRepository,
        \Magento\Catalog\Model\Product\Url        $productUrl,
        MarketplaceProductManagement              $marketplaceProductManagement,
        ProductVersionRepositoryInterface         $productVersionRepository,
        GetProductLogEntryByProductId             $getProductLogEntryByProductId,
        GetSalableQuantityDataBySku               $getSalableQuantityDataBySku,
        Composite                                 $postProcessorComposite,
        HelperData                                $helper = null,
        DataPersistorInterface                    $dataPersistor = null,
        ProductFactory                            $productFactory = null,
        Json                                      $serializer = null
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_saveProduct = $saveProduct;
        $this->_productResourceModel = $productResourceModel;
        $this->storeChangedData = $storeChangedData;
        $this->helper = $helper ?: ObjectManager::getInstance()->create(HelperData::class);
        $this->dataPersistor = $dataPersistor ?: ObjectManager::getInstance()->create(DataPersistorInterface::class);
        $this->_productFactory = $productFactory ?: ObjectManager::getInstance()->create(ProductFactory::class);
        parent::__construct($context, $customerSession, $formKeyValidator, $saveProduct, $productResourceModel);
        $this->helperStaging = $helperStaging;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->productTempDataFactory = $productTempDataFactory;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->b8CatalogHelper = $b8CatalogHelper;
        $this->variationaHelper = $variationaHelper;
        $this->_preorderHelper = $preorderHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->helperImages = $helperImages;
        $this->registry = $registry;
        $this->productCopyTracking = $productCopyTracking;
        $this->subAccountHelper = $subAccountHelper;
        $this->reportHelper = $reportHelper;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
        $this->productUrl = $productUrl;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->resourceConnection = $resourceConnection;
        $this->postProcessorComposite = $postProcessorComposite;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($isPartner == 1) {

            $productId = $this->getRequest()->getParam('product_id');
            $wholeData = $this->getRequest()->getParams();
            $sellerId = $helper->getCustomerId();
            /**
             * Process image base64
             */
            $wholeData['product'] = isset($wholeData['product']) ? $this->helperImages->processPostImagesInTextArea($wholeData['product'], $sellerId) : [];
            // fix/HTGO2-2765-The-variant-product-enable-disable-feature-in-the-seller-backend
            $wholeData['product'] = $this->adjustProductDataForNewProduct($wholeData['product']);
            /**
             * Validate Preservation status
             */
            $conn = $this->resourceConnection->getConnection();
            $sellerPreservatioStatusQuery = $conn->select()
                ->from(['mp_user' => 'marketplace_userdata'], ['preservation_status'])
                ->where('seller_id = ?', (int)$sellerId);
            $sellerPreservationStatus = $conn->fetchOne($sellerPreservatioStatusQuery);
            $sellerPreservationStatusArr = explode(',', (string)$sellerPreservationStatus);
            $productType = $this->getRequest()->getParam('type');
            if (
                !(
                    $productType == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
                    $productType == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE ||
                    (
                        $productType == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD &&
                        $wholeData['product']['giftcard_type'] == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL
                    )
                )
                &&
                (
                    !isset($wholeData['product']['preservation_status']) || !in_array($wholeData['product']['preservation_status'], $sellerPreservationStatusArr)
                )
            ) {
                $this->messageManager->addErrorMessage(__('Your Preservation status value is not allowed, please contact Administrator.'));
                if (!empty($productId)) {
                    return $resultRedirect->setPath(
                        'marketplace/product/edit',
                        [
                            'id' => $productId,
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    );
                } else {
                    return $resultRedirect->setPath(
                        'marketplace/product/create',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            }

            if (!empty($productId)) {
                $productObj = $this->_productFactory->create()->load($productId);
                if (
                    isset($wholeData['type'])
                    && $wholeData['type'] == 'configurable'
                    && $productObj->getTypeId() == 'simple'
                ) {
                    $productObj->setTypeId('configurable');
                    $productObj->save();
                }
            }
            if (isset($wholeData['product']['use_config_is_returnable']) && $wholeData['product']['use_config_is_returnable']) {
                unset($wholeData['product']['use_config_is_returnable']);
                //                unset($wholeData['product']['is_returnable']);
            }
            try {
                $returnArr = [];
                if ($this->getRequest()->isPost()) {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $resultRedirect->setPath(
                            'marketplace/product/create',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                    /**
                     * @TODO will refactor code when having chance
                     */
                    $wholeData = $this->postProcessorComposite->process($this->getRequest(), (int)$productId, $wholeData);
                    list($errors, $wholeData) = $this->validatePost($wholeData);
                    if (empty($errors)) {
                        $productTempData = null;
                        if ($productId) {
                            try {
                                $productTempData = $this->productTempDataRepository->get((int)$productId);
                            } catch (\Exception $e) {
                                $productTempData = null;
                            }
                        }
                        $tempId = $this->getRequest()->getParam('temp_id');
                        if (!$productTempData && $tempId) {
                            try {
                                $productTempData = $this->productTempDataRepository->getById((int)$tempId);
                            } catch (\Exception $e) {
                                $productTempData = null;
                            }
                        }

                        $isSaveProductChangeLog = false;
                        $productChangeLog = $this->productChangeLogFactory->create();
                        $resolvedSellerId = (int)$sellerId;
                        if ($this->subAccountHelper->isSubAccount()) {
                            $subAccountOwnerId = (int)$this->subAccountHelper->getCustomerId();
                            if ($subAccountOwnerId > 0) {
                                $resolvedSellerId = $subAccountOwnerId;
                            }
                        }
                        $productChangeLog->setUserType(UserType::TYPE_SELLER)
                            ->setUserId($resolvedSellerId);

                        if (isset($productObj)) {
                            $productBeforeData = $productObj->getData();
                            $links = $productObj->getProductLinks();
                            if (empty($links)) {
                                $productLinks = ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []];
                            } else {
                                $productLinks = $this->reportHelper->prepareProductLinks($productObj, $links);
                            }
                            $productBeforeData += $productLinks;
                            $productBefore = $this->reportHelper->adjustProductData($productBeforeData);
                            $productChangeLog->setBeforeValues($productBefore);
                            $isSaveProductChangeLog = true;
                        }

                        $productData = $wholeData['product'];
                        $productData['seller_id'] = $helper->getCustomerId();
                        $productData['type_id'] = $wholeData['type'];
                        $this->b8CatalogHelper->warningCommmisionRate($productData);

                        $productPostData = ['status' => $wholeData['status'] ?? Status::STATUS_DISABLED] + $productData;
                        if (isset($wholeData['links'])) {
                            $productPostData += $wholeData['links'];
                        }
                        $productPostData = $this->reportHelper->preparePostData($productPostData);
                        $productChangeLog->setPostData($productPostData);
                        if (isset($wholeData['back']) && ($wholeData['back'] === 'draft' || $wholeData['back'] === 'draft-duplicate')) {
                            $productChangeLog->setAction((string)$wholeData['back']);
                            if (!isset($wholeData['product']['shipping_method'])) {
                                $wholeData['product']['shipping_method'] = [];
                            }
                            //check min commission and avg commission

                            //end warning commission
                            $productVersionCheck = $this->productVersionCollectionFactory->create();
                            $productVersionCheck->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('status', 0);
                            $productVersionCheck->getSelect()->where(
                                "(created_from!='" . CreatedFrom::CREATED_FROM_SCHEDULE . "' AND created_from!='" . CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED . "')"
                            );
                            if ($productVersionCheck->getSize() > 0) {
                                throw new LocalizedException(
                                    __('This product is reviewing. Please wait for the result.')
                                );
                            }
                            $productTempId = '';
                            if ($productTempData) {
                                $status = (isset($wholeData['status']) && $wholeData['status']) ? (int)$wholeData['status'] : SellerProduct::STATUS_ENABLED;
                                $productTempData->setStatus($status);
                                $productTempData->setThumbnail($wholeData['product']['thumbnail'] ?? '');
                                $productTempData->setName($wholeData['product']['name'] ?? '');
                                $productTempData->setType($wholeData['type'] ?? '');
                                $productTempData->setSku($wholeData['product']['sku'] ?? '');
                                $productTempData->setQuantity(isset($wholeData['product']['quantity_and_stock_status']['qty']) ? (float)$wholeData['product']['quantity_and_stock_status']['qty'] : (float)'0');
                                $productTempData->setCost(isset($wholeData['product']['cost']) ? (float)$wholeData['product']['cost'] : (float)'0');
                                $productTempData->setPrice(isset($wholeData['product']['price']) ? (float)$wholeData['product']['price'] : (float)'0');
                                $productTempData->setSpecialPrice(isset($wholeData['product']['special_price']) ? (float)$wholeData['product']['special_price'] : (float)'0');
                                $productTempData->setInformation($this->serializer->serialize($wholeData));
                                $this->productTempDataRepository->save($productTempData);
                            } else {
                                $status = (isset($wholeData['status']) && $wholeData['status']) ? (int)$wholeData['status'] : SellerProduct::STATUS_ENABLED;
                                $productTempData = $this->productTempDataFactory->create();
                                $productTempData->setProductId((int)$productId ?? 0);
                                $productTempData->setSellerId((int)$sellerId);
                                $productTempData->setStatus($status);
                                $productTempData->setThumbnail($wholeData['product']['thumbnail'] ?? '');
                                $productTempData->setName($wholeData['product']['name'] ?? '');
                                $productTempData->setType($wholeData['type'] ?? '');
                                $productTempData->setSku($wholeData['product']['sku'] ?? '');
                                $productTempData->setQuantity(isset($wholeData['product']['quantity_and_stock_status']['qty']) ? (float)$wholeData['product']['quantity_and_stock_status']['qty'] : (float)'0');
                                $productTempData->setCost(isset($wholeData['product']['cost']) ? (float)$wholeData['product']['cost'] : (float)'0');
                                $productTempData->setPrice(isset($wholeData['product']['price']) ? (float)$wholeData['product']['price'] : (float)'0');
                                $productTempData->setSpecialPrice(isset($wholeData['product']['special_price']) ? (float)$wholeData['product']['special_price'] : (float)'0');
                                $productTempData->setInformation($this->serializer->serialize($wholeData));
                                $this->productTempDataRepository->save($productTempData);
                                $productTemp = $this->productTempDataFactory->create()->load($wholeData['product']['sku'], 'sku');
                                $productTempId = $productTemp->getId();
                            }
                            $duplicateId = '';
                            if ($wholeData['back'] === 'draft-duplicate') {
                                $dataSku = $productTempData->getSku();
                                $sku = '';
                                if (str_contains($dataSku, 'HOTAI') && str_contains($dataSku, '-')) {
                                    $arSku = explode('-', $dataSku);
                                    $sku = $arSku[1];
                                }
                                $name = $wholeData['product']['name'] ?? '';
                                $sku = $sku ? 'HOTAI' . time() . '-' . $sku : 'HOTAI' . time() . '-' . $name;
                                $wholeData['product']['sku'] = $sku;
                                $wholeData['product']['url_key'] = $this->productUrl->formatUrlKey($sku);
                                if (!empty($wholeData['product']['media_gallery']['images'])) {
                                    foreach ($wholeData['product']['media_gallery']['images'] as $key => $image) {
                                        $file = $image['file'] ?? '';
                                        if ($file) {
                                            $newFile = $this->helperImages->copyImage($file);
                                            $wholeData['product']['media_gallery']['images'][$key]['file'] = $newFile;
                                            if (isset($wholeData['product']['image']) && $wholeData['product']['image'] == $file) {
                                                $wholeData['product']['image'] = $newFile;
                                            }
                                            if (isset($wholeData['product']['small_image']) && $wholeData['product']['small_image'] == $file) {
                                                $wholeData['product']['small_image'] = $newFile;
                                            }
                                            if (isset($wholeData['product']['thumbnail']) && $wholeData['product']['thumbnail'] == $file) {
                                                $wholeData['product']['thumbnail'] = $newFile;
                                            }
                                            if (isset($wholeData['product']['dpa_image']) && $wholeData['product']['dpa_image'] == $file) {
                                                $wholeData['product']['dpa_image'] = $newFile;
                                            }
                                            if (isset($wholeData['product']['swatch_image']) && $wholeData['product']['swatch_image'] == $file) {
                                                $wholeData['product']['swatch_image'] = $newFile;
                                            }
                                        }
                                    }
                                }
                                if (!empty($wholeData['product']['options'])) {
                                    foreach ($wholeData['product']['options'] as $k => $option) {
                                        $wholeData['product']['options'][$k] = $option;
                                        $wholeData['product']['options'][$k]['product_id'] = '';
                                        $wholeData['product']['options'][$k]['option_id'] = '';
                                        $wholeData['product']['options'][$k]['record_id'] = $k;
                                        if (in_array($option['type'], ['drop_down', 'radio', 'checkbox', 'multiple'])) {
                                            foreach ($option['values'] as $k1 => $optionValue) {
                                                $wholeData['product']['options'][$k]['values'][$k1] = $optionValue;
                                                $wholeData['product']['options'][$k]['values'][$k1]['option_type_id'] = '';
                                                $wholeData['product']['options'][$k]['values'][$k1]['option_id'] = '';
                                            }
                                        }
                                    }
                                }
                                if ($name) {
                                    $name = 'copy-' . $name;
                                    $wholeData['product']['name'] = $name;
                                }
                                $wholeData['id'] = 0;
                                $wholeData['product_id'] = 0;
                                $status = (isset($wholeData['status']) && $wholeData['status']) ? (int)$wholeData['status'] : SellerProduct::STATUS_ENABLED;
                                $productTempDuplicateData = $this->productTempDataFactory->create();
                                $productTempDuplicateData->setProductId(0);
                                $productTempDuplicateData->setSellerId((int)$sellerId);
                                $productTempDuplicateData->setStatus($status);
                                $productTempDuplicateData->setThumbnail($wholeData['product']['thumbnail'] ?? '');
                                $productTempDuplicateData->setName($wholeData['product']['name'] ?? '');
                                $productTempDuplicateData->setType($wholeData['type'] ?? '');
                                $productTempDuplicateData->setSku($sku);
                                $productTempDuplicateData->setQuantity(isset($wholeData['product']['quantity_and_stock_status']['qty']) ? (float)$wholeData['product']['quantity_and_stock_status']['qty'] : (float)'0');
                                $productTempDuplicateData->setCost(isset($wholeData['product']['cost']) ? (float)$wholeData['product']['cost'] : (float)'0');
                                $productTempDuplicateData->setPrice(isset($wholeData['product']['price']) ? (float)$wholeData['product']['price'] : (float)'0');
                                $productTempDuplicateData->setSpecialPrice(isset($wholeData['product']['special_price']) ? (float)$wholeData['product']['special_price'] : (float)'0');
                                $productTempDuplicateData->setInformation($this->serializer->serialize($wholeData));
                                $this->productTempDataRepository->save($productTempDuplicateData);
                                $productTemp = $this->productTempDataFactory->create()->load($sku, 'sku');
                                $duplicateId = $productTemp->getId();
                            }
                            $isTracked = $this->productCopyTracking->isTracked((int)$productId);
                            if ($isTracked) {
                                $this->productCopyTracking->markAsDraft((int)$productId);
                            }
                            //$this->variationaHelper->saveVariation($productId, $wholeData);
                            $this->messageManager->addSuccessMessage(__('Your product has been successfully saved'));
                            $this->getDataPersistor()->clear('seller_catalog_product');

                            if (isset($productObj) && $isSaveProductChangeLog && !$this->registry->registry('productChangeLogAdded')) {
                                $productObj = $productObj->load($productId);
                                $productAfterData = $productObj->getData();
                                $links = $productObj->getProductLinks();
                                $productLinks = (empty($links)) ? ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []]
                                    : $this->reportHelper->prepareProductLinks($productObj, $links);
                                $productAfterData += $productLinks;
                                $productAfter = $this->reportHelper->adjustProductData($productAfterData);
                                $productChangeLog->setProductId((int)$productId)
                                    ->setAfterValues($productAfter);
                                try {
                                    $this->productChangeLogRepository->save($productChangeLog);
                                } catch (CouldNotSaveException $e) {
                                    $this->helper->logDataInLogger('Controller_Product_Save execute : ' . $e->getMessage());
                                }
                            }

                            if ($duplicateId) {
                                return $resultRedirect->setPath(
                                    'marketplace/product/edit',
                                    [
                                        'temp_id' => $duplicateId,
                                        '_secure' => $this->getRequest()->isSecure()
                                    ]
                                );
                            } elseif ($productId) {
                                return $resultRedirect->setPath(
                                    'marketplace/product/edit',
                                    [
                                        'id' => $productId,
                                        '_secure' => $this->getRequest()->isSecure()
                                    ]
                                );
                            } elseif ($productTempData->getId()) {
                                return $resultRedirect->setPath(
                                    'marketplace/product/edit',
                                    [
                                        'temp_id' => $productTempData->getId(),
                                        '_secure' => $this->getRequest()->isSecure()
                                    ]
                                );
                            } elseif ($productTempId) {
                                return $resultRedirect->setPath(
                                    'marketplace/product/edit',
                                    [
                                        'temp_id' => $productTempId,
                                        '_secure' => $this->getRequest()->isSecure()
                                    ]
                                );
                            } else {
                                return $resultRedirect->setPath(
                                    'marketplace/product/productlist',
                                    ['_secure' => $this->getRequest()->isSecure()]
                                );
                            }
                        } else {
                            $productChangeLog->setAction('save');
                            if ($productTempData) {
                                //Get temp Variation for approve product
                                $productTempVariation = $this->serializer->unserialize($productTempData->getInformation(), true);
                                if (!array_key_exists('wk_manage_variation', $wholeData['product']) && array_key_exists('wk_manage_variation', $productTempVariation['product'])) {
                                    $wholeData['product']['wk_manage_variation'] = $productTempVariation['product']['wk_manage_variation'];
                                }
                                if (!array_key_exists('wk_manage_variation', $wholeData['product']) && array_key_exists('wk_manage_swatch', $productTempVariation['product'])) {
                                    $wholeData['product']['wk_manage_swatch'] = $productTempVariation['product']['wk_manage_swatch'];
                                }
                            }
                            $sellerId = (int)$this->_getSession()->getCustomerId();
                            if ($this->sellerSubAccountHelper->isSubAccount()) {
                                $sellerId = $this->sellerSubAccountHelper->getSubAccountSellerId();
                            }
                            if (!isset($wholeData['product']['shipping_method'])) {
                                $wholeData['product']['shipping_method'] = [];
                            }
                            if (!$productId || !$helper->getIsProductEditApproval()) {
                                // Handle the case of adding a new product
                                if (!$productId && isset($wholeData['product']['shipping_method']) && is_array($wholeData['product']['shipping_method'])) {
                                    $wholeData['product']['shipping_method'] = implode(',', $wholeData['product']['shipping_method']);
                                }
                                $returnArr = $this->_saveProduct->saveProductData($sellerId, $wholeData);
                                $this->storeChangedData->execute($wholeData, (int)$returnArr['product_id'], $sellerId, CreatedFrom::CREATED_FROM_SELLER, true);
                                $productId = $returnArr['product_id'];
                                $this->variationaHelper->saveVariation($productId, $wholeData);

                                if (isset($productObj) && $isSaveProductChangeLog && !$this->registry->registry('productChangeLogAdded')) {
                                    $productObj = $productObj->load($productId);
                                    $productAfterData = $productObj->getData();
                                    $links = $productObj->getProductLinks();
                                    $productLinks = (empty($links)) ? ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []]
                                        : $this->reportHelper->prepareProductLinks($productObj, $links);
                                    $productAfterData += $productLinks;
                                    $productAfter = $this->reportHelper->adjustProductData($productAfterData);
                                    $productChangeLog->setProductId((int)$productId)
                                        ->setAfterValues($productAfter);
                                    try {
                                        $this->productChangeLogRepository->save($productChangeLog);
                                    } catch (CouldNotSaveException $e) {
                                        $this->helper->logDataInLogger('Controller_Product_Save execute : ' . $e->getMessage());
                                    }
                                }
                            } else {
                                try {
                                    $product = $this->marketplaceProductManagement->getByCode('mageproduct_id', (int)$productId);
                                    if ($product->getData('status') != Product::STATUS_PENDING) {
                                        $logEntry = $this->getProductLogEntryByProductId->execute($productId);
                                        if (!empty($logEntry['id'])) {
                                            $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                                            $productVersion->setStatus(Product::STATUS_DISABLED);
                                            $this->productVersionRepository->save($productVersion);
                                        }
                                    }
                                } catch (NoSuchEntityException $e) {
                                }
                                // Handle the case of updating a product
                                if ($product->getNewNeedApprove()) {
                                    $returnArr = $this->storeChangedData->execute($wholeData, (int)$productId, $sellerId, CreatedFrom::CREATED_FROM_SELLER, false, true);
                                } else {
                                    $returnArr = $this->storeChangedData->execute($wholeData, (int)$productId, $sellerId);
                                }
                                $isError = $returnArr['error'] ?? false;
                                if ($isError) {
                                    $message = $returnArr['message'] ?? 'Something went wrong while saving this product.';
                                    $this->messageManager->addErrorMessage($message);
                                    if ($message == __('This product is reviewing. Please wait for the result.')) {
                                        return $resultRedirect->setPath(
                                            'marketplace/product/productlist',
                                            [
                                                '_secure' => $this->getRequest()->isSecure(),
                                            ]
                                        );
                                    }
                                    return $resultRedirect->setPath(
                                        'marketplace/product/edit',
                                        [
                                            'id' => $productId,
                                            '_secure' => $this->getRequest()->isSecure(),
                                        ]
                                    );
                                } else {
                                    $this->productCopyTracking->delete((int)$productId);
                                    if ($productTempData) $this->productTempDataRepository->delete($productTempData);
                                    $message = __('Your product has been saved successfully. An administrator will review and approve it if necessary.');
                                    $this->messageManager->addSuccessMessage($message);
                                    if (isset($productObj) && $isSaveProductChangeLog && !$this->registry->registry('productChangeLogAdded')) {
                                        $productObj = $productObj->load($productId);
                                        $productAfterData = $productObj->getData();
                                        $links = $productObj->getProductLinks();
                                        $productLinks = (empty($links)) ? ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []]
                                            : $this->reportHelper->prepareProductLinks($productObj, $links);
                                        $productAfterData += $productLinks;
                                        $productAfter = $this->reportHelper->adjustProductData($productAfterData);
                                        $productChangeLog->setProductId((int)$productId)
                                            ->setAfterValues($productAfter);
                                        try {
                                            $this->productChangeLogRepository->save($productChangeLog);
                                        } catch (CouldNotSaveException $e) {
                                            $this->helper->logDataInLogger('Controller_Product_Save execute : ' . $e->getMessage());
                                        }
                                    }
                                    if (isset($returnArr['product_id'])) {
                                        $productId = $returnArr['product_id'];
                                    }
                                    return $resultRedirect->setPath(
                                        'marketplace/product/productlist',
                                        [
                                            '_secure' => $this->getRequest()->isSecure(),
                                        ]
                                    );
                                }
                            }
                            $error = false;
                            if (isset($returnArr['error']) && isset($returnArr['message'])) {
                                if ($returnArr['error'] && $returnArr['message'] != '') {
                                    $error = true;
                                }
                            }
                            if ($productTempData && !$error) {
                                $this->productTempDataRepository->delete($productTempData);
                            }
                        }
                    } else {
                        foreach ($errors as $message) {
                            $this->messageManager->addErrorMessage($message);
                        }
                        $this->getDataPersistor()->set('seller_catalog_product', $wholeData);
                    }
                }
                if ($productId != '') {
                    // why need to clean all caches here ?
                    $helper->clearCache();
                    if (empty($errors)) {
                        $this->messageManager->addSuccessMessage(__('Your product has been saved successfully. An administrator will review and approve it if necessary.'));
                        $this->getDataPersistor()->clear('seller_catalog_product');
                        return $resultRedirect->setPath(
                            'marketplace/product/productlist',
                            [
                                '_secure' => $this->getRequest()->isSecure()
                            ]
                        );
                    } else {
                        return $resultRedirect->setPath(
                            'marketplace/product/edit',
                            [
                                'id' => $productId,
                                '_secure' => $this->getRequest()->isSecure(),
                            ]
                        );
                    }
                } else {
                    if (isset($returnArr['error']) && isset($returnArr['message'])) {
                        if ($returnArr['error'] && $returnArr['message'] != '') {
                            $this->messageManager->addErrorMessage($returnArr['message']);
                        }
                    }
                    $this->getDataPersistor()->set('seller_catalog_product', $wholeData);
                    if (isset($wholeData['set']) && isset($wholeData['type'])) {
                        return $resultRedirect->setPath(
                            'marketplace/product/add',
                            [
                                'set' => $wholeData['set'],
                                'type' => $wholeData['type'],
                                '_secure' => $this->getRequest()->isSecure()
                            ]
                        );
                    } else {
                        return $resultRedirect->setPath(
                            'marketplace/product/productlist',
                            [
                                '_secure' => $this->getRequest()->isSecure()
                            ]
                        );
                    }
                }
            } catch (LocalizedException $e) {
                $this->helper->logDataInLogger('Controller_Product_Save execute : ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->getDataPersistor()->set('seller_catalog_product', $wholeData);
                if ($productId) {
                    return $resultRedirect->setPath(
                        'marketplace/product/edit',
                        [
                            'id' => $productId,
                            '_secure' => $this->getRequest()->isSecure(),
                        ]
                    );
                } else {
                    return $resultRedirect->setPath(
                        'marketplace/product/add',
                        [
                            'set' => $wholeData['set'],
                            'type' => $wholeData['type'],
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->helper->logDataInLogger('Controller_Product_Save execute : ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->getDataPersistor()->set('seller_catalog_product', $wholeData);
                if ($productId) {
                    return $resultRedirect->setPath(
                        'marketplace/product/edit',
                        [
                            'id' => $productId,
                            '_secure' => $this->getRequest()->isSecure(),
                        ]
                    );
                } elseif (isset($wholeData['set']) && isset($wholeData['type'])) {
                    return $resultRedirect->setPath(
                        'marketplace/product/add',
                        [
                            'set' => $wholeData['set'],
                            'type' => $wholeData['type'],
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    );
                } else {
                    return $resultRedirect->setPath(
                        'marketplace/product/productlist',
                        [
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    );
                }
            }
        } else {
            return $resultRedirect->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }


    /**
     * Check if sku exist
     *
     * @param string $sku
     * @return string
     */
    private function checkSkuExist($sku)
    {
        try {
            $id = $this->_productResourceModel->getIdBySku($sku);
            $availability = $id ? 0 : 1;
        } catch (\Exception $e) {
            $this->helper->logDataInLogger('Controller_Product_Save checkSkuExist : ' . $e->getMessage());
            $availability = 0;
        }
        if ($availability == 0) {
            $sku = $sku . Random::getRandomNumber();
            $sku = $this->checkSkuExist($sku);
        }
        return $sku;
    }

    /**
     * Validate data
     *
     * @param array $wholeData
     * @return array
     */
    private function validatePost(&$wholeData)
    {
        $errors = [];
        $data = [];
        foreach ($wholeData['product'] as $code => $value) {
            switch ($code):
                case 'name':
                    $result = $this->nameValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Name has to be completed');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'description':
                    $result = $this->descriptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Description has to be completed');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'short_description':
                    $result = $this->descriptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'price':
                    $result = $this->priceValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Price should contain only decimal numbers');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'weight':
                    $result = $this->weightValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Weight should contain only decimal numbers');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'stock':
                    $result = $this->stockValidateFunction($value, $code, $errors, $data);
                    if ($result['error']) {
                        $errors[] = __('Product quantity should contain only decimal numbers');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'sku_type':
                    $result = $this->skuTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Sku Type has to be selected');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'sku':
                    $result = $this->skuValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Sku has to be completed');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'price_type':
                    $result = $this->priceTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Price Type has to be selected');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'weight_type':
                    $result = $this->weightTypeValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Weight Type has to be selected');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'bundle_options':
                    $result = $this->bundleOptionValidateFunction($value, $code, $data);
                    if ($result['error']) {
                        $errors[] = __('Default Title has to be completed');
                        $wholeData['product'][$code] = '';
                    } else {
                        $wholeData['product'][$code] = $result['data'][$code];
                    }
                    break;
                case 'url_key':
                    $result = $this->urlKeyValidateFunction($value, $code, $data);
                    $wholeData['product'][$code] = $result['data'][$code];
                    break;
                case 'meta_title':
                    $result = $this->metaTitleValidateFunction($value, $code, $data);
                    $wholeData['product'][$code] = $result['data'][$code];
                    break;
                case 'meta_keyword':
                    $result = $this->metaKeywordValidateFunction($value, $code, $data);
                    $wholeData['product'][$code] = $result['data'][$code];
                    break;
                case 'meta_description':
                    $result = $this->metaDiscValidateFunction($value, $code, $data);
                    $wholeData['product'][$code] = $result['data'][$code];
                    break;
                case 'mp_product_cart_limit':
                    if (!empty($value)) {
                        $result = $this->stockValidateFunction($value, $code, $errors, $data);
                        if ($result['error']) {
                            $errors[] = __('Allowed Product Cart Limit Qty should contain only decimal numbers');
                            $wholeData['product'][$code] = '';
                        } else {
                            $wholeData['product'][$code] = $result['data'][$code];
                        }
                    }
                    break;
                case 'large_item':
                    if (!empty($value)) {
                        if (!$this->helperStaging->isAllowSettingLargeItem()) {
                            unset($wholeData['product'][$code]);
                            $errors[] = __("You can't update Large Item for product.");
                        }
                    }
                    break;
                case 'search_tag':
                    $result = $this->searchTagValidate($value, $code, $data);
                    $wholeData['product'][$code] = $result['data'][$code];
                    if ($result['error']) {
                        unset($wholeData['product'][$code]);
                        $errors[] = __("Only accept comma as separator for search tag.");
                    }
                    break;
            endswitch;
        }

        $attributeOptions = $this->_preorderHelper->getPreorderAttribute('simple');
        $enabledId = -1;
        foreach ($attributeOptions as $attributeOption) {
            if ($attributeOption['label'] == 'Enable' || $attributeOption['label'] == '啟用') {
                $enabledId = $attributeOption['value'];
            }
        }
        $isPreorderMatch = 0;
        if (
            is_array($wholeData) && is_array($wholeData['product']) &&
            array_key_exists("wk_marketplace_preorder", $wholeData['product'])
            && array_key_exists("wk_marketplace_availability", $wholeData['product'])
            && $wholeData['product']['wk_marketplace_preorder'] == $enabledId
            && $wholeData['product']['preorder_mode'] == 1 /*Only Mode start-end date have to use available date*/
        ) {
            $isPreorderMatch = 1;
            $today = date('m/d/y');
            if (strtotime($wholeData['product']['wk_marketplace_availability']) < strtotime($today)) {
                $errors[] = __("Preorder Availability date should be of future");
            }
        }
        if ($isPreorderMatch) {
            if ($wholeData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE) {
                //validate start/ end date
                $startDate = $this->convertDate($wholeData['product']['preorder_start_date']);
                $endDate = $this->convertDate($wholeData['product']['preorder_end_date']);
                if (strtotime($startDate) >= strtotime($endDate)) {
                    $errors[] = __('Pre-order Start date must be less than End date.');
                }
                //validate available/ end date
                $availableDate = $this->convertDate($wholeData['product']['wk_marketplace_availability']);
                if (strtotime($endDate) > strtotime($availableDate)) {
                    $errors[] = __('Pre-order End date must be less than Available date.');
                }
            } else if ($wholeData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS) {
                $endDate = $this->convertDate($wholeData['product']['preorder_end_date']);
                if ($endDate == '') {
                    $errors[] = __('Please set End date for PreOrder X-Days mode.');
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if (strtotime($todayDate) > strtotime($endDate)) {
                    $errors[] = __('Pre-order End date must be in the future.');
                }
                if ((int)$wholeData['product']['preorder_x_days'] == 0) {
                    $errors[] = __('Pre-order XDays field must be great than 0 for XDays mode.');
                }
            } else if ($wholeData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE) {
                $endDate = $this->convertDate($wholeData['product']['preorder_end_date']);
                if ($endDate == '') {
                    $errors[] = __('Please set End date for PreOrder Specify Shipping Date mode.');
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if (strtotime($todayDate) > strtotime($endDate)) {
                    $errors[] = __('Pre-order End date must be in the future.');
                }
                $shipDate = $this->convertDate($wholeData['product']['preorder_ship_date']);
                if ($shipDate == '') {
                    $errors[] = __('Please set Ship Date for PreOrder Specify Shipping Date mode.');
                }
                if (strtotime($todayDate) > strtotime($shipDate)) {
                    $errors[] = __('Pre-order Ship date must be in the future.');
                }
            }
        }
        $transport = new DataObject(
            [
                'errors' => $errors,
                'productData' => $wholeData['product']
            ],
        );
        $this->_eventManager->dispatch(
            'marketplace_product_validate_post_before_save',
            [
                'transport' => $transport
            ]
        );
        $errors = $transport->getErrors();
        return [$errors, $wholeData];
    }

    /**
     * Validate name
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function nameValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate description
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function descriptionValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ?: $value;
            $helper = $this->helper;
            $value = $helper->validateXssString($value);
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate short description
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function shortDescValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate price
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function priceValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^\s*[+\-]?(?:\d+(?:\.\d*)?|\.\d+)\s*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate weight
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function weightValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^\s*[+\-]?(?:\d+(?:\.\d*)?|\.\d+)\s*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate stock
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function stockValidateFunction($value, $code, $data)
    {
        $error = false;
        if (!preg_match('/^([0-9])+?[0-9.]*$/', $value)) {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate sku type
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function skuTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate Sku
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function skuValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate price type
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function priceTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate weight
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function weightTypeValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate bundle options
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function bundleOptionValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
        } else {
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate meta title
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function metaTitleValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate meta keyword
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function metaKeywordValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ?: $value;
            $helper = $this->helper;
            $value = $helper->validateXssString($value);
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Validate meta descriotion
     *
     * @param string $value
     * @param string|int $code
     * @param array $data
     * @return array
     */
    private function metaDiscValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ?: $value;
            $helper = $this->helper;
            $value = $helper->validateXssString($value);
            $data[$code] = $value;
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * @param $value
     * @param $code
     * @param $data
     * @return array
     */
    private function searchTagValidate($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $data[$code] = '';
        } else {
            $value = preg_replace("/<script.*?\/script>/s", "", $value) ?: $value;
            $value = $this->helper->validateXssString($value);
            if (substr($value, -1) === ',') {
                $error = true;
                $newValue[] = $value;
            } else {
                $tags = explode(',', $value);
                $newValue = [];
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    $newValue[] = $tag;
                    if ($tag === '' || !preg_match('/^[\p{L}\p{N}_\-,% ]+$/u', $tag)) {
                        $error = true;
                    }
                }
            }
            $data[$code] = $error ? $value : join(',', $newValue);
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * Retrieve data persistor
     *
     * @return DataPersistorInterface|mixed
     */
    protected function getDataPersistor()
    {
        return $this->dataPersistor;
    }

    /**
     * UrlKeyValidateFunction function
     *
     * @param string $value
     * @param string $code
     * @param mixed[] $data
     * @return mixed[]
     */
    private function urlKeyValidateFunction($value, $code, $data)
    {
        $error = false;
        if (trim($value) == '') {
            $error = true;
            $data[$code] = '';
        } else {
            $data[$code] = strip_tags($value);
        }
        return ['error' => $error, 'data' => $data];
    }

    /**
     * @param string $date
     * @return string
     */
    private function convertDate($date)
    {
        try {
            $time = strtotime($date);
            return date('Y-m-d', $time) . ' 00:00:00';
        } catch (\Exception $e) {
            return '';
        }
    }
    public function adjustProductDataForNewProduct($productData)
    {
        if (isset($productData['options'])) {
            foreach ($productData['options'] as &$option) {
                $values = $option['values'] ?? '';
                if ($values) {
                    foreach ($values as $key => &$value) {
                        if (!isset($value['is_visible'])) {
                            $value['is_visible'] = 0;
                        }
                        $values[$key] = $value;
                    }
                }
                $option['values'] = $values;
            }
        }

        return $productData;
    }
}
