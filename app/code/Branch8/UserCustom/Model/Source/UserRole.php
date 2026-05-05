<?php

namespace Branch8\UserCustom\Model\Source;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Framework\Data\OptionSourceInterface;
class UserRole implements OptionSourceInterface
{
    protected $roleCollectionFactory;
    public function __construct(\Magento\Authorization\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory)
    {
        $this->roleCollectionFactory = $roleCollectionFactory;
    }

    public function toOptionArray()
    {
        $roles = $this->roleCollectionFactory->create()->addFieldToFilter('role_type', RoleGroup::ROLE_TYPE);;
        $data = [];
        foreach ($roles->getData() as $role){
            $data[] = [
                'value' => $role['role_id'],
                'label' => $role['role_name']
            ];
        }
        return $data;
    }
}
