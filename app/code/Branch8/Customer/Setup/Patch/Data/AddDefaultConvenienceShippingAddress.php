<?php

namespace Branch8\Customer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\GroupFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddDefaultConvenienceShippingAddress implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    protected $eavConfig;

    protected $attributeSetFactory;

    protected $_storeManager;

    protected $scopeConfig;

    protected $groupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory          $eavSetupFactory,
        Config                   $eavConfig,
        AttributeSetFactory      $attributeSetFactory,
        StoreManagerInterface    $storeManager,
        ScopeConfigInterface     $scopeConfig,
        GroupFactory             $groupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->groupFactory = $groupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $this->eavConfig->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        $attrName = 'default_convenience_shipping';
        $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, $attrName, [
            'type' => 'int',
            'input' => 'text',
            'label' => 'Default shipping address for convenience store pickup',
            'visible' => false,
            'source' => '',
            'required' => false,
            'user_defined' => true,
            'system' => false,
            'global' => true,
            'visible_on_front' => true,
            'sort_order' => 101,
            'position' => 101
        ]);
        $customAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attrName);
        $customAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => []
        ]);
        $customAttribute->save();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
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
    public static function getVersion()
    {
        return '1.0.1';
    }

}