<?php

namespace Branch8\PointMoneyConfig\Setup;

use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigHelper;
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
        EavSetupFactory $eavSetupFactory,
        PointMoneyConfigHelper $helper
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->helper          = $helper;
    }

    /**
     * @inheritdoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_TYPE)) {
            $this->createAttributeType($eavSetup);
        }

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_PRODUCT_POINT)) {
            $this->createAttributeProductPoint($eavSetup);
        }

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_TYPE)) {
            $this->createAttributeFreeRatioUpperRedeemLimitType($eavSetup);
        }

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE)) {
            $this->createAttributeFreeRatioUpperRedeemLimitValue($eavSetup);
        }

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE)) {
            $this->createAttributeFreeRatioLowerRedeemLimitType($eavSetup);
        }

        if (!$eavSetup->getAttributeId('catalog_product', $this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE)) {
            $this->createAttributeFreeRatioLowerRedeemLimitValue($eavSetup);
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeType(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_TYPE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Config Type',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType::TYPE_ONLY_MONEY,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeProductPoint(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_PRODUCT_POINT,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Product Point',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeFreeRatioUpperRedeemLimitType(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_TYPE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Free Ratio Upper Redeem Limit Type',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeFreeRatioUpperRedeemLimitValue(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_FREE_RATIO_UPPER_REDEEM_LIMIT_VALUE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Free Ratio Upper Redeem Limit Value',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeFreeRatioLowerRedeemLimitType(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Free Ratio Lower Redeem Limit Type',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType::TYPE_PERCENTAGE,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return void
     */
    public function createAttributeFreeRatioLowerRedeemLimitValue(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Point:Money Free Ratio Lower Redeem Limit Value',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }
}
