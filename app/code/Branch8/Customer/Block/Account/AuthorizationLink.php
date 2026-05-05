<?php

namespace Branch8\Customer\Block\Account;

use Webkul\SellerSubAccount\Helper\Data as HelperData;

class AuthorizationLink extends \Magento\Customer\Block\Account\AuthorizationLink
{
    protected $b8CustomerHelper;

    protected $subAccountHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        HelperData $subAccountHelper,
        array $data = []
    ) {
        parent::__construct($context, $httpContext,$customerUrl,$postDataHelper, $data );
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
    }
    public function getHref()
    {
        return (
            $this->isLoggedIn() && !$this->b8CustomerHelper->isSeller()
            && !$this->b8CustomerHelper->isWaitForSeller()
            && !$this->subAccountHelper->isSubAccount()
        )
            ? $this->_customerUrl->getLogoutUrl()
            : $this->_customerUrl->getLoginUrl();
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return ($this->isLoggedIn() && !$this->b8CustomerHelper->isSeller() && !$this->b8CustomerHelper->isWaitForSeller() && !$this->subAccountHelper->isSubAccount()) ? __('Sign Out') : __('Sign In');
    }
}