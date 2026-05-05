<?php
namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class DisablePageBuilderForDescription implements DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $attributeId = $connection->fetchOne("
            SELECT attribute_id FROM eav_attribute
            WHERE attribute_code = 'description'
            AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product')
        ");

        if ($attributeId) {
            $connection->update(
                $this->moduleDataSetup->getTable('catalog_eav_attribute'),
                ['is_pagebuilder_enabled' => 0, 'is_wysiwyg_enabled' => 1],
                ['attribute_id = ?' => $attributeId]
            );
        }

        $connection->endSetup();
    }

    public static function getDependencies() { return []; }
    public function getAliases() { return []; }
}