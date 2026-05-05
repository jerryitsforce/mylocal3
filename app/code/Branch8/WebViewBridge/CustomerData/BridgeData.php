<?php
declare(strict_types=1);

namespace Branch8\WebViewBridge\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class BridgeData implements SectionSourceInterface
{
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        CookieManagerInterface $cookieManager
    ) {
        $this->cookieManager = $cookieManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        return [
            'access_token' => $this->cookieManager->getCookie('hotai_app_tk') ?: $this->cookieManager->getCookie('hotai_app_access_token'),
            'login_failed' => $this->cookieManager->getCookie('hotai_app_login_failed')
        ];
    }
}
