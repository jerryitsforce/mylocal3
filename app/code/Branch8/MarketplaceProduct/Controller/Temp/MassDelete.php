<?php
namespace Branch8\MarketplaceProduct\Controller\Temp;

use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Marketplace Product Draft MassDelete controller.
 */
class MassDelete extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
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
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $_productTempCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Session $customerSession
     * @param CollectionFactory $productTempCollectionFactory
     * @param HelperData $helper
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param CustomerUrl|null $customerUrl
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        CollectionFactory $productTempCollectionFactory,
        HelperData $helper,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        CustomerUrl $customerUrl = null
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->_productTempCollectionFactory = $productTempCollectionFactory;
        $this->helper = $helper;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->customerUrl = $customerUrl ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerUrl::class);
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
                    $this->productTempDataRepository->delete($item);
                    $total++;
                }

                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been deleted.', $total)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Temp_MassDelete execute : ".$e->getMessage()
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
