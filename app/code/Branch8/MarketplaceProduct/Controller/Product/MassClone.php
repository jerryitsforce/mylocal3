<?php
namespace Branch8\MarketplaceProduct\Controller\Product;

use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Helper\Images;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Branch8\MarketplaceStaging\Helper\Data as B8HelperData;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

/**
 * Marketplace Product Draft MassDelete controller.
 */
class MassClone extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    private static $productFields = [
        'name',
        'short_description',
        'sku',
        'virtual_product_type',
        'price',
        'special_price',
        'msrp',
        'msrp_display_actual_price_type',
        'stock_data',
        'quantity_and_stock_status',
        'visibility',
        'product_has_weight',
        'weight',
        'is_returnable',
        'use_config_is_returnable',
        'tax_class_id',
        'description',
        'url_key',
        'meta_title',
        'meta_keyword',
        'meta_description',
        'media_gallery',
        'image',
        'small_image',
        'thumbnail',
        'swatch_image',
        'dpa_image',
        'options'
    ];

    private static $skipProductFields = [
        'marketing_code',
        'flagship_store_process_seller_id',
        'product_name_sub',
        'is_hidden',
        'large_item',
        'sitemap_exclude',
        'hot_sell',
        'allow_customer_groups',
        'livesearch_categories',
        'seller_shop_name',
        'livesearch_instock',
        'hotai1_FRCD',
        'hotai1_PARTNO',
        'hotai1_PARTCUSTID',
        'hotai1_EMPRTAX',
        'dpa_image'
    ];

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var ProductTempDataInterfaceFactory
     */
    protected ProductTempDataInterfaceFactory $productTempDataFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var B8HelperData
     */
    protected $b8HelperData;

    /**
     * @var Images
     */
    protected Images $helperImages;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    public $swatchFactory;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Session $customerSession
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param ProductTempDataInterfaceFactory $productTempDataFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param HelperData $helper
     * @param B8HelperData $b8HelperData
     * @param Images $helperImages
     * @param VariationsFactory $variationsFactory
     * @param SwatchFactory $swatchFactory
     * @param CustomerUrl|null $customerUrl
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        ProductTempDataInterfaceFactory $productTempDataFactory,
        ProductCollectionFactory $productCollectionFactory,
        HelperData $helper,
        B8HelperData $b8HelperData,
        Images $helperImages,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        CustomerUrl $customerUrl = null,
        Json $serializer = null
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->productTempDataFactory = $productTempDataFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->helper = $helper;
        $this->b8HelperData = $b8HelperData;
        $this->helperImages = $helperImages;
        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->customerUrl = $customerUrl ?: ObjectManager::getInstance()
            ->create(CustomerUrl::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
        parent::__construct(
            $context
        );
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Mass delete seller products action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $collection = $this->filter->getCollection(
                    $this->_productCollectionFactory->create()
                );
                $collection->addFieldToFilter(
                    'index_seller_id',
                    $sellerId
                );
                $collection->addAttributeToSelect('*');
                $collection->addMediaGalleryData();
                $collection->addOptionsToResult();
                $total = 0;
                foreach ($collection as $product) {
                    $attSet = $product->getAttributeSetId();
                    $type = $product->getTypeId();
                    $attributes = $this->b8HelperData->getAttributesByAttributeSetId($attSet, $type);
                    $stockItem = $this->b8HelperData->getStockItem($product->getId());
                    $wholeData = [];
                    $wholeData['type'] = $type;
                    $wholeData['set'] = $attSet;
                    $wholeData['id'] = 0;
                    $wholeData['product_id'] = 0;
                    foreach (self::$productFields as $field) {
                        if ($field == 'stock_data') {
                            $wholeData['product'][$field]['manage_stock'] = $stockItem->getManageStock();
                            $wholeData['product'][$field]['use_config_manage_stock'] = $stockItem->getManageStock();
                        } elseif ($field == 'quantity_and_stock_status') {
                            $wholeData['product'][$field]['qty'] = $stockItem->getQty();
                            $wholeData['product'][$field]['is_in_stock'] = $stockItem->getIsInStock();
                        } elseif ($field == 'product_has_weight') {
                            $wholeData['product'][$field] = $product->getWeight() ? 1 : 0;
                            if ($type !== 'simple') {
                                $wholeData['product'][$field] = 0;
                            }
                        } elseif ($field == 'options' && $product->getOptions()) {
                            foreach ($product->getOptions() as $k => $option) {
                                $wholeData['product'][$field][$k] = $option->getData();
                                $wholeData['product'][$field][$k]['product_id'] = '';
                                $wholeData['product'][$field][$k]['option_id'] = '';
                                $wholeData['product'][$field][$k]['record_id'] = $k;
                                if (in_array($option->getType(), ['drop_down', 'radio', 'checkbox', 'multiple'])) {
                                    foreach ($option->getValues() as $k1 => $optionValue) {
                                        $wholeData['product'][$field][$k]['values'][$k1] = $optionValue->getData();
                                        $wholeData['product'][$field][$k]['values'][$k1]['option_type_id'] = '';
                                        $wholeData['product'][$field][$k]['values'][$k1]['option_id'] = '';
                                    }
                                }
                            }
                        } else {
                            $wholeData['product'][$field] = $product->getData($field);
                        }
                    }
                    foreach ($attributes as $attribute) {
                        $attCode = $attribute->getAttributeCode();
                        if (in_array($attCode, self::$skipProductFields) || str_contains($attCode, 'index_')) {
                            continue;
                        }
                        $wholeData['product'][$attCode] = $product->getData($attCode);
                    }
                    foreach ($product->getProductLinks() as $productLink) {
                        $wholeData['links'][$productLink->getLinkType()][$productLink->getPosition()] = $productLink->getLinkedProductSku();
                    }
                    $dataSku = $wholeData['product']['sku'] ?? '';
                    $sku = '';
                    if (str_contains($dataSku, 'HOTAI') && str_contains($dataSku, '-')) {
                        $arSku = explode('-', $dataSku);
                        $sku = $arSku[1];
                    }
                    $name = $wholeData['product']['name'] ?? '';
                    $sku = $sku ? 'HOTAI' . time() . '-' . $sku : 'HOTAI' . time() . '-' . $name;
                    $wholeData['product']['sku'] = $sku;
                    $wholeData['product']['url_key'] = $product->formatUrlKey($sku);
                    if (!empty($wholeData['product']['media_gallery']['images'])) {
                        foreach ($wholeData['product']['media_gallery']['images'] as $key => $image) {
                            $file = $image['file'] ?? '';
                            if ($file) {
                                $newFile = $this->helperImages->copyImage($file);
                                if (isset($wholeData['product']['media_gallery']['images'][$key]['row_id'])) {
                                    unset($wholeData['product']['media_gallery']['images'][$key]['row_id']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['label_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['position_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['disabled_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_provider']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_url']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_title']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_description']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_metadata']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_provider_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_url_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_title_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_description_default']);
                                    unset($wholeData['product']['media_gallery']['images'][$key]['video_metadata_default']);
                                }
                                $wholeData['product']['media_gallery']['images'][$key]['value_id'] = NULL;
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
                            }
                        }
                    }

                    if ($name) {
                        $name = 'copy-'.$name;
                        $wholeData['product']['name'] = $name;
                    }
                    $wholeData['id'] = 0;
                    $wholeData['product_id'] = 0;
                    $wholeData['org_product_id'] = $product->getRowId();
                    $status = (isset($wholeData['status']) && $wholeData['status']) ? (int) $wholeData['status'] : SellerProduct::STATUS_ENABLED;
                    $productTempDuplicateData = $this->productTempDataFactory->create();
                    $productTempDuplicateData->setProductId( 0);
                    $productTempDuplicateData->setSellerId((int) $sellerId);
                    $productTempDuplicateData->setStatus($status);
                    $productTempDuplicateData->setThumbnail($wholeData['product']['thumbnail'] ?? '');
                    $productTempDuplicateData->setName($wholeData['product']['name'] ?? '');
                    $productTempDuplicateData->setType($wholeData['type'] ?? '');
                    $productTempDuplicateData->setSku($sku);
                    $productTempDuplicateData->setQuantity((float) isset($wholeData['product']['quantity_and_stock_status']['qty']) ? $wholeData['product']['quantity_and_stock_status']['qty'] : '0');
                    $productTempDuplicateData->setCost(isset($wholeData['product']['cost']) ? (float) $wholeData['product']['cost'] : (float) '0');
                    $productTempDuplicateData->setPrice(isset($wholeData['product']['price']) ? (float) $wholeData['product']['price'] : (float) '0');
                    $productTempDuplicateData->setSpecialPrice(isset($wholeData['product']['special_price']) ? (float) $wholeData['product']['special_price'] : (float) '0');
                    $productTempDuplicateData->setInformation($this->serializer->serialize($wholeData));
                    $this->productTempDataRepository->save($productTempDuplicateData);
                    $total++;
                }

                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been cloned.', $total)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Temp_MassClone execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/product/productlist',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
