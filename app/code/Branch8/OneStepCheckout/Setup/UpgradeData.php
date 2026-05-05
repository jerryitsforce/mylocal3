<?php

namespace Branch8\OneStepCheckout\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

     /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param Config $eavConfig
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $addressAttributeCode = 'hotai_address_type';
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute('customer_address', $addressAttributeCode, [
                'type' => 'varchar',
                'input' => 'select',
                'label' => 'Hotai Address Type',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'source' => \Branch8\OneStepCheckout\Model\Source\HotaiAddressType::class,
                'default' => \Branch8\OneStepCheckout\Model\Source\HotaiAddressType::ADDRESS_TYPE_NORMAL,
                'system'=> false,
                'group'=> 'General',
                'global' => true,
                'visible_on_front' => true,
            ]);
        
            $customAttribute = $this->eavConfig->getAttribute('customer_address', $addressAttributeCode);

            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address','customer_address_edit','customer_register_address']
            );
            $customAttribute->save();
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            //referrer_code
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'referrer_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Referrer Code',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'referrer_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Referrer Code',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'),
                'referrer_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Referrer Code',
                ]
            );

            //order_note
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'order_note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 300,
                    'nullable' => true,
                    'comment' => 'Order Note',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'order_note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 300,
                    'nullable' => true,
                    'comment' => 'Order Note',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order_grid'),
                'order_note',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 300,
                    'nullable' => true,
                    'comment' => 'Order Note',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            //cvs_store_code 
            $eavSetup->addAttribute('customer_address', 'cvs_store_code', [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Store Address - Store Code',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system'=> false,
                'group'=> 'General',
                'global' => true,
                'sort_order' => 160,
                'position' => 160,
                'visible_on_front' => true
            ]);
            $customAttribute = $this->eavConfig->getAttribute('customer_address', 'cvs_store_code');
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address','customer_address_edit','customer_register_address']
            );
            $customAttribute->save();

            //cvs_store_name
            $eavSetup->addAttribute('customer_address', 'cvs_store_name', [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Store Address - Store Name',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system'=> false,
                'group'=> 'General',
                'global' => true,
                'sort_order' => 170,
                'position' => 170,
                'visible_on_front' => true
            ]);
            $customAttribute = $this->eavConfig->getAttribute('customer_address', 'cvs_store_name');
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address','customer_address_edit','customer_register_address']
            );
            $customAttribute->save();

            //cvs_store_servicetype
            $eavSetup->addAttribute('customer_address', 'cvs_store_servicetype', [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Store Address - Service Type',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system'=> false,
                'group'=> 'General',
                'global' => true,
                'sort_order' => 180,
                'position' => 180,
                'visible_on_front' => true
            ]);
            $customAttribute = $this->eavConfig->getAttribute('customer_address', 'cvs_store_servicetype');
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address','customer_address_edit','customer_register_address']
            );
            $customAttribute->save();

            //cvs_store_outside
            $eavSetup->addAttribute('customer_address', 'cvs_store_outside', [
                'type' => 'int',
                'input' => 'boolean',
                'label' => 'Store Address - Is Store Outside',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'system'=> false,
                'group'=> 'General',
                'global' => true,
                'sort_order' => 190,
                'position' => 190,
                'visible_on_front' => true
            ]);
            $customAttribute = $this->eavConfig->getAttribute('customer_address', 'cvs_store_outside');
            $customAttribute->setData(
                'used_in_forms',
                ['adminhtml_customer_address','customer_address_edit','customer_register_address']
            );
            $customAttribute->save();
        }

        $setup->endSetup();
    }
}