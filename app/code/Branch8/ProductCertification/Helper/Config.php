<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Config helper
 */
class Config extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'branch8_product_certification/general/enabled';

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
