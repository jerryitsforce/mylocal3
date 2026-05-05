<?php
namespace Branch8\MarketplaceProduct\Controller\Temp;

use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Helper\Images;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory;
use Magento\Catalog\Model\Product\Url;
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

/**
 * Marketplace Product Draft MassDelete controller.
 */
class MassClone extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
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
     * @var \Magento\Catalog\Model\Product\Url
     * @since 100.0.3
     */
    protected $productUrl;

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
     * @param CollectionFactory $productTempCollectionFactory
     * @param HelperData $helper
     * @param Images $helperImages
     * @param Url $productUrl
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
        \Magento\Catalog\Model\Product\Url $productUrl,
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
        $this->productUrl = $productUrl;
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
                    $dataSku = $wholeData['product']['sku'] ?? '';
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
                            }
                        }
                    }
                    if ($name) {
                        $name = 'copy-'.$name;
                        $wholeData['product']['name'] = $name;
                    }
                    $wholeData['id'] = 0;
                    $wholeData['product_id'] = 0;
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
