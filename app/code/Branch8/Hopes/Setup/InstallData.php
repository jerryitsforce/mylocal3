<?php

namespace Branch8\Hopes\Setup;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 * @package Magento\TestSetupDeclarationModule3\Setup
 */
class InstallData implements InstallDataInterface
{
    const ASSIGN_GROUP = "Product Details";

    /** @var EavSetupFactory */
    protected $eavSetupFactory;

    /** @var PointMoneyConfigHelper */
    protected $helper;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $this->createHopesProductAttribute($eavSetup);
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createHopesProductAttribute(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $attributes = [
            "hotai1_FRCD" => [
                "type" => "varchar",
                "label" => "hotai1_FRCD",
                "input" => "text",
            ],
            "hotai1_PARTNO" => [
                "type" => "varchar",
                "label" => "hotai1_PARTNO",
                "input" => "text",
            ],
            "hotai1_PARTCUSTID" => [
                "type" => "varchar",
                "label" => "hotai1_PARTCUSTID",
                "input" => "text",
            ],
            "hotai1_EMPRTAX" => [
                "type" => "int",
                "label" => "hotai1_EMPRTAX",
                "input" => "text",
            ],
        ];

        foreach ($attributes as $key => $value) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $key,
                [
                    'type' => $value['type'],
                    'backend' => '',
                    'frontend' => '',
                    'label' => $value['label'],
                    'input' => $value['input'],
                    'class' => '',
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => false,
                    'required' => false,
                    'user_defined' => true,
                    'default' => null,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => '',
                    'group' => self::ASSIGN_GROUP,
                ]
            );
        }
    }
}
