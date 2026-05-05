<?php

namespace Branch8\ProductAlert\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Url\EncoderInterface;

class CheckEighteenProduct implements ObserverInterface
{
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var ActionFlag
     */
    protected $actionFlag;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EncoderInterface
     */
    protected $urlEncoder;

    /**
     * CheckEighteenProduct constructor.
     * @param HttpContext $httpContext
     * @param ActionFlag $actionFlag
     * @param ProductRepositoryInterface $productRepository
     * @param ManagerInterface $messageManager
     * @param UrlInterface $url
     * @param CustomerSession $customerSession
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        HttpContext $httpContext,
        ActionFlag $actionFlag,
        ProductRepositoryInterface $productRepository,
        ManagerInterface $messageManager,
        UrlInterface $url,
        CustomerSession $customerSession,
        EncoderInterface $urlEncoder
    ) {
        $this->httpContext = $httpContext;
        $this->actionFlag = $actionFlag;
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
        $this->url = $url;
        $this->customerSession = $customerSession;
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $isLoggedIn = $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        
        if (!$isLoggedIn) {
            $controller = $observer->getEvent()->getControllerAction();
            $productId = $controller->getRequest()->getParam('id');
            
            if ($productId) {
                try {
                    $product = $this->productRepository->getById($productId);
                    if ($product->getData('eighteen_product')) {
                        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                        //$this->messageManager->addErrorMessage(__('Please login to view this product.'));
                        $this->customerSession->setBeforeAuthUrl($product->getProductUrl());
                        $encodedUrl = $this->urlEncoder->encode($product->getProductUrl());
                        $customUrl = $this->url->getUrl('customer/account/login', ['referer' => $encodedUrl]);
                        $controller->getResponse()->setRedirect($customUrl);
                    }
                } catch (\Exception $e) {}
            }
        }
        
        return $this;
    }
}
