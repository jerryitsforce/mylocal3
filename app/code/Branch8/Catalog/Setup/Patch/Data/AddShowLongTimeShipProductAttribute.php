<?php

declare(strict_types=1);

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddShowLongTimeShipProductAttribute implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * AddShowLongTimeShipProductAttribute constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        EavSetupFactory          $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'show_long_time_ship',
            [
                'type' => 'int',
                'label' => 'Show long time to be shipped',
                'input' => 'boolean',
                'default' => false,
                'user_defined' => true,
                'visible' => true,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => false,
                'sort_order' => 555,
                'required' => false,
                'source' => Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'is_filterable_in_grid' => false,
                'group' => 'General',
            ]
        );

        $tableName = $connection->getTableName('eav_entity_attribute');
        $attributeId = (int)$eavSetup->getAttributeId(Product::ENTITY, 'long_time_ship');
        $dataToUpdate = ['sort_order' => 556];
        $where = ['attribute_id = ?' => $attributeId];
        $connection->update($tableName, $dataToUpdate, $where);

        $connection->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            AddLongTimeShipProductAttribute::class,
            UpdateLongTimeShipProductAttribute::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
