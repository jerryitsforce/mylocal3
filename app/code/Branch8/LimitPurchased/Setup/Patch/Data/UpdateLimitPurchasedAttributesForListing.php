<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class UpdateLimitPurchasedAttributesForListing implements DataPatchInterface, PatchRevertableInterface
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
        $this->moduleDataSetup->getConnection()->startSetup();
        
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        
        // List of limit purchased attributes to update
        $attributesToUpdate = [
            'limit_purchased_enable',
            'limit_purchased_qty',
            'limit_purchased_start_time',
            'limit_purchased_end_time',
            'limit_purchased_customer_group'
        ];
        
        // Update each attribute to be used in product listing
        foreach ($attributesToUpdate as $attributeCode) {
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeCode,
                'used_in_product_listing',
                1
            );
        }
        
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        
        // List of limit purchased attributes to revert
        $attributesToRevert = [
            'limit_purchased_enable',
            'limit_purchased_qty',
            'limit_purchased_start_time',
            'limit_purchased_end_time',
            'limit_purchased_customer_group'
        ];
        
        // Revert each attribute back to not used in product listing
        foreach ($attributesToRevert as $attributeCode) {
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeCode,
                'used_in_product_listing',
                0
            );
        }
        
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
            AddLimitPurchasedEnableProductAttribute::class,
            AddLimitPurchasedQtyProductAttribute::class,
            AddLimitPurchasedStartTimeProductAttribute::class,
            AddLimitPurchasedEndTimeProductAttribute::class,
            AddLimitPurchasedCustomerGroupProductAttribute::class
        ];
    }
}