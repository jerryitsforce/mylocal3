<?php

declare(strict_types=1);

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeLongTimeShipAttributeScope implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory          $eavSetupFactory,
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeCodes = ['long_time_ship', 'show_long_time_ship'];
        foreach ($attributeCodes as $code) {
            $attribute = $eavSetup->getAttribute(Product::ENTITY, $code);
            $attributeId = $attribute['attribute_id'];
            $eavSetup->updateAttribute(
                Product::ENTITY,
                $attributeId,
                'is_global',
                ScopedAttributeInterface::SCOPE_GLOBAL
            );

            $connection->delete(
                $connection->getTableName('catalog_product_entity_int'),
                ['attribute_id = ?' => $attributeId, 'store_id > ?' => 0]
            );
        }

        $connection->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            AddLongTimeShipProductAttribute::class,
            UpdateLongTimeShipProductAttribute::class,
            AddShowLongTimeShipProductAttribute::class
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
