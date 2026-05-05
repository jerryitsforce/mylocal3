<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\ProductPoint\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RevertProductPointAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        // This patch is used for reverting product attribute "point_money_config_product_point" changes in app/code/Branch8/ProductPoint/Setup/Patch/Data/UpdateProductPointAttribute.php,
        // because price type input for "point_money_config_product_point" will show a currency sign in the field,
        // which might cause misunderstanding, so we adjust it back to normal input field with int type.
        $this->moduleDataSetup->getConnection()->startSetup();

        $attributeComment = "if price = 100 and point = 80 means this product needs 20$+80points to buy(if 1point = 1$).";

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'point_money_config_product_point',
            [
                'backend_type'            => 'int',
                'frontend_input'          => 'text',
                'backend_model'           => null,
                'is_filterable_in_search' => 1,
                'note'                    => $attributeComment
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            \Branch8\ProductPoint\Setup\Patch\Data\UpdateProductPointAttribute::class
        ];
    }
}

