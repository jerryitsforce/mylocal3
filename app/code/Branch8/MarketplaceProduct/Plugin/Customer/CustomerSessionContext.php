<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Plugin\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * Plugin to add special customer flag to HTTP Context for FPC cache variation
 *
 * This plugin runs before FPC checks, allowing different cache versions for:
 * - Special customers (multiple IDs supported)
 * - Regular customers
 */
class CustomerSessionContext
{
    /**
     * Customer IDs that should get special treatment
     * Add new customer IDs to this array as needed
     */
    private const SPECIAL_CUSTOMER_IDS = [
        635451,// Dennis
        718383,//Eric
        790329,//Eddie
        1186263,//Selena
        1205721,//Elaine
        1839903,//Steven
        1850754,//Chris
        1988305,//Brian

        // 在此添加更多客戶 ID，例如:
        // 123,
        // 456,
    ];

    /**
     * HTTP Context key for special customer flag
     */
    public const CONTEXT_SPECIAL_CUSTOMER = 'b8_special_customer';

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var HttpContext
     */
    private HttpContext $httpContext;

    /**
     * @param CustomerSession $customerSession
     * @param HttpContext $httpContext
     */
    public function __construct(
        CustomerSession $customerSession,
        HttpContext $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * Set special customer flag in HTTP Context before FPC cache lookup
     *
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        \Magento\Framework\App\ActionInterface $subject,
        \Magento\Framework\App\RequestInterface $request
    ): void {
        $customerId = $this->customerSession->getCustomerId();
        $isSpecialCustomer = ($customerId && in_array((int)$customerId, self::SPECIAL_CUSTOMER_IDS, true));

        $this->httpContext->setValue(
            self::CONTEXT_SPECIAL_CUSTOMER,
            $isSpecialCustomer,
            false // default value for non-logged-in users
        );
    }
}
