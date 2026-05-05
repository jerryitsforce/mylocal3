<?php

namespace Branch8\Preorder\Setup;

use Branch8\Preorder\Setup\Patch\Data\EavSetup;
use Branch8\Preorder\Setup\Patch\Data\Magento;
use Branch8\Preorder\Setup\Patch\Data\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

Class InstallSchema implements InstallSchemaInterface{
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
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context){
    $setup->startSetup();

    $this->installData($this->moduleDataSetup);

    $setup->endSetup();
}


    /**
     * Install Data
     *
     * @param Magento\Framework\Setup\ModuleDataSetupInterface $setup
     */
    public function installData($setup){

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
        $setup->startSetup();

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'preorder_mode',
            [
                'type' => 'int',
                'label' => 'Preorder mode',
                'input' => 'select',
                'source' => 'Branch8\Preorder\Model\Source\PreorderMode',
                'frontend' => '',
                'required' => false,
                'backend' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'used_in_product_listing' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'option' => array('values' => array(""))
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'preorder_x_days',
            [
                'type' => 'int',
                'group' => 'Product Details',
                'backend' => '',
                'frontend' => '',
                'label' => 'Preorder XDays',
                'input' => 'text',
                'frontend_class' => 'validate-number validate-greater-than-zero',
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


        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'preorder_start_date',
            [
                'type' => 'datetime',
                'group' => 'Product Details',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                'frontend' => '',
                'label' => 'Marketplace Preorder Start Date',
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
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'preorder_end_date',
            [
                'type' => 'datetime',
                'group' => 'Product Details',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                'frontend' => '',
                'label' => 'Marketplace Preorder End Date',
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

        $setup->endSetup();

    }


}
