<?php

namespace Branch8\OptionsWithStockAndImages\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_DEBUG_MODE = 'branch8_debug/branch8_optionswithstockandimages/debug_mode';
    const XML_PATH_LOG_TYPES = 'branch8_debug/branch8_optionswithstockandimages/log_types';

    /**
     * @return bool
     */
    public function isDebugModeEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $logType
     * @return bool
     */
    public function isLogTypeEnabled($logType)
    {
        if (!$this->isDebugModeEnabled()) {
            return false;
        }

        $logTypes = $this->scopeConfig->getValue(
            self::XML_PATH_LOG_TYPES,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($logTypes)) {
            return false;
        }

        $logTypesArray = explode(',', $logTypes);
        return in_array($logType, $logTypesArray);
    }
}
