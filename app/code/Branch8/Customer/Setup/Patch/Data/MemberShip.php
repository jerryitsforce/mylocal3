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

class MemberShip implements DataPatchInterface{
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
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeSetFactory $attributeSetFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        GroupFactory $groupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->_storeManager    = $storeManager;
        $this->scopeConfig       = $scopeConfig;
        $this->groupFactory     = $groupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $connection = $this->moduleDataSetup->getConnection();
        //add default organization for default group
        $sqlDefaultOrganization = 'insert into branch8_customer_organization values(1, "EC Customer", now(), 0, 1000, "ec_customer");';
        //set default Magento group to default Organization
        $connection->query($sqlDefaultOrganization);

        $customerEntity = $this->eavConfig->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'group_date', [
            'type'             => 'datetime',
            'input'            => 'text',
            'label'            => 'Group date',
            'visible'          => false,
            'source'           => '',
            'required'         => false,
            'user_defined'     => true,
            'system'           => false,
            'global'           => true,
            'visible_on_front' => false,
            'sort_order'       => 104,
            'position'         => 104
        ]);
        $customAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'group_date');
        $customAttribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => []
        ]);
        $customAttribute->save();

        //set default group to default Organization
        $storeId = $this->_storeManager->getStore()->getCode();
        $groupId = $this->scopeConfig->getValue(
            \Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $defaultGroup = $this->groupFactory->create()->load($groupId);

        $defaultGroupFullname = 'Default Organization - '.$defaultGroup->getCustomerGroupCode();
        $sqlDefaultGroup = 'update customer_group set organization=1, period=12, fullname="'.$defaultGroupFullname.'" where customer_group_id=' . $groupId;
        $connection->query($sqlDefaultGroup);
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
