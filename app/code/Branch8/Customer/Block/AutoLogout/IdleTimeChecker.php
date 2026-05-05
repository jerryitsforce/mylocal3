<?php
namespace Branch8\Customer\Block\AutoLogout;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

class IdleTimeChecker extends \Magento\Framework\View\Element\Template
{
    private \Magento\Framework\App\Http\Context $httpContext;

    /**
     * @var \Branch8\HotaiPay\Helper\Data
     */
    protected $hotaiPayHelper;
    private MobileDetect $mobileDetect;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Branch8\HotaiPay\Helper\Data $hotaiPayHelper,
        MobileDetect $mobileDetect,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->hotaiPayHelper = $hotaiPayHelper;
        $this->mobileDetect = $mobileDetect;
        parent::__construct($context, $data);
    }

    public function getIdleTimeout()
    {
        return $this->_scopeConfig->getValue('customer/online_customers/section_data_lifetime');
    }

    public function isHotaiApp()
    {
        return $this->mobileDetect->isHotaiApp();
    }

    public function isLoggedIn()
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    public function checkTokenExpried()
    {
        return $this->hotaiPayHelper->getToken();
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'BLOCK_TPL',
            $this->_storeManager->getStore()->getCode(),
            $this->getTemplateFile(),
            'base_url' => $this->getBaseUrl(),
            'template' => $this->getTemplate(),
            'is_logged_in' => $this->isLoggedIn() ? '1' : '0'
        ];
    }
}
