<?php
declare(strict_types=1);

namespace Branch8\HotaiAuth\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateAppKey implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    private const OLD_PATH = 'hotai_auth/external_exchange/app_key';
    private const NEW_PATH = 'hotai_auth/general/app_key';

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $conn = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $this->moduleDataSetup->startSetup();

        $sql = "
            INSERT INTO {$table} (scope, scope_id, path, value)
            SELECT scope, scope_id, :new_path, value
            FROM {$table} AS src
            WHERE src.path = :old_path
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ";


        $conn->query($sql, [
            'new_path' => self::NEW_PATH,
            'old_path' => self::OLD_PATH,
        ]);

        $this->moduleDataSetup->endSetup();
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
