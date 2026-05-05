<?php
declare(strict_types=1);

namespace Branch8\Hopes\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetDefaultRmaConfig implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private WriterInterface $configWriter;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $pathValues = [
            'hopes/convenience_store/rma_receiver' => '羅少伶',
            'hopes/convenience_store/rma_phone' => '0981988386',
            'hopes/convenience_store/rma_reason' => '廠退資料',
            'hopes/convenience_store/rma_address' => '臺北市中山區松江路433號12樓',
            'hopes/tcat/rcver_name' => '和泰聯網精品退貨組',
            'hopes/tcat/rcver_phone' => '0225080786',
            'hopes/tcat/rcver_cellphone' => '',
            'hopes/tcat/rcver_suda6' => '37821A',
            'hopes/tcat/rcver_address' => '桃園市楊梅區高獅路560號',
            'hopes/tcat/customer_id' => '5538411401',
            'hopes/tcat/climate' => '0001',
            'hopes/tcat/distance' => '00',
        ];

        foreach ($pathValues as $path => $value) {
            // Save at default scope so admin UI can override later
            $this->configWriter->save($path, $value, 'default', 0);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}

