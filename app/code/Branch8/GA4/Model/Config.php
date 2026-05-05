<?php
/**
 * @package
 * @author      Cuong Ho <cuonghh@forixwebdesign.com>
 * @copyright   Copyright © 2021 Forix LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace Branch8\GA4\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private $scopeConfig;

    private const XML_PATH_ACTIVE = 'google/gtag/branch8_ga4/active';
    private const XML_PATH_GTM_CONTAINER_ID = 'google/gtag/branch8_ga4/gtm_container_id';
    private const XML_PATH_GTM_NON_JS_CODE = 'google/gtag/branch8_ga4/gtm_non_js_code';
    private const XML_PATH_DEV_MOVE_JS_TO_BOTTOM = 'dev/js/move_script_to_bottom';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * @return true
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACTIVE);
    }

    /**
     * @return true
     */
    public function getGtmContainerId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GTM_CONTAINER_ID);
    }

    /**
     * @return true
     */
    public function getGtmNonJsCode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GTM_NON_JS_CODE);
    }

    /**
     * @return bool
     */
    public function isDevMoveJsBottomEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEV_MOVE_JS_TO_BOTTOM,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
