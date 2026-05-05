<?php

namespace Branch8\LiveSearchResync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_LOG_TYPES = 'branch8_debug/livesearch_resync/log_types';

    /**
     * @param string $logType
     * @return bool
     */
    public function isLogTypeEnabled($logType)
    {
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
