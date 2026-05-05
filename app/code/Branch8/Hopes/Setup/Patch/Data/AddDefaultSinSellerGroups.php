<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Hopes\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Add default SIN file name to seller code mapping: 01->HTC01, 02->HTC02, etc.
 * Only applies when config is not yet set.
 */
class AddDefaultSinSellerGroups implements DataPatchInterface
{
    private const CONFIG_PATH = 'hopes/sin/seller_groups';

    /** Default JSON: file_name and seller_code per row */
    private const DEFAULT_VALUE = '[{"file_name":"01","seller_code":"HTC01"},'
        . '{"file_name":"02","seller_code":"HTC02"},{"file_name":"03","seller_code":"HTC03"},'
        . '{"file_name":"04","seller_code":"HTC04"},{"file_name":"05","seller_code":"HTC05"}]';

    private ModuleDataSetupInterface $moduleDataSetup;
    private WriterInterface $configWriter;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $currentValue = $this->scopeConfig->getValue(self::CONFIG_PATH);
        if ($currentValue === null || $currentValue === '' || $currentValue === '[]') {
            $this->configWriter->save(self::CONFIG_PATH, self::DEFAULT_VALUE, 'default', 0);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
