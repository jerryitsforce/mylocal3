<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Plugin\Magento\Checkout\Block\Cart;

use Branch8\MarketplaceProduct\Plugin\Customer\CustomerSessionContext;
use Magento\Framework\App\Http\Context as HttpContext;

class Sidebar
{
    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @param HttpContext $httpContext
     */
    public function __construct(
        HttpContext $httpContext
    ) {
        $this->httpContext = $httpContext;
    }

    /**
     * Modify shopping cart URL based on special customer flag from HTTP Context
     *
     * @param \Magento\Checkout\Block\Cart\Sidebar $subject
     * @param string $result
     * @return string
     */
    public function afterGetShoppingCartUrl(
        \Magento\Checkout\Block\Cart\Sidebar $subject,
        string $result
    ): string {
        // 從 HTTP Context 讀取特殊客戶標記（由 CustomerSessionContext Plugin 設置）
        // 此值在 FPC 之前已被設置，所以即使頁面被快取也能正確讀取
        $isSpecialCustomer = (bool)$this->httpContext->getValue(
            CustomerSessionContext::CONTEXT_SPECIAL_CUSTOMER
        );

        // 特殊客戶使用 /cart 路徑
        if ($isSpecialCustomer) {
            return $subject->getUrl('cart');
        }

        return $subject->getUrl('checkout/cart');
    }
}
