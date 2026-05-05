<?php

declare(strict_types=1);

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateLongTimeShipProductAttribute implements DataPatchInterface
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
     *
     * @throws LocalizedException
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            Product::ENTITY,
            'long_time_ship',
            'frontend_input',
            'select'
        );

        $eavSetup->updateAttribute(
            Product::ENTITY,
            'long_time_ship',
            'source_model',
            Table::class
        );

        $attribute = $eavSetup->getAttribute(Product::ENTITY, 'long_time_ship');

        if ($attribute) {
            $attributeId = $attribute['attribute_id'];
            $connection->delete(
                $connection->getTableName('eav_attribute_option'),
                ['attribute_id = ?' => $attributeId]
            );
            $optionData = [
                'attribute_id' => $attributeId,
                'values' => [
                    'Over 3 days',
                    'Over 5 days',
                    'Over 10 days',
                    'Over 15 days',
                    'Over 20 days',
                    'Over 30 days'
                ]
            ];

            $eavSetup->addAttributeOption($optionData);
        }

        $connection->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [AddLongTimeShipProductAttribute::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
