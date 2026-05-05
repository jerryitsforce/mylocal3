<?php

namespace Branch8\HotaiPoint\Block\Header;

use Magento\Framework\Math\Random;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Html\Link as HtmlLink;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Point extends HtmlLink
{
    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @param HttpContext $httpContext
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     * @param Random|null $random
     */
    public function __construct(
        HttpContext $httpContext,
        Template\Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
        ?Random $random = null
    ) {
        $this->httpContext = $httpContext;

        parent::__construct($context, $data, $secureRenderer, $random);
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('hotai_point/detail');
    }
}
