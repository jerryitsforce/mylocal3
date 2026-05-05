<?php

namespace Branch8\HotaiAuth\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class SellerFromCustomer implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;
    /**
     * @var SchemaSetupInterface
     */
    protected $setup;

    /**
     * @param  ModuleDataSetupInterface  $moduleDataSetup
     * @param  SchemaSetupInterface  $setup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SchemaSetupInterface $setup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->setup = $setup;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $select = $this->moduleDataSetup->getConnection()
            ->select()
            ->from(
                ['mu' => $this->moduleDataSetup->getTable('marketplace_userdata')],
                ['seller_id']
            )
            ->where('mu.is_seller = ?', 1);

        $this->moduleDataSetup->getConnection()
            ->update(
                $this->moduleDataSetup->getTable('customer_entity'),
                ['platform' => 'seller'],
                ['entity_id IN (?)' => $select]
            );
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
