<?php

namespace Branch8\CustomerTicketTable\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class LogConfig
{
    public const XML_PATH_LOG_FILE = 'branch8_debug/branch8_customertickettable/log_file';

    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if CustomerTicketTable logging is enabled globally.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getEnabledLogKeys() !== [];
    }

    /**
     * Check if logging is enabled for a given class key.
     *
     * @param string $classKey
     * @return bool
     */
    public function isEnabledFor(string $classKey): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $enabledKeys = $this->getEnabledLogKeys();

        return in_array($classKey, $enabledKeys, true);
    }

    /**
     * Get enabled log keys from admin config multiselect.
     *
     * @return string[]
     */
    public function getEnabledLogKeys(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_LOG_FILE);
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        $parts = array_values(array_filter($parts, static fn (string $v) => $v !== ''));

        return $parts;
    }
}

