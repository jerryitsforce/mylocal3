<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Add product attribute branch8_product_certifications
 * Stores JSON of selected certification data per product
 */
class AddProductCertificationAttribute implements DataPatchInterface
{
    public const ATTRIBUTE_CODE = 'branch8_product_certifications';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
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
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'label'                   => 'Product Certifications',
                'input'                   => 'textarea',
                'required'                => false,
                'visible'                 => false,   // Hidden from standard forms — handled by custom UI
                'searchable'              => false,
                'filterable'              => false,
                'comparable'             => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined'            => true,
                'sort_order'              => 0,
                'group'                   => 'General',
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
