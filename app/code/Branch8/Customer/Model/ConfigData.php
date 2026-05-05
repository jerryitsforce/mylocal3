<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       01/02/2026
 */

namespace Branch8\Customer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigData
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }
}
