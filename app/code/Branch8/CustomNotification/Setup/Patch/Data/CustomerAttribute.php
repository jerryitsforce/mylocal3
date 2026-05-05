<?php

namespace Branch8\CustomNotification\Setup\Patch\Data;

use Magento\Catalog\Ui\DataProvider\Product\ProductCollectionFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
class CustomerAttribute implements DataPatchInterface, PatchRevertableInterface
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
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute
     */
    private $attributeResource;
    private $attributeSetFactory;

    /**
     * CustomerAttribute Constructor
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\ResourceModel\Attribute $attributeResource
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        LoggerInterface $logger,
        \Magento\Customer\Model\ResourceModel\Attribute $attributeResource,
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
        $this->attributeResource = $attributeResource;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addLevelsAttribute();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addLevelsAttribute()
    {
//        $eavSetup = $this->eavSetupFactory->create();
//        $eavSetup->addAttribute(
//            \Magento\Customer\Model\Customer::ENTITY,
//            'level_id',
//            [
//                'type' => 'int',
//                'label' => 'Customer Levels',
//                'input' => 'select',
//                'source' => \Branch8\CustomNotification\Model\Customer\Attribute\Source\Levels::class,
//                'required' => 0,
//                'visible' => 1,
//                'user_defined' => 1,
//                'default' => 2,
//                'sort_order' => 100,
//                'position' => 100,
//                'system' => 0
//            ]
//        );
        $eavSetup = $this->eavSetupFactory->create();

        $customerEntity = $this->eavConfig->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $eavSetup->addAttribute('customer', 'levels_id', [
            'type'             => 'int',
            'input'            => 'select',
            'label'            => 'Customer Levels',
            'visible'          => false,
            'source'            => \Branch8\CustomNotification\Model\Customer\Attribute\Source\Levels::class,
            'required'         => false,
            'user_defined'     => true,
            'system'           => false,
            'global'           => true,
            'default'          => 2,
            'visible_on_front' => false,
            'sort_order'       => 100,
            'position'         => 100
        ]);
        $customAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'levels_id');
        $customAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => ['adminhtml_customer', 'customer_account_edit']
        ]);
        $customAttribute->save();
//        $this->attributeResource->save($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     *
     */
    public function revert()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
