<?php
/**
 * Copyright © 2016 SW-THEMES. All rights reserved.
 */

namespace Branch8\Mmegamenu\Setup;

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.0', '<=')) {
            // set new resource model paths
            /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */

            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $menu_attributes = [
                'branch8_megamenu_item_label' => [
                    'type' => 'varchar',
                    'label' => 'Label on menu',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 10,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Branch8 Megamenu'
                ],
                'branch8_megamenu_item_background' => [
                    'type' => 'varchar',
                    'label' => 'Label background color',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 15,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Branch8 Megamenu'
                ]
            ];

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
            }

            $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Branch8 Megamenu');

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $idg,
                    $item,
                    $data['sort_order']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.1', '<=')) {
            // set new resource model paths
            /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */

            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $menu_attributes = [
                'branch8_megamenu_item_logo' => [
                    'type' => 'varchar',
                    'label' => 'Logo on Megamenu',
                    'input' => 'image',
                    'required' => false,
                    'sort_order' => 16,
                    'source' => '',
                    'visible' => true,
                    'user_defined' => false,
                    'default' => null,
                    'used_in_product_listing' => true,
                    'visible_on_front' => true,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
                    'group' => 'Branch8 Megamenu'
                ],
                'branch8_megamenu_item_static_content' => [
                    'type' => 'text',
                    'label' => 'Static Content',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 17,
                    'used_in_product_listing' => true,
                    'visible_on_front' => true,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Branch8 Megamenu'
                ]
            ];

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
            }

            $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Branch8 Megamenu');

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $idg,
                    $item,
                    $data['sort_order']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2', '<=')) {
            // set new resource model paths
            /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */

            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $menu_attributes = [
                'branch8_megamenu_item_static_content_css_class' => [
                    'type' => 'text',
                    'label' => 'Static Content CSS Class',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 18,
                    'used_in_product_listing' => true,
                    'visible_on_front' => true,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Branch8 Megamenu'
                ]
            ];

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
            }

            $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Branch8 Megamenu');

            foreach($menu_attributes as $item => $data) {
                $categorySetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $idg,
                    $item,
                    $data['sort_order']
                );
            }
        }

        $setup->endSetup();
    }
}
