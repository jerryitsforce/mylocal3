<?php

namespace Branch8\MarketplaceProduct\Controller\Temp;

use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\ObjectManager;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\Product as SellerProduct;

class MassEnable extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_productTempCollectionFactory;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * MassEnable constructor.
     *
     * @param Context $context
     * @param CollectionFactory $productTempCollectionFactory
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param Filter $filter
     * @param Session $customerSession
     * @param HelperData $helper
     * @param CustomerUrl|null $customerUrl
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        CollectionFactory $productTempCollectionFactory,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        Filter $filter,
        Session $customerSession,
        HelperData $helper,
        CustomerUrl $customerUrl = null,
        Json $serializer = null
    ) {
        $this->_productTempCollectionFactory = $productTempCollectionFactory;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl ?: ObjectManager::getInstance()
            ->create(CustomerUrl::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
        parent::__construct($context);
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
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $collection = $this->filter->getCollection($this->_productTempCollectionFactory->create());
                $collection->addFieldToFilter(
                    'seller_id',
                    $sellerId
                )->addFieldToFilter(
                    'product_id',
                    ['neq' => 0]
                );

                $total = 0;
                foreach ($collection as $item) {
                    $information = $this->serializer->unserialize($item->getInformation());
                    $information['status'] = SellerProduct::STATUS_ENABLED;
                    $item->setStatus(SellerProduct::STATUS_ENABLED);
                    $item->setInformation($this->serializer->serialize($information));
                    $this->productTempDataRepository->save($item);
                    $total++;
                }

                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been enabled. Please note: The status of new products cannot be edited. Thank you.', $total)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Product_MassEnable execute : ".$e->getMessage()
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
