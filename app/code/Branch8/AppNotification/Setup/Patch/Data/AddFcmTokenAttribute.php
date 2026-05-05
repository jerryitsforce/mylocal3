<?php
namespace Branch8\AppNotification\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;

class AddFcmTokenAttribute implements DataPatchInterface
{
    private $customerSetupFactory;

    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->getSetup()]);

        $customerSetup->addAttribute(Customer::ENTITY, 'fcm_token', [
            'type' => 'text',
            'label' => 'FCM Token',
            'input' => 'text',
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'system' => false,
            'position' => 999,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'fcm_token');
        $attribute->setData('used_in_forms', []);
        $attribute->save();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    private function getSetup()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Setup\ModuleDataSetupInterface::class);
    }
}
