<?php

namespace Branch8\Customer\Block\Account;

use Magento\Customer\Model\Context;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class RegisterLink extends \Magento\Customer\Block\Account\RegisterLink{

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;
    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $random;
    /**
     * @var \Magento\Framework\View\Helper\SecureHtmlRenderer
     */
    protected $secureRenderer;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Registration $registration
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer
     * @param HelperData $subAccountHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Registration $registration,
        \Magento\Customer\Model\Url $customerUrl,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer,
        HelperData $subAccountHelper,
        array $data = []
    )
    {
        parent::__construct($context, $httpContext, $registration, $customerUrl, $data);
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->random = $random;
        $this->secureRenderer = $secureRenderer;
        $this->subAccountHelper = $subAccountHelper;
    }


    protected function _toHtml()
    {
        if (
            !$this->_registration->isAllowed()
            || (
                $this->httpContext->getValue(Context::CONTEXT_AUTH)
                && !$this->b8CustomerHelper->isSeller()
                && !$this->b8CustomerHelper->isWaitForSeller()
                && !$this->subAccountHelper->isSubAccount()
            )
        ) {
            return '';
        }
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }

        if (!$this->getDataUsingMethod('id')) {
            $this->setDataUsingMethod('id', 'id' .$this->random->getRandomString(8));
        }

        return '<li><a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>'
            .$this->renderSpecialAttributes();
    }

    private function renderSpecialAttributes(): string
    {
        $id = $this->getDataUsingMethod('id');
        if (!$id) {
            throw new \RuntimeException('ID is required to render the link');
        }

        $html = '';
        $style = $this->getDataUsingMethod('style');
        if ($style) {
            $html .= $this->secureRenderer->renderStyleAsTag($style, "#$id");
        }
        foreach ($this->allowedAttributes as $attribute) {
            if (mb_strpos($attribute, 'on') === 0 && $value = $this->getDataUsingMethod($attribute)) {
                $html .= $this->secureRenderer->renderEventListenerAsTag(
                    $attribute,
                    $value,
                    "#$id"
                );
            }
        }

        return $html;
    }
}