<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       14/04/2026
 */

namespace Branch8\RestrictedProduct\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigData
{
    const USE_OPTIMIZE_PATH = 'amasty_groupcat/general/optimize_collection_loading';
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function enableOptimize()
    {
       return (bool)$this->scopeConfig->getValue(self::USE_OPTIMIZE_PATH);
    }
}
