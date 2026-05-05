<?php

namespace Branch8\CatalogCustom\Plugin;

use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Store\Model\ScopeInterface;

class Cartplugin
{
    const XML_PATH_GUEST_CHECKOUT = 'checkout/options/guest_checkout';
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var HotaiAuthService
     */
    protected $hotaiAuthService;

    /**
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param HotaiAuthService $hotaiAuthService
     */
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        HotaiAuthService $hotaiAuthService
    ) {
        $this->_url = $url;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->hotaiAuthService = $hotaiAuthService;
    }

    /**
     * Handle add product to cart for individual_product
     * @param $subject
     * @param $productInfo
     * @param $requestInfo
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeAddProduct($subject, $productInfo, $requestInfo = null)
    {
        $checkoutUrl = $this->storeManager->getStore()->getBaseUrl() . "checkout/";
        $guestCheckout = $this->scopeConfig->isSetFlag(
            self::XML_PATH_GUEST_CHECKOUT,
            ScopeInterface::SCOPE_STORE,
            $subject->getQuote()->getStoreId()
        );

        $accUrl = $this->_url->getUrl($checkoutUrl);

        if ($productInfo->getData('individual_product')) {
            if ($guestCheckout || $subject->getCustomerSession()->getId()) {
                $this->request->setParam('return_url', $accUrl);
            } else {
                $this->request->setParam('return_url', $this->redirectLogin());
            }
        }

        return [$productInfo, $requestInfo];
    }

    /**
     * Get login URL
     * @return string
     * @throws \JsonException
     */
    public function redirectLogin(){
        $hotaiUrl = $this->hotaiAuthService->getLoginUrl();
        if (empty($hotaiUrl)) {
            return $this->_url->getUrl('');
        } else {
            return $hotaiUrl;
        }
    }
}
