<?php

namespace Branch8\Preorder\Setup;

use Branch8\Preorder\Setup\Patch\Data\EavSetup;
use Branch8\Preorder\Setup\Patch\Data\Magento;
use Branch8\Preorder\Setup\Patch\Data\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

Class UpgradeSchema implements UpgradeSchemaInterface{

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ){
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
    $setup->startSetup();

    $this->upgradeData($this->moduleDataSetup, $context);

    $setup->endSetup();
}


    /**
     * Upgrade Data
     *
     * @param Magento\Framework\Setup\ModuleDataSetupInterface $setup
     */
    public function upgradeData($setup, $context){

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'preorder_ship_date',
                [
                    'type' => 'datetime',
                    'group' => 'Product Details',
                    'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                    'frontend' => '',
                    'label' => 'Marketplace Preorder Ship Date',
                    'input' => 'date',
                    'class' => 'validate-date',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'preorder_use_qty', [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Marketplace Preorder Use Quantity',
                'input' => 'boolean',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => 0,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'group' => 'Product Details'
            ]);
        }

        $setup->endSetup();

    }


}
