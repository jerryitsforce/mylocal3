<?php
namespace Branch8\MarketplaceProduct\Controller\Temp;

use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Helper\Images;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\StoreChangedData;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory;
use Branch8\MarketplaceStaging\Helper\Variation;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Controller\Product\SaveProduct;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\Product;

/**
 * Marketplace Product Draft MassDelete controller.
 */
class MassReview extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
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
     * @var CollectionFactory
     */
    protected $_productTempCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Images
     */
    protected Images $helperImages;

    /**
     * @var SaveProduct
     */
    protected $_saveProduct;

    /**
     * @var StoreChangedData
     */
    protected StoreChangedData $storeChangedData;

    /**
     * @var Variation
     */
    protected $variationHelper;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

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
     * @param Context $context
     * @param Filter $filter
     * @param Session $customerSession
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param ProductTempDataInterfaceFactory $productTempDataFactory
     * @param CollectionFactory $productTempCollectionFactory
     * @param HelperData $helper
     * @param Images $helperImages
     * @param SaveProduct $saveProduct
     * @param StoreChangedData $storeChangedData
     * @param Variation $variationHelper
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param CustomerUrl|null $customerUrl
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        ProductTempDataInterfaceFactory $productTempDataFactory,
        CollectionFactory $productTempCollectionFactory,
        HelperData $helper,
        Images $helperImages,
        SaveProduct $saveProduct,
        StoreChangedData $storeChangedData,
        Variation $variationHelper,
        MarketplaceProductManagement $marketplaceProductManagement,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        CustomerUrl $customerUrl = null,
        Json $serializer = null
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->productTempDataFactory = $productTempDataFactory;
        $this->_productTempCollectionFactory = $productTempCollectionFactory;
        $this->helper = $helper;
        $this->helperImages = $helperImages;
        $this->_saveProduct = $saveProduct;
        $this->storeChangedData = $storeChangedData;
        $this->variationHelper = $variationHelper;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
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
                    $this->_productTempCollectionFactory->create()
                );
                $collection->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                $total = 0;
                foreach ($collection as $item) {
                    $wholeData = $this->serializer->unserialize($item->getInformation());
                    $options = [];
                    $productId = $item->getProductId();
                    if(!$productId && isset($wholeData['product']['options'])){
                        $optionData = $wholeData['product']['options'];
                        foreach($optionData as $key => $option){
                            unset($option['option_id']);
                            unset($option['product_id']);
                            if(isset($option['values'])){
                                $values = [];
                                foreach($option['values'] as $value){
                                    unset($value['option_id']);
                                    unset($value['option_type_id']);
                                    $values[] = $value;
                                }
                                $option['values'] = $values;
                            }
                            $options[] = $option;
                        }
                        $wholeData['product']['options'] = $options;
                    }
                    if(!isset($wholeData['product']['shipping_method'])){
                        $wholeData['product']['shipping_method'] = [];
                    }
                    if (!$productId || !$this->helper->getIsProductEditApproval()) {
                        // Handle the case of adding a new product
                        if(!$productId && isset($wholeData['product']['shipping_method']) && is_array($wholeData['product']['shipping_method'])){
                            $wholeData['product']['shipping_method'] = implode(',', $wholeData['product']['shipping_method']);
                        }
                        $returnArr = $this->_saveProduct->saveProductData($sellerId, $wholeData);
                        $this->storeChangedData->execute($wholeData, (int) $returnArr['product_id'], $sellerId, CreatedFrom::CREATED_FROM_SELLER, true);
                        $productId = $returnArr['product_id'];
                        $productRowId = $returnArr['row_id'];
                        if(isset($wholeData['org_product_id'])){
                            $this->variationHelper->cloneVariationData($wholeData['org_product_id'], $productRowId, $productId);
                        }
                        //$this->variationHelper->saveVariation($productId, $wholeData);
                    } else {
                        try {
                            $product = $this->marketplaceProductManagement->getByCode('mageproduct_id', (int) $productId);
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
                        $returnArr = $this->storeChangedData->execute($wholeData, (int) $productId, $sellerId);
                        $isError = $returnArr['error'] ?? false;
                        if ($isError) {
                            $message = $returnArr['message'] ?? 'Something went wrong while saving this product.';
                            $this->messageManager->addErrorMessage($message);
                        }
                    }
                    $this->productTempDataRepository->delete($item);
                    $total++;
                }

                $this->messageManager->addSuccess(
                    __('A total of %1 product(s) have been saved successfully. An administrator will review and approve it if necessary.', $total)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Temp_MassReview execute : ".$e->getMessage()
                );
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplacectrl/temp/productlist',
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
