<?php

namespace Branch8\MarketplaceProduct\Controller\Product;

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
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\ObjectManager;
use Webkul\Marketplace\Helper\Data as HelperData;

class MassDisable extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
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
     * Constructor.
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
        Context           $context,
        CollectionFactory $collectionFactory,
        Filter            $filter,
        Session           $customerSession,
        HelperData        $helper,
        ProductAction     $productAction,
        CustomerUrl       $customerUrl = null
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
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\SessionException
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
     * @inheritDoc
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $collection = $this->filter->getCollection($this->collectionFactory->create());
                $collection->getSelect()->joinLeft(
                    'marketplace_product as mp',
                    'e.entity_id = mp.mageproduct_id AND mp.seller_id = ' . $sellerId,
                    []
                );

                $productIds = $collection->getAllIds();
                $bulkData = ['action' => 'MassDisable', 'product_ids' => $productIds];
                $this->productAction->updateAttributes(
                    $productIds,
                    ['status' => Status::STATUS_DISABLED, 'updated_in_bulk' => $bulkData],
                    Store::DEFAULT_STORE_ID
                );

                $totalRecords = count($productIds);
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been disabled.', $totalRecords)
                );

            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    'Controller_Product_Product_MassDisable execute : ' . $e->getMessage()
                );
                $this->messageManager->addErrorMessage($e->getMessage());
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
