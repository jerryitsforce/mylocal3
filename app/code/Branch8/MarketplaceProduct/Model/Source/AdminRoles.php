<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Authorization\Model\ResourceModel\Role\Collection as RoleCollection;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Authorization\Model\Role;

class AdminRoles implements OptionSourceInterface
{
    /**
     * @var RoleCollectionFactory
     */
    private RoleCollectionFactory $roleCollectionFactory;

    /**
     * AdminUser constructor.
     *
     * @param RoleCollectionFactory $roleCollectionFactory
     */
    public function __construct(RoleCollectionFactory $roleCollectionFactory)
    {
        $this->roleCollectionFactory = $roleCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => '']];

        $roleCollection = $this->getRoleCollection();
        /** @var Role $role */
        foreach ($roleCollection as $role) {
            $options[] = [
                'value' => $role->getId(),
                'label' => $role->getRoleName()
            ];
        }

        return $options;
    }

    public function toArray(): array
    {
        $options = [];
        $roleCollection = $this->getRoleCollection();
        /** @var Role $role */
        foreach ($roleCollection as $role) {
            $options[$role->getId()] = $role->getRoleName();
        }

        return $options;
    }

    /**
     * Retrieve role collection.
     *
     * @return RoleCollection
     */
    private function getRoleCollection(): RoleCollection
    {
        return $this->roleCollectionFactory->create()
            ->addFieldToSelect(['role_id', 'role_name'])
            ->setRolesFilter();
    }
}
