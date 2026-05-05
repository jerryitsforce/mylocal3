<?php
namespace Branch8\Customer\Block\AutoLogout;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey;
use Branch8\Customer\Helper\Data;

class SessionRefresher extends Template
{
    private \Magento\Framework\App\Http\Context $httpContext;
    
    protected $customerSession;
    
    protected $formKey;

    protected $b8CustomerHelper;

    protected $subAccountHelper;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper,
        Session $customerSession,
        FormKey $formKey,
        Data $b8CustomerHelper,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
        $this->formKey = $formKey;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
        parent::__construct($context, $data);
    }

    public function getRefreshInterval()
    {
        return 24;
    }

    public function isLoggedIn()
    {
        return (bool)$this->customerSession->isLoggedIn() && !$this->b8CustomerHelper->isSeller() && !$this->subAccountHelper->isSubAccount();
    }
    
    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}