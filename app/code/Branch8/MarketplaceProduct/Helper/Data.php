<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_LOG_TYPES = 'branch8_debug/branch8_marketplaceproduct/log_types';

    /**
     * @param string $logType
     * @return bool
     */
    public function isLogTypeEnabled(string $logType): bool
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
