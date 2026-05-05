<?php

namespace Branch8\CatalogCustom\Controller\Cart;

use Branch8\HotaiAuth\Service\HotaiAuthService;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\Session\SessionManager;

class Index extends \Magento\Checkout\Controller\Cart\Index
{
    /**
     * EDM campaign guard: redirect to login only when all three UTM params match exactly.
     */
    const EDM_REQUIRED_UTM = [
        'utm_medium'   => 'flow',
        'utm_source'   => 'Klaviyo',
        'utm_campaign' => 'AC_1',
    ];

    const EDM_LOG_FOLDER = 'Checkout/Controller/CatalogCustomCartIndex';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /** @var \Magento\Framework\Session\SessionManager $sessionManager */
    protected $sessionManager;

    /** @var HotaiAuthService */
    protected $hotaiAuthService;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;


    /**
     * @param  \Magento\Framework\App\Action\Context  $context
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param  \Magento\Checkout\Model\Session  $checkoutSession
     * @param  \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param  \Magento\Framework\Data\Form\FormKey\Validator  $formKeyValidator
     * @param  \Magento\Checkout\Model\Cart  $cart
     * @param  \Magento\Framework\View\Result\PageFactory  $resultPageFactory
     * @param  \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository
     * @param  \Magento\Framework\Registry  $registry
     * @param  SessionManager  $sessionManager
     * @param  HotaiAuthService  $hotaiAuthService
     * @param  HotaiCoreCommonHelper  $hotaiCoreCommonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry $registry,
        SessionManager $sessionManager,
        HotaiAuthService $hotaiAuthService,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->productRepository = $productRepository;
        $this->sessionManager   = $sessionManager;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart,
            $resultPageFactory);
    }

    /**
     * Execute method for Cart Index Controller
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // EDM campaign login guard
        if ($redirect = $this->handleEdmLoginGuard()) {
            return $redirect;
        }

        // Check if the cart contains any individual products and redirect to checkout if necessary
        if($this->isAccessCheckoutCart() && $this->sessionManager->getData('custom_referer_url') != 'checkout'){
            return $this->resultRedirectFactory->create()->setPath('checkout');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shopping Cart'));
        return $resultPage;
    }

    /**
     * EDM campaign login guard.
     * Redirect guests to HotaiAuth login only when ALL required UTM params match exactly.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|null
     */
    protected function handleEdmLoginGuard()
    {
        $request = $this->getRequest();

        // DEBUG entry log: always record what the controller actually sees
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            'title'         => 'EDM controller guard entry',
            'uri'           => $request->getRequestUri(),
            'utm_medium'    => $request->getParam('utm_medium'),
            'utm_source'    => $request->getParam('utm_source'),
            'utm_campaign'  => $request->getParam('utm_campaign'),
            'server_query'  => $_SERVER['QUERY_STRING'] ?? '(none)',
            'server_uri'    => $_SERVER['REQUEST_URI'] ?? '(none)',
            'raw_get'       => $_GET,
        ]), self::EDM_LOG_FOLDER);

        // 1. All three UTM params must match exactly (case-sensitive, trimmed)
        foreach (self::EDM_REQUIRED_UTM as $key => $expected) {
            $value = $request->getParam($key);
            if (!is_scalar($value) || trim((string)$value) !== $expected) {
                return null;
            }
        }

        // 2. Not logged in (no hotai_token in session)
        $sessionData = $this->sessionManager->getData();
        $hotaiToken = $sessionData['hotai_token'] ?? null;
        if ($hotaiToken) {
            return null;
        }

        // 3. Store current URL for post-login return, then redirect to login
        try {
            $currentUrl = $this->_url->getCurrentUrl();
            $this->sessionManager->setCustomerRefererUrl($currentUrl);

            $loginUrl = $this->hotaiAuthService->getLoginUrl();

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                'title'       => 'EDM controller guard triggered, redirecting to login.',
                'current_url' => $currentUrl,
                'login_url'   => $loginUrl,
            ]), self::EDM_LOG_FOLDER);

            return $this->resultRedirectFactory->create()->setUrl($loginUrl);
        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                'title'   => 'EDM controller guard failed to redirect.',
                'message' => $e->getMessage(),
            ]), self::EDM_LOG_FOLDER);
            return null;
        }
    }

    /**
     * @return bool
     * Check if the cart contains any products with the 'individual_product' attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAccessCheckoutCart()
    {
        $items = $this->_checkoutSession->getQuote()->getAllItems();
        if ($items) {
            foreach ($items as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                try {
                    $product = $item->getProduct();
                    if ($product->getData('individual_product')) {
                        return true;
                    }
                } catch (\Exception $exception) {
                    return false;
                }
            }
        }
        return false;
    }
}
