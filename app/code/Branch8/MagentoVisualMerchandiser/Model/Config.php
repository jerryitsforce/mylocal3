<?php

namespace Branch8\MagentoVisualMerchandiser\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_ENABLE = 'branch8_visualmerchandiser/configuration/enable_auto_index';
    const XML_PATH_THROTTLE = 'branch8_visualmerchandiser/configuration/throttle';

    private ScopeConfigInterface $config;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->config = $scopeConfig;
    }

    /**
     * @return true
     */
    public function enabled()
    {
        return (bool)$this->config->getValue(self::XML_PATH_ENABLE);
    }

    /**
     * @return bool
     */
    public function getThrottle()
    {
        return (int)$this->config->getValue(self::XML_PATH_THROTTLE);
    }

}
