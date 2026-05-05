<?php

namespace Branch8\HotaiCore\Helper;

class DebugLog
{
    public static function isEnable($module, $filename)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        // normalize inputs for safety (config may be comma-separated string).
        $module = strtolower(trim((string) $module));
        $filename = trim((string) $filename);

        if ($module === '' || $filename === '') {
            return false;
        }

        $configPath = 'branch8_debug/' . $module . '/log_file';
        $files = $scopeConfig->getValue($configPath);

        if (empty($files)) {
            return false;
        }

        if (is_string($files)) {
            $files = array_filter(array_map('trim', explode(',', $files)), static fn($v) => $v !== '');
        }

        return in_array($filename, $files);
    }
}
