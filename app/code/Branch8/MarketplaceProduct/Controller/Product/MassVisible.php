<?php

namespace Branch8\MarketplaceProduct\Controller\Product;

use Branch8\Catalog\Model\Source\HiddenType;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\ObjectManager;
use Webkul\Marketplace\Helper\Data as HelperData;

class MassVisible extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

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
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * MassVisible constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param Session $customerSession
     * @param HelperData $helper
     * @param ProductAction $productAction
     * @param CustomerUrl|null $customerUrl
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Filter $filter,
        Session $customerSession,
        HelperData $helper,
        ProductAction $productAction,
        CustomerUrl $customerUrl = null,
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->helper = $helper;
        $this->productAction = $productAction;
        $this->customerUrl = $customerUrl ?: ObjectManager::getInstance()
            ->create(CustomerUrl::class);
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
                $collection = $this->filter->getCollection($this->collectionFactory->create());
                $collection->addFieldToFilter('index_seller_id', $sellerId);

                $productIds = $collection->getAllIds();
                $this->productAction->updateAttributes(
                    $productIds,
                    ['is_hidden' => HiddenType::NOT_HIDDEN],
                    Store::DEFAULT_STORE_ID
                );

                $totalRecords = count($productIds);
                $this->messageManager->addSuccess(
                    __('You have successfully unhidden %1 items.', $totalRecords)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Product_MassVisible execute : ".$e->getMessage()
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
