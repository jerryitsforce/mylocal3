<?php

namespace Branch8\BrandManagement\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class DebugLogConfig
{
    public const XML_PATH_LOG_TARGETS = 'branch8_debug/b8_brandmanagement/log_targets';

    /**
     * @param ScopeConfigInterface $scopeConfig Configuration reader for system.xml values.
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * Whether BrandManagement logging is enabled by configuration.
     */
    public function isEnabledGlobally(): bool
    {
        return $this->getEnabledTargets() !== [];
    }

    /**
     * Backward-compatible alias for global enablement.
     */
    public function isEnabled(): bool
    {
        return $this->isEnabledGlobally();
    }

    /**
     * Return enabled log targets from a multiselect config value.
     *
     * @return string[]
     */
    public function getEnabledTargets(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_LOG_TARGETS);

        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * Determine whether the given target is enabled.
     *
     * @param string $target
     */
    public function isTargetEnabled(string $target): bool
    {
        if (!$this->isEnabledGlobally()) {
            return false;
        }

        $targets = $this->getEnabledTargets();

        return in_array($target, $targets, true);
    }
}

