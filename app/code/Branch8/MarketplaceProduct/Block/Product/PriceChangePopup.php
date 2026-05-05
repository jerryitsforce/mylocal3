<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Product;

use Magento\Framework\View\Element\Template;
use Branch8\Customer\Helper\Data;
use Branch8\MarketplaceProduct\Plugin\Customer\CustomerSessionContext;
use Magento\Framework\App\Http\Context as HttpContext;

class PriceChangePopup extends Template
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @param Template\Context $context
     * @param Data $helper
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->httpContext = $httpContext;
    }

    /**
     * Returns the shopping cart URL.
     *
     * @return string
     */
    public function getShoppingCartUrl(): string
    {
        // 從 HTTP Context 讀取特殊客戶標記（由 CustomerSessionContext Plugin 設置）
        // 此值在 FPC 之前已被設置，所以即使頁面被快取也能正確讀取
        $isSpecialCustomer = (bool)$this->httpContext->getValue(
            CustomerSessionContext::CONTEXT_SPECIAL_CUSTOMER
        );

        // 特殊客戶使用 /cart 路徑
        if ($isSpecialCustomer) {
            return $this->getUrl('cart');
        }

        return $this->getUrl('checkout/cart');
    }

    /**
     * Returns a message to be displayed on the popup.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->helper->getPriceChangePopupContent();
    }
}
