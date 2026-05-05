<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ProductAttributeForFilter implements DataPatchInterface
{

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    ) {
        
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeCodes = ['livesearch_categories','commission_percent'];

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);


        foreach ($attributeCodes as $attributeCode) {
            $attribute = $eavSetup->getAttribute($entityTypeId, $attributeCode);
            if (empty($attribute)) {
                continue;
            }
            $eavSetup->updateAttribute($entityTypeId, $attributeCode, 'is_filterable_in_grid', 1);
            $eavSetup->updateAttribute($entityTypeId, $attributeCode, 'is_used_in_grid', 1);
        }
        
        $eavSetup->updateAttribute($entityTypeId, 'livesearch_categories', 'frontend_label', 'Categories');

        $this->moduleDataSetup->getConnection()->endSetup();
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
