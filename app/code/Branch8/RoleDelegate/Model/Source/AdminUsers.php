<?php

declare(strict_types=1);

namespace Branch8\RoleDelegate\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\User\Model\User;

class AdminUsers implements OptionSourceInterface
{
    /**
     * @var UserCollectionFactory
     */
    private UserCollectionFactory $userCollectionFactory;

    /**
     * AdminUser constructor.
     *
     * @param UserCollectionFactory $userCollectionFactory
     */
    public function __construct(
        UserCollectionFactory $userCollectionFactory
    ){
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => '']];

        $userCollection = $this->getUserCollection();
        /** @var User $user */
        foreach ($userCollection as $user) {
            $options[] = [
                'value' => $user->getId(),
                'label' => $user->getUsername()
            ];
        }

        return $options;
    }

    public function toArray(): array
    {
        $options = [];
        $userCollection = $this->getUserCollection();
        /** @var User $user */
        foreach ($userCollection as $user) {
            $options[$user->getId()] = $user->getUsername();
        }

        return $options;
    }

    /**
     * Retrieve user collection.
     *
     * @return UserCollection
     */
    private function getUserCollection(): UserCollection
    {
        return $this->userCollectionFactory->create()
            ->addFieldToSelect(['user_id', 'username'])
            ->setOrder('username', 'ASC');
    }
}
